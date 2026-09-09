<?php

namespace App\Services;

use App\Models\Notificacion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Business Cloud API (Meta). Requiere WHATSAPP_TOKEN y WHATSAPP_PHONE_NUMBER_ID
 * en .env — ver docs/01-ARQUITECTURA.md sección de WhatsApp.
 *
 * Sin esas variables configuradas, el envío se degrada a un log local (modo desarrollo) en
 * vez de fallar — así el resto del sistema funciona igual en una máquina sin credenciales
 * reales de Meta todavía.
 */
class WhatsAppService
{
    /**
     * Texto humano equivalente a cada plantilla de Meta, usado solo para armar el enlace
     * wa.me de respaldo (clic-para-chatear) mientras no hay cuenta de WhatsApp Business.
     *
     * @var array<string, \Closure(array<string, mixed>): string>
     */
    private const TEXTOS_PLANTILLA = [
        // Meta no aprueba una plantilla si una variable queda al principio o al final del
        // cuerpo — por eso estas tres (antes terminaban justo en {link}) llevan una frase
        // corta después del enlace.
        'invitacion_cuenta' => "Hola {nombre}, te invitamos a seguir el estado de tu vehículo en Doctor Motor. Ingresa a este enlace para crear tu cuenta: {link}. ¡Te esperamos!",
        'invitacion_tecnico' => "Hola, te invitamos a unirte al equipo de Doctor Motor. Define tu contraseña aquí: {link}. ¡Bienvenido al equipo!",
        'enlace_acceso' => "Tu enlace de acceso a Doctor Motor: {link}. Válido por tiempo limitado.",
        'ot_en_diagnostico' => "Tu {vehiculo} (OT {codigo_ot}) ya está en diagnóstico.",
        'ot_esperando_aprobacion' => "Tenemos un presupuesto listo para tu {vehiculo} (OT {codigo_ot}). Revísalo en Doctor Motor.",
        'ot_en_reparacion' => "Tu {vehiculo} (OT {codigo_ot}) ya está en reparación.",
        'ot_lista_entrega' => "¡Tu {vehiculo} (OT {codigo_ot}) está listo para entrega!",
        'presupuesto_enviado' => "Te enviamos el presupuesto de la OT {codigo_ot} por Bs {total}. Revísalo en Doctor Motor.",
    ];

    /**
     * Enlace wa.me (clic para chatear) con el mensaje ya redactado, para que un cajero o
     * administrador lo mande a mano mientras no hay cuenta de WhatsApp Business Cloud API.
     * No requiere ninguna credencial de Meta.
     *
     * @param  array<string, mixed>  $parametros
     */
    public function linkWaMe(string $telefono, string $plantilla, array $parametros = []): string
    {
        $texto = self::TEXTOS_PLANTILLA[$plantilla] ?? '';

        foreach ($parametros as $clave => $valor) {
            $texto = str_replace('{'.$clave.'}', (string) $valor, $texto);
        }

        return 'https://wa.me/'.$this->normalizarTelefono($telefono).'?text='.rawurlencode($texto);
    }

    /**
     * WhatsApp exige el número completo en formato internacional (sin "+", sin "0" inicial).
     * Los clientes/técnicos casi siempre registran solo el número local boliviano — se asume
     * el código de país 591 salvo que ya lo hayan puesto explícito (con "+" adelante).
     */
    private function normalizarTelefono(string $telefono): string
    {
        $tieneCodigoExplicito = str_starts_with(trim($telefono), '+');
        $digitos = preg_replace('/\D+/', '', $telefono);

        if ($tieneCodigoExplicito || str_starts_with($digitos, '591')) {
            return $digitos;
        }

        return '591'.$digitos;
    }

    public function enviarPlantilla(
        string $telefono,
        string $plantilla,
        array $parametros = [],
        ?int $userId = null,
        ?int $ordenTrabajoId = null,
    ): Notificacion {
        $notificacion = Notificacion::create([
            'user_id' => $userId,
            'telefono_destino' => $telefono,
            'canal' => 'whatsapp',
            'plantilla' => $plantilla,
            'orden_trabajo_id' => $ordenTrabajoId,
            'payload' => $parametros,
            'estado' => 'pendiente',
        ]);

        $this->despachar($notificacion);

        return $notificacion;
    }

    public function despachar(Notificacion $notificacion): void
    {
        if (! $this->configurado()) {
            Log::info('[whatsapp-mock] Sin WHATSAPP_TOKEN configurado — no se envía de verdad', [
                'notificacion_id' => $notificacion->id,
                'telefono' => $notificacion->telefono_destino,
                'plantilla' => $notificacion->plantilla,
                'payload' => $notificacion->payload,
            ]);

            $notificacion->update(['estado' => 'enviado', 'enviado_at' => now()]);

            return;
        }

        try {
            $response = Http::withToken(config('services.whatsapp.token'))
                ->post(config('services.whatsapp.api_url').'/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $this->normalizarTelefono($notificacion->telefono_destino),
                    'type' => 'template',
                    'template' => [
                        'name' => $notificacion->plantilla,
                        'language' => ['code' => 'es'],
                        'components' => $this->componentesDesdeParametros($notificacion->payload ?? []),
                    ],
                ]);

            if ($response->successful()) {
                $notificacion->update(['estado' => 'enviado', 'enviado_at' => now()]);
            } else {
                // El estado queda en la fila (visible en /admin/notificaciones), pero también
                // se loguea: la primera vez que se probó esto en serio, el único rastro del
                // rechazo de Meta era la fila en la base — nada en el log ni en la UI.
                Log::warning('[whatsapp] Meta rechazó el envío', [
                    'notificacion_id' => $notificacion->id,
                    'telefono' => $notificacion->telefono_destino,
                    'plantilla' => $notificacion->plantilla,
                    'respuesta' => $response->json() ?? $response->body(),
                ]);

                $notificacion->update(['estado' => 'fallido', 'error' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('[whatsapp] Excepción al llamar a la API de Meta', [
                'notificacion_id' => $notificacion->id,
                'mensaje' => $e->getMessage(),
            ]);

            $notificacion->update(['estado' => 'fallido', 'error' => $e->getMessage()]);
        }
    }

    public function configurado(): bool
    {
        return filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'));
    }

    private function componentesDesdeParametros(array $parametros): array
    {
        if (empty($parametros)) {
            return [];
        }

        return [[
            'type' => 'body',
            'parameters' => collect($parametros)->values()->map(fn ($v) => ['type' => 'text', 'text' => (string) $v])->all(),
        ]];
    }
}
