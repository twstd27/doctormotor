<?php

namespace App\Support;

/**
 * Estados de la orden de trabajo: etiqueta y color en un solo lugar para que el tablero,
 * las listas y los widgets del panel muestren siempre lo mismo (HANDOFF §3 del rediseño).
 */
class EstadoOrden
{
    public const ETIQUETAS = [
        'recepcionado' => 'Recepcionado',
        'en_diagnostico' => 'En diagnóstico',
        'esperando_aprobacion' => 'Esperando aprobación',
        'en_reparacion' => 'En reparación',
        'control_calidad' => 'Control de calidad',
        'listo_entrega' => 'Listo para entrega',
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado',
    ];

    /** Estados en los que la orden ya no está en el taller. */
    public const CERRADOS = ['entregado', 'cancelado'];

    public static function etiqueta(?string $estado): string
    {
        return self::ETIQUETAS[$estado] ?? (string) $estado;
    }

    /** Nombre del color de Filament (definidos en AdminPanelProvider::colors). */
    public static function color(?string $estado): string
    {
        return match ($estado) {
            'en_diagnostico' => 'info',
            'esperando_aprobacion' => 'warning',
            'en_reparacion', 'listo_entrega' => 'success',
            'control_calidad' => 'violet',
            'cancelado' => 'danger',
            default => 'gray',
        };
    }
}
