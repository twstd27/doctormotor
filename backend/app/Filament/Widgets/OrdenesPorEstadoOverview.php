<?php

namespace App\Filament\Widgets;

use App\Models\OrdenTrabajo;
use Filament\Widgets\Widget;

class OrdenesPorEstadoOverview extends Widget
{
    protected string $view = 'filament.widgets.ordenes-por-estado-overview';

    protected int | string | array $columnSpan = 'full';

    private const LABELS = [
        'recepcionado' => 'Recepcionado',
        'en_diagnostico' => 'En diagnóstico',
        'esperando_aprobacion' => 'Esperando aprobación',
        'en_reparacion' => 'En reparación',
        'control_calidad' => 'Control de calidad',
        'listo_entrega' => 'Listo para entrega',
        'entregado' => 'Entregado',
    ];

    private const COLORES = [
        'recepcionado' => 'gray',
        'en_diagnostico' => 'info',
        'esperando_aprobacion' => 'warning',
        'en_reparacion' => 'success',
        'control_calidad' => 'info',
        'listo_entrega' => 'success',
        'entregado' => 'gray',
    ];

    private const CLASES_DOT = [
        'gray' => 'bg-gray-500',
        'info' => 'bg-cyan-400',
        'warning' => 'bg-amber-400',
        'success' => 'bg-lime-400',
    ];

    protected function getViewData(): array
    {
        $conteos = OrdenTrabajo::query()
            ->whereIn('estado', array_keys(self::LABELS))
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $filas = collect(self::LABELS)
            ->map(fn (string $label, string $estado) => [
                'label' => $label,
                'dotClass' => self::CLASES_DOT[self::COLORES[$estado]],
                'total' => (int) ($conteos[$estado] ?? 0),
            ])
            ->values()
            ->all();

        return ['filas' => $filas];
    }
}
