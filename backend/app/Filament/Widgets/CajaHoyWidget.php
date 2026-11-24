<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\CajaDelDia;
use App\Models\CajaCierre;
use App\Models\GastoEgreso;
use App\Models\Pago;
use Filament\Widgets\Widget;

/**
 * "Caja de hoy". El cajero ve el turno que tiene abierto; el super_admin, el movimiento de
 * todo el taller en el día.
 */
class CajaHoyWidget extends Widget
{
    protected string $view = 'filament.widgets.caja-hoy';

    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        $usuario = auth()->user();
        $esAdmin = $usuario?->rol === 'super_admin';
        $caja = CajaCierre::abiertaDe((int) $usuario?->id);

        if ($esAdmin) {
            $pagos = Pago::query()->whereDate('fecha', today());
            $egresos = (float) GastoEgreso::query()->whereDate('fecha', today())->sum('monto');
        } elseif ($caja) {
            $pagos = $caja->pagos();
            $egresos = $caja->gastosEnEfectivo();
        } else {
            return ['modo' => 'cerrada', 'url' => CajaDelDia::getUrl()];
        }

        $porMetodo = [
            'Efectivo' => (float) (clone $pagos)->where('metodo', 'efectivo')->sum('monto'),
            'QR' => (float) (clone $pagos)->where('metodo', 'qr')->sum('monto'),
            'Tarjeta' => (float) (clone $pagos)->where('metodo', 'tarjeta')->sum('monto'),
        ];

        return [
            'modo' => $esAdmin ? 'taller' : 'turno',
            'abierta' => (bool) $caja,
            'neto' => array_sum($porMetodo) - $egresos,
            'porMetodo' => $porMetodo,
            'egresos' => $egresos,
            'url' => CajaDelDia::getUrl(),
        ];
    }
}
