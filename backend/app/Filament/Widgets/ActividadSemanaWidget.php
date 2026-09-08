<?php

namespace App\Filament\Widgets;

use App\Models\OrdenTrabajo;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class ActividadSemanaWidget extends Widget
{
    protected string $view = 'filament.widgets.actividad-semana';

    protected static ?int $sort = 40;

    private const LABELS_DIA = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

    private const ALTURA_MAX_PX = 64;

    protected function getViewData(): array
    {
        $inicioSemana = now()->startOfWeek(Carbon::MONDAY);
        $inicioSemanaAnterior = now()->subWeek()->startOfWeek(Carbon::MONDAY);
        $finSemanaAnterior = now()->subWeek()->endOfWeek(Carbon::SUNDAY);

        $conteosPorDia = collect(range(0, 6))->map(
            fn (int $i) => OrdenTrabajo::whereDate('fecha_ingreso', $inicioSemana->copy()->addDays($i))->count()
        );

        $totalSemana = $conteosPorDia->sum();
        $totalSemanaAnterior = OrdenTrabajo::whereBetween('fecha_ingreso', [$inicioSemanaAnterior, $finSemanaAnterior])->count();
        $diferencia = $totalSemana - $totalSemanaAnterior;

        $maxDia = max(1, $conteosPorDia->max());

        $barras = $conteosPorDia->map(fn (int $total, int $i) => [
            'label' => self::LABELS_DIA[$i],
            'total' => $total,
            'esHoy' => $inicioSemana->copy()->addDays($i)->isToday(),
            'alturaPx' => $total > 0 ? max(5, (int) round(($total / $maxDia) * self::ALTURA_MAX_PX)) : 5,
        ])->values()->all();

        return [
            'totalSemana' => $totalSemana,
            'diferencia' => $diferencia,
            'barras' => $barras,
        ];
    }
}
