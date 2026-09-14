<?php

namespace App\Services;

use App\Events\OrdenTrabajoActualizada;
use App\Models\OrdenTrabajo;
use Illuminate\Support\Facades\DB;

class OrdenTrabajoEstadoService
{
    /**
     * Plantilla de WhatsApp por cada estado del Kanban — solo se notifican los que
     * realmente le importan al cliente, no cada micro-transición interna.
     */
    private const PLANTILLAS_POR_ESTADO = [
        'en_diagnostico' => 'ot_en_diagnostico',
        'esperando_aprobacion' => 'ot_esperando_aprobacion',
        'en_reparacion' => 'ot_en_reparacion',
        'listo_entrega' => 'ot_lista_entrega',
    ];

    public function __construct(private WhatsAppService $whatsApp) {}

    /**
     * Único punto de entrada para mover el estado de una OT: además del cambio en sí,
     * registra el historial, transmite el evento en tiempo real y notifica por WhatsApp.
     * Usado tanto por el cambio manual (Kanban) como por flujos automáticos (ej. un
     * adicional de presupuesto que fuerza "esperando_aprobacion").
     */
    public function cambiarA(OrdenTrabajo $ordenTrabajo, string $nuevoEstado, int $userId, ?string $comentario = null): OrdenTrabajo
    {
        if ($ordenTrabajo->estado === $nuevoEstado) {
            return $ordenTrabajo;
        }

        $estadoAnterior = $ordenTrabajo->estado;

        DB::transaction(function () use ($ordenTrabajo, $nuevoEstado, $userId, $comentario, $estadoAnterior) {
            $ordenTrabajo->update([
                'estado' => $nuevoEstado,
                'fecha_entrega_real' => $nuevoEstado === 'entregado' ? now() : $ordenTrabajo->fecha_entrega_real,
            ]);

            $ordenTrabajo->estadosHistorial()->create([
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado,
                'user_id' => $userId,
                'comentario' => $comentario,
            ]);
        });

        $ordenTrabajo = $ordenTrabajo->fresh();
        broadcast(new OrdenTrabajoActualizada($ordenTrabajo));
        $this->notificar($ordenTrabajo);

        return $ordenTrabajo;
    }

    private function notificar(OrdenTrabajo $ordenTrabajo): void
    {
        $plantilla = self::PLANTILLAS_POR_ESTADO[$ordenTrabajo->estado] ?? null;
        if (! $plantilla) {
            return;
        }

        $ordenTrabajo->loadMissing('cliente', 'vehiculo');

        $this->whatsApp->enviarPlantilla(
            telefono: $ordenTrabajo->cliente->telefono_whatsapp,
            plantilla: $plantilla,
            parametros: [
                'vehiculo' => "{$ordenTrabajo->vehiculo->marca} {$ordenTrabajo->vehiculo->modelo}",
                'codigo_ot' => $ordenTrabajo->codigo,
            ],
            userId: $ordenTrabajo->cliente->user_id,
            ordenTrabajoId: $ordenTrabajo->id,
        );
    }
}
