<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Http;

/**
 * Herramienta de un solo uso para conectar el número de WhatsApp Business ya activo del
 * taller (con Coexistencia — sin desconectarlo de la app del celular) usando el Embedded
 * Signup de Meta. No es parte del flujo normal de la app; una vez conectado no hace falta
 * volver a usarla salvo que se reconecte el número.
 */
class ConectarWhatsapp extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Conectar WhatsApp';

    protected static ?string $title = 'Conectar WhatsApp';

    protected string $view = 'filament.pages.conectar-whatsapp';

    public ?string $wabaId = null;

    public ?string $phoneNumberId = null;

    public ?string $error = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->rol === 'super_admin';
    }

    public function getSubheading(): string
    {
        return 'Vincula el número de WhatsApp Business que ya usa el taller en el celular, '
            .'sin perder el historial de chats ni desconectarlo de la app.';
    }

    public function appId(): ?string
    {
        return config('services.whatsapp.app_id');
    }

    public function configId(): ?string
    {
        return config('services.whatsapp.embedded_signup_config_id');
    }

    public function configuracionCompleta(): bool
    {
        return filled($this->appId()) && filled($this->configId()) && filled(config('services.whatsapp.app_secret'));
    }

    /**
     * Recibe lo que capturó el JavaScript de la página (el código intercambiable del login
     * y los IDs del WABA/número que llegaron por el evento WA_EMBEDDED_SIGNUP), y hace el
     * intercambio server-to-server — el App Secret nunca toca el navegador.
     */
    public function completarConexion(string $code, ?string $wabaId = null, ?string $phoneNumberId = null): void
    {
        $this->error = null;

        $intercambio = Http::get('https://graph.facebook.com/v24.0/oauth/access_token', [
            'client_id' => config('services.whatsapp.app_id'),
            'client_secret' => config('services.whatsapp.app_secret'),
            'code' => $code,
        ]);

        if (! $intercambio->successful()) {
            $this->error = 'Meta rechazó el intercambio del código: '.$intercambio->body();
            Notification::make()->title('No se pudo completar la conexión')->body($this->error)->danger()->send();

            return;
        }

        $accessToken = $intercambio->json('access_token');

        // El aviso con el WABA ID (postMessage) no siempre llega al navegador. Sin depender de él,
        // el propio token dice a qué cuentas de WhatsApp Business se dio acceso (debug_token).
        if (blank($wabaId)) {
            $cuentas = $this->cuentasCompartidas($accessToken);

            if ($cuentas === []) {
                $this->error = 'El inicio de sesión funcionó pero Meta no compartió ninguna cuenta de WhatsApp Business: '
                    .'el flujo se cerró antes de elegir o confirmar el número. Vuelve a intentarlo y llega hasta el final.';
                Notification::make()->title('Falta elegir el número')->body($this->error)->warning()->persistent()->send();

                return;
            }

            $wabaId = $cuentas[0];
        }

        // Suscribe esta app a los eventos del WABA (mensajes entrantes, etc.) — sin esto,
        // el número queda vinculado pero el webhook de la app nunca recibe nada de él.
        $suscripcion = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v24.0/{$wabaId}/subscribed_apps");

        // En la coexistencia Meta avisa solo el WABA ID: el número se busca en la cuenta.
        if (blank($phoneNumberId)) {
            $numeros = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/v24.0/{$wabaId}/phone_numbers", ['fields' => 'id,display_phone_number'])
                ->json('data') ?? [];
            $phoneNumberId = $numeros[0]['id'] ?? null;

            if (! $phoneNumberId) {
                $this->error = 'La cuenta quedó conectada pero Meta no devolvió ningún número. Revisa el WABA '.$wabaId.' en Meta Business Suite.';
                Notification::make()->title('Falta el número')->body($this->error)->warning()->persistent()->send();

                return;
            }
        }

        // Coexistencia: Meta da solo 24 horas para pedir la copia de contactos y del historial de
        // chats; pasado ese plazo ya no se puede. Es de una sola vez y los resultados llegan al
        // webhook (que hoy los ignora): se piden igual para no perder la oportunidad.
        foreach (['smb_app_state_sync', 'history'] as $tipoSync) {
            Http::withToken($accessToken)->post("https://graph.facebook.com/v24.0/{$phoneNumberId}/smb_app_data", [
                'messaging_product' => 'whatsapp',
                'sync_type' => $tipoSync,
            ]);
        }

        $this->wabaId = $wabaId;
        $this->phoneNumberId = $phoneNumberId;

        // ¿Quedó el número en la app del celular y en la API a la vez? (coexistencia)
        $estadoNumero = Http::withToken($accessToken)
            ->get("https://graph.facebook.com/v24.0/{$phoneNumberId}", ['fields' => 'is_on_biz_app,platform_type'])
            ->json();
        $coexiste = ($estadoNumero['is_on_biz_app'] ?? false) === true;

        Notification::make()
            ->title('WhatsApp conectado')
            ->body(
                "WABA ID: {$wabaId} · Phone Number ID: {$phoneNumberId}.".
                ($coexiste ? ' El número sigue activo en la app del celular.' : ' Ojo: Meta no confirma que el número siga en la app del celular (platform_type: '.($estadoNumero['platform_type'] ?? 'desconocido').').').
                ($suscripcion->successful() ? '' : ' (ojo: no se pudo suscribir la app al WABA, revisa el log)').
                ' Copia el Phone Number ID en WHATSAPP_PHONE_NUMBER_ID del .env.'
            )
            ->success()
            ->persistent()
            ->send();
    }

    /**
     * IDs de las cuentas de WhatsApp Business a las que la persona dio acceso en el flujo,
     * leídos del propio token con debug_token.
     *
     * @return list<string>
     */
    private function cuentasCompartidas(string $accessToken): array
    {
        $respuesta = Http::get('https://graph.facebook.com/v24.0/debug_token', [
            'input_token' => $accessToken,
            'access_token' => config('services.whatsapp.app_id').'|'.config('services.whatsapp.app_secret'),
        ]);

        return collect($respuesta->json('data.granular_scopes') ?? [])
            ->where('scope', 'whatsapp_business_management')
            ->flatMap(fn (array $permiso) => $permiso['target_ids'] ?? [])
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }
}
