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
                    'to' => $notificacion->telefono_destino,
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
                $notificacion->update(['estado' => 'fallido', 'error' => $response->body()]);
            }
        } catch (\Throwable $e) {
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
