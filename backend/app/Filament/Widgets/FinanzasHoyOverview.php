<?php

namespace App\Filament\Widgets;

use App\Models\GastoEgreso;
use App\Models\Pago;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanzasHoyOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $hoy = now()->toDateString();
        $inicioMes = now()->startOfMonth()->toDateString();

        $cobradoHoy = (float) Pago::whereDate('fecha', $hoy)->sum('monto');
        $egresosHoy = (float) GastoEgreso::whereDate('fecha', $hoy)->sum('monto');
        $ingresosMes = (float) Pago::whereDate('fecha', '>=', $inicioMes)->sum('monto');
        $egresosMes = (float) GastoEgreso::whereDate('fecha', '>=', $inicioMes)->sum('monto');
        $utilidadMes = $ingresosMes - $egresosMes;

        return [
            Stat::make('Cobrado hoy', "Bs {$this->fmt($cobradoHoy)}")
                ->description('Efectivo + QR + tarjeta')
                ->color('success'),
            Stat::make('Egresos hoy', "Bs {$this->fmt($egresosHoy)}")
                ->description('Gastos fijos y variables')
                ->color($egresosHoy > 0 ? 'warning' : 'gray'),
            Stat::make('Neto hoy', "Bs {$this->fmt($cobradoHoy - $egresosHoy)}")
                ->description('Cobrado − egresos de hoy')
                ->color($cobradoHoy - $egresosHoy >= 0 ? 'success' : 'danger'),
            Stat::make('Utilidad del mes', "Bs {$this->fmt($utilidadMes)}")
                ->description('Desde el 1° del mes')
                ->color($utilidadMes >= 0 ? 'success' : 'danger'),
        ];
    }

    private function fmt(float $n): string
    {
        return number_format($n, 2, '.', ',');
    }
}
