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
        // Mismos textos que las plantillas aprobadas en Meta como Utilidad (WABA del número
        // 74275155, WhatsApp Manager) — el link va en el cuerpo, sin botón. {codigo_ot} ya
        // trae el prefijo "OT-", por eso no se antepone "OT" a mano.
        'invitacion_cuenta_cliente' => 'Hola {nombre}, ya registramos tu cuenta en Doctor Motor. Ingresa aquí {link} para ver el estado de tu vehículo y tus órdenes de trabajo.',
        // Se evita la palabra "contraseña" y las frases tipo "válido por tiempo limitado":
        // el clasificador automático de Meta las confunde con Autenticación (código OTP) o
        // con Marketing, y rechaza la plantilla bajo la categoría Utilidad.
        'invitacion_tecnico' => 'Hola, tu cuenta de técnico en Doctor Motor ya está lista. Usa este enlace para ingresar y activarla: {link}. ¡Bienvenido al equipo!',
        'ot_en_diagnostico' => 'Tu *{vehiculo}* ({codigo_ot}) ya está en *diagnóstico*.',
        // Reemplaza a "ot_esperando_aprobacion" (quedó aprobada como Marketing en Meta y
        // no se puede editar ni recategorizar in place).
        'ot_presupuesto_listo' => 'El presupuesto de tu *{vehiculo}* ({codigo_ot}) ya está listo. Apruébalo en Doctor Motor para continuar con la reparación.',
        'ot_en_reparacion' => 'Tu *{vehiculo}* ({codigo_ot}) ya está en *reparación*.',
        'ot_lista_entrega' => '¡Tu *{vehiculo}* ({codigo_ot}) está *listo para entrega*! Te esperamos en el taller.',
        // Sin "OT" literal antes de {codigo_ot}: el código ya viene con el prefijo
        // ("OT-2026-0001"), ponerlo dos veces salía "de la OT OT-2026-0001".
        'presupuesto_enviado' => 'Te enviamos el presupuesto de la {codigo_ot} por *Bs {total}*. Revísalo en Doctor Motor.',
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
        return $this->linkChat($telefono, $this->redactar($plantilla, $parametros));
    }

    /**
     * El mensaje listo para mandar: la plantilla con sus datos puestos. Los asteriscos y guiones
     * bajos son el formato de WhatsApp (*negrita*, _cursiva_): viajan en el texto y WhatsApp los
     * dibuja al mostrarlo, sin hacer nada especial al copiar o pegar.
     *
     * @param  array<string, mixed>  $parametros
     */
    public function redactar(string $plantilla, array $parametros = []): string
    {
        $texto = self::TEXTOS_PLANTILLA[$plantilla] ?? '';

        foreach ($parametros as $clave => $valor) {
            $texto = str_replace('{'.$clave.'}', (string) $valor, $texto);
        }

        return $texto;
    }

    /**
     * Enlace para abrir el chat con el cliente (https://wa.me/<número>?text=<mensaje>). El texto va
     * codificado en la URL (espacios, saltos de línea, links y emojis incluidos). Si es muy largo
     * (las URL largas fallan en algunos navegadores) se abre el chat sin texto y hay que copiarlo.
     */
    public function linkChat(string $telefono, ?string $texto = null, int $maximo = 1500): string
    {
        $enlace = 'https://wa.me/'.$this->normalizarTelefono($telefono);

        if (blank($texto) || mb_strlen($texto) > $maximo) {
            return $enlace;
        }

        return $enlace.'?text='.rawurlencode($texto);
    }

    /** ¿Los mensajes los manda una persona a mano (true) o la API de Meta sola (false)? */
    public function modoManual(): bool
    {
        return config('services.whatsapp.modo', 'manual') !== 'api';
    }

    /**
     * WhatsApp exige el número completo en formato internacional (sin "+", sin "0" inicial).
     * Los clientes/técnicos casi siempre registran solo el número local boliviano — se asume
     * el código de país 591 salvo que ya lo hayan puesto explícito (con "+" adelante).
     *
     * Pública porque WebhookController también la necesita para poder comparar el número
     * que manda Meta (siempre en formato internacional) contra lo que haya guardado en
     * `clientes.telefono_whatsapp` (que puede estar con o sin el código de país).
     */
    public function normalizarTelefono(string $telefono): string
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
            'mensaje' => $this->redactar($plantilla, $parametros),
            // Manual: queda esperando que alguien lo mande. API: sale ahora mismo.
            'estado' => $this->modoManual() ? 'por_enviar' : 'pendiente',
        ]);

        $this->despachar($notificacion);

        return $notificacion;
    }

    public function despachar(Notificacion $notificacion): void
    {
        // Modo manual: no se llama a Meta. El mensaje queda en «Por enviar» hasta que una persona
        // lo abra en WhatsApp (ver Notificacion::marcarEnviada).
        if ($this->modoManual()) {
            return;
        }

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
                        'components' => $this->componentes($notificacion->payload ?? []),
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

    /**
     * Todos los parámetros van al cuerpo, en el mismo orden en que llegan — tiene que
     * calzar con el orden de {{1}}, {{2}}... de la plantilla ya aprobada en Meta. Ninguna
     * de las plantillas en uso quedó con botón de URL dinámica (se intentó para
     * invitacion_cuenta_cliente, pero Meta aprobó la versión con el link en el texto).
     */
    private function componentes(array $parametros): array
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
