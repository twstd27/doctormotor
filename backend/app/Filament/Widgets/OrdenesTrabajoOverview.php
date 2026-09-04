<?php

namespace App\Filament\Widgets;

use App\Models\OrdenTrabajo;
use App\Models\Producto;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdenesTrabajoOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $inicioSemana = now()->startOfWeek();
        $finSemana = now()->endOfWeek();
        $inicioSemanaAnterior = now()->subWeek()->startOfWeek();
        $finSemanaAnterior = now()->subWeek()->endOfWeek();

        $estaSemana = OrdenTrabajo::whereBetween('fecha_ingreso', [$inicioSemana, $finSemana])->count();
        $semanaAnterior = OrdenTrabajo::whereBetween('fecha_ingreso', [$inicioSemanaAnterior, $finSemanaAnterior])->count();
        $diferencia = $estaSemana - $semanaAnterior;

        $activas = OrdenTrabajo::whereNotIn('estado', ['entregado', 'cancelado'])->count();
        $esperandoAprobacion = OrdenTrabajo::where('estado', 'esperando_aprobacion')->count();
        $stockBajo = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')->where('activo', true)->count();

        return [
            Stat::make('Órdenes esta semana', $estaSemana)
                ->description(
                    $diferencia === 0
                        ? 'Igual que la semana pasada'
                        : ($diferencia > 0 ? "+{$diferencia} vs. semana pasada" : "{$diferencia} vs. semana pasada")
                )
                ->descriptionIcon($diferencia >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($diferencia >= 0 ? 'success' : 'danger'),
            Stat::make('Órdenes activas', $activas)
                ->description('En todo el tablero')
                ->color('info'),
            Stat::make('Esperando aprobación', $esperandoAprobacion)
                ->description('Presupuestos pendientes del cliente')
                ->color($esperandoAprobacion > 0 ? 'warning' : 'gray'),
            Stat::make('Alertas de stock', $stockBajo)
                ->description('Productos bajo el mínimo')
                ->color($stockBajo > 0 ? 'danger' : 'success'),
        ];
    }
}
