<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GastoEgreso;
use App\Models\OrdenTrabajo;
use App\Models\Pago;
use App\Models\ReglaReparto;
use App\Models\RepartoUtilidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepartoUtilidadController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => RepartoUtilidad::orderByDesc('periodo_inicio')->get()]);
    }

    public function show(RepartoUtilidad $reparto_utilidad): JsonResponse
    {
        return response()->json(['data' => $reparto_utilidad->load('detalle.socio:id,nombre')]);
    }

    public function generar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'periodo_inicio' => ['required', 'date'],
            'periodo_fin' => ['required', 'date', 'after_or_equal:periodo_inicio'],
        ]);

        $reglas = ReglaReparto::whereNull('vigente_hasta')->get();
        if ($reglas->isEmpty()) {
            return response()->json(['message' => 'No hay reglas de reparto configuradas.'], 422);
        }

        $ingresos = Pago::whereBetween('fecha', [$data['periodo_inicio'], $data['periodo_fin']])->sum('monto');
        $costosDirectos = OrdenTrabajo::query()
            ->join('costos_directos', 'costos_directos.orden_trabajo_id', '=', 'ordenes_trabajo.id')
            ->whereBetween('costos_directos.created_at', [$data['periodo_inicio'], $data['periodo_fin']])
            ->sum('costos_directos.costo_total');
        $gastos = GastoEgreso::whereBetween('fecha', [$data['periodo_inicio'], $data['periodo_fin']])->sum('monto');

        $utilidadNeta = $ingresos - $costosDirectos - $gastos;

        $reparto = DB::transaction(function () use ($data, $ingresos, $costosDirectos, $gastos, $utilidadNeta, $reglas, $request) {
            $reparto = RepartoUtilidad::create([
                'periodo_inicio' => $data['periodo_inicio'],
                'periodo_fin' => $data['periodo_fin'],
                'ingresos_total' => $ingresos,
                'costos_directos_total' => $costosDirectos,
                'gastos_total' => $gastos,
                'utilidad_neta' => $utilidadNeta,
                'generado_por_id' => $request->user()->id,
                'generado_at' => now(),
            ]);

            foreach ($reglas as $regla) {
                $reparto->detalle()->create([
                    'socio_id' => $regla->socio_id,
                    'porcentaje_aplicado' => $regla->porcentaje,
                    'monto' => $utilidadNeta * ($regla->porcentaje / 100),
                ]);
            }

            return $reparto;
        });

        return response()->json(['data' => $reparto->load('detalle.socio:id,nombre')], 201);
    }
}
