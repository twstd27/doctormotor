<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GastoEgreso;
use App\Models\OrdenTrabajo;
use App\Models\Pago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $hoy = now()->toDateString();
        $inicioMes = now()->startOfMonth()->toDateString();

        return response()->json([
            'data' => [
                'facturado_hoy' => (float) Pago::whereDate('fecha', $hoy)->sum('monto'),
                'facturado_mes' => (float) Pago::whereDate('fecha', '>=', $inicioMes)->sum('monto'),
                'gastos_mes' => (float) GastoEgreso::whereDate('fecha', '>=', $inicioMes)->sum('monto'),
                'ots_activas' => OrdenTrabajo::whereNotIn('estado', ['entregado', 'cancelado'])->count(),
                'ots_esperando_aprobacion' => OrdenTrabajo::where('estado', 'esperando_aprobacion')->count(),
            ],
        ]);
    }

    public function ingresosEgresos(Request $request): JsonResponse
    {
        $desde = $request->date('desde') ?? now()->subDays(7);
        $hasta = $request->date('hasta') ?? now();

        $ingresos = Pago::selectRaw('DATE(fecha) as fecha, SUM(monto) as total')
            ->whereBetween('fecha', [$desde, $hasta])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $egresos = GastoEgreso::selectRaw('fecha, SUM(monto) as total')
            ->whereBetween('fecha', [$desde, $hasta])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        return response()->json(['data' => ['ingresos' => $ingresos, 'egresos' => $egresos]]);
    }

    public function rentabilidadPorOt(Request $request): JsonResponse
    {
        $desde = $request->date('desde') ?? now()->subDays(30);
        $hasta = $request->date('hasta') ?? now();

        $ordenes = OrdenTrabajo::query()
            ->whereBetween('fecha_ingreso', [$desde, $hasta])
            ->withSum('pagos as ingresos', 'monto')
            ->withSum('costosDirectos as costos', 'costo_total')
            ->get()
            ->map(fn ($ot) => [
                'id' => $ot->id,
                'codigo' => $ot->codigo,
                'ingresos' => (float) ($ot->ingresos ?? 0),
                'costos' => (float) ($ot->costos ?? 0),
                'margen' => (float) ($ot->ingresos ?? 0) - (float) ($ot->costos ?? 0),
            ]);

        return response()->json(['data' => $ordenes]);
    }
}
