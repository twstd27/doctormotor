<?php

namespace App\Filament\Widgets;

use App\Models\OrdenTrabajo;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdenesPorEstadoOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Órdenes por estado';

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

    protected function getStats(): array
    {
        $conteos = OrdenTrabajo::query()
            ->whereIn('estado', array_keys(self::LABELS))
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return collect(self::LABELS)
            ->map(fn (string $label, string $estado) => Stat::make($label, (string) ($conteos[$estado] ?? 0))
                ->color(self::COLORES[$estado]))
            ->values()
            ->all();
    }
}
