<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Models\OrdenTrabajo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CostoDirectoController extends Controller
{
    public function store(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        if ($ordenes_trabajo->estaCerrada()) {
            return response()->json(['message' => 'Esta OT ya está entregada o cancelada, no se le pueden agregar más costos.'], 422);
        }

        $data = $request->validate([
            'tipo' => ['required', 'in:repuesto,mano_obra,tercerizado'],
            'producto_id' => ['nullable', 'exists:productos,id'],
            'tecnico_id' => ['nullable', 'exists:users,id'],
            'descripcion' => ['required', 'string', 'max:255'],
            'cantidad' => ['nullable', 'numeric', 'min:0.01'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        $cantidad = $data['cantidad'] ?? 1;

        if ($data['tipo'] === 'repuesto' && ! empty($data['producto_id'])) {
            try {
                app(ProductoController::class)->registrarMovimiento(
                    productoId: $data['producto_id'],
                    tipo: 'salida_ot',
                    cantidad: -$cantidad,
                    referenciaId: $ordenes_trabajo->id,
                    referenciaTipo: 'orden_trabajo',
                    userId: $request->user()->id,
                );
            } catch (StockInsuficienteException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        $costo = $ordenes_trabajo->costosDirectos()->create([
            ...$data,
            'cantidad' => $cantidad,
            'costo_total' => $cantidad * $data['costo_unitario'],
        ]);

        return response()->json(['data' => $costo], 201);
    }

    /**
     * Margen neto de la OT: ingresos (pagos recibidos) vs. costos directos imputados.
     */
    public function margen(OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        $ingresos = $ordenes_trabajo->pagos()->sum('monto');
        $costos = $ordenes_trabajo->costosDirectos()->sum('costo_total');

        return response()->json([
            'data' => [
                'orden_trabajo_id' => $ordenes_trabajo->id,
                'ingresos' => (float) $ingresos,
                'costos_directos' => (float) $costos,
                'margen_neto' => (float) $ingresos - (float) $costos,
            ],
        ]);
    }
}
