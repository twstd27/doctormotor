<?php

namespace App\Filament\Widgets;

use App\Models\OrdenTrabajo;
use App\Models\Producto;
use Filament\Widgets\Widget;

class RequiereAtencionWidget extends Widget
{
    protected string $view = 'filament.widgets.requiere-atencion';

    protected static ?int $sort = 30;

    private const CLASES_TEXTO = [
        'gray' => 'text-gray-500',
        'info' => 'text-cyan-400',
        'warning' => 'text-amber-400',
        'danger' => 'text-red-400',
    ];

    protected function getViewData(): array
    {
        $activas = OrdenTrabajo::whereNotIn('estado', ['entregado', 'cancelado'])->count();
        $esperandoAprobacion = OrdenTrabajo::where('estado', 'esperando_aprobacion')->count();
        $stockBajo = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')->where('activo', true)->count();

        $filas = [
            [
                'total' => $stockBajo,
                'label' => 'Alertas de stock',
                'descripcion' => 'Productos bajo el mínimo',
                'color' => $stockBajo > 0 ? 'danger' : 'gray',
                'url' => route('filament.admin.resources.productos.index', ['tableFilters' => ['stock_bajo' => ['isActive' => true]]]),
            ],
            [
                'total' => $esperandoAprobacion,
                'label' => 'Esperando aprobación',
                'descripcion' => 'Presupuestos pendientes del cliente',
                'color' => $esperandoAprobacion > 0 ? 'warning' : 'gray',
                'url' => route('filament.admin.resources.ordenes-trabajo.index', ['tableFilters' => ['estado' => ['value' => 'esperando_aprobacion']]]),
            ],
            [
                'total' => $activas,
                'label' => 'Órdenes activas',
                'descripcion' => 'En todo el tablero',
                'color' => 'info',
                'url' => route('filament.admin.resources.ordenes-trabajo.index'),
            ],
        ];

        foreach ($filas as &$fila) {
            $fila['claseTexto'] = self::CLASES_TEXTO[$fila['color']];
        }

        return ['filas' => $filas];
    }
}
