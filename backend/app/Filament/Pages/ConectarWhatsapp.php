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
    public function completarConexion(string $code, string $wabaId, string $phoneNumberId): void
    {
        $this->error = null;

        $intercambio = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
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

        // Suscribe esta app a los eventos del WABA (mensajes entrantes, etc.) — sin esto,
        // el número queda vinculado pero el webhook de la app nunca recibe nada de él.
        $suscripcion = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v21.0/{$wabaId}/subscribed_apps");

        $this->wabaId = $wabaId;
        $this->phoneNumberId = $phoneNumberId;

        Notification::make()
            ->title('WhatsApp conectado')
            ->body(
                "WABA ID: {$wabaId} · Phone Number ID: {$phoneNumberId}.".
                ($suscripcion->successful() ? '' : ' (ojo: no se pudo suscribir la app al WABA, revisa el log)').
                ' Copia el Phone Number ID en WHATSAPP_PHONE_NUMBER_ID del .env.'
            )
            ->success()
            ->persistent()
            ->send();
    }
}
