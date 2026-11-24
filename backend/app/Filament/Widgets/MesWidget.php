<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\SoloSuperAdminWidget;
use App\Models\GastoEgreso;
use App\Models\Pago;
use Filament\Widgets\Widget;

/**
 * El mes en curso: utilidad y cuatro semanas de ingresos contra egresos. Es información de
 * dirección, solo para el super_admin.
 */
class MesWidget extends Widget
{
    use SoloSuperAdminWidget;

    protected string $view = 'filament.widgets.mes';

    protected static ?int $sort = 40;

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        $inicio = now()->startOfMonth();
        $fin = now()->endOfMonth();

        $ingresos = (float) Pago::query()->whereBetween('fecha', [$inicio, $fin])->sum('monto');
        $egresos = (float) GastoEgreso::query()->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])->sum('monto');

        // Semanas del mes: 1-7, 8-14, 15-21 y desde el 22 hasta el final.
        $rangos = [[1, 7], [8, 14], [15, 21], [22, $fin->day]];
        $semanas = collect($rangos)->map(function (array $r) use ($inicio) {
            $desde = $inicio->copy()->day($r[0])->startOfDay();
            $hasta = $inicio->copy()->day($r[1])->endOfDay();

            return [
                'ingresos' => (float) Pago::query()->whereBetween('fecha', [$desde, $hasta])->sum('monto'),
                'egresos' => (float) GastoEgreso::query()->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->sum('monto'),
            ];
        });

        $mayor = max($semanas->max(fn (array $s) => max($s['ingresos'], $s['egresos'])), 1);

        return [
            'mes' => ucfirst(now()->translatedFormat('F')),
            'utilidad' => $ingresos - $egresos,
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'semanas' => $semanas->map(fn (array $s) => $s + [
                'altoIngresos' => round($s['ingresos'] / $mayor * 100),
                'altoEgresos' => round($s['egresos'] / $mayor * 100),
            ]),
        ];
    }
}
