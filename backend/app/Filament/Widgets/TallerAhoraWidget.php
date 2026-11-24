<?php

namespace App\Filament\Widgets;

use App\Models\OrdenTrabajo;
use App\Support\EstadoOrden;
use Filament\Widgets\Widget;

/**
 * "Taller ahora": un tablero compacto con los vehículos que hay en el taller, por estado.
 */
class TallerAhoraWidget extends Widget
{
    protected string $view = 'filament.widgets.taller-ahora';

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    /** Columnas del tablero y su color (los mismos que usa el resto del panel). */
    private const COLUMNAS = [
        'recepcionado' => ['nombre' => 'Recepcionado', 'color' => 'var(--dm-muted)'],
        'en_diagnostico' => ['nombre' => 'Diagnóstico', 'color' => 'var(--dm-azul)'],
        'esperando_aprobacion' => ['nombre' => 'Aprobación', 'color' => 'var(--dm-ambar)'],
        'en_reparacion' => ['nombre' => 'En reparación', 'color' => 'var(--dm-lima)'],
        'control_calidad' => ['nombre' => 'Calidad', 'color' => 'var(--dm-violeta)'],
        'listo_entrega' => ['nombre' => 'Listo para entrega', 'color' => 'var(--dm-lima)'],
    ];

    protected function getViewData(): array
    {
        $ordenes = OrdenTrabajo::query()
            ->conSaldo()
            ->whereNotIn('estado', EstadoOrden::CERRADOS)
            ->with(['vehiculo', 'tecnicoAsignado'])
            ->orderBy('fecha_entrega_estimada')
            ->orderBy('fecha_ingreso')
            ->get()
            ->groupBy('estado');

        $columnas = collect(self::COLUMNAS)->map(fn (array $c, string $estado) => $c + [
            'estado' => $estado,
            'ordenes' => $ordenes->get($estado, collect()),
        ]);

        return [
            'columnas' => $columnas,
            'total' => $columnas->sum(fn (array $c) => $c['ordenes']->count()),
        ];
    }
}
