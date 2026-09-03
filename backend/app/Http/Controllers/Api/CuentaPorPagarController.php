<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CuentaPorPagar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CuentaPorPagarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            CuentaPorPagar::with('proveedor:id,nombre')
                ->when($request->string('estado')->toString(), fn ($q, $e) => $q->where('estado', $e))
                ->orderBy('fecha_vencimiento')
                ->paginate(30),
        );
    }

    public function pagos(Request $request, CuentaPorPagar $cuentas_por_pagar): JsonResponse
    {
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01', 'max:'.$cuentas_por_pagar->saldo_pendiente],
        ]);

        $saldo = $cuentas_por_pagar->saldo_pendiente - $data['monto'];

        $cuentas_por_pagar->update([
            'saldo_pendiente' => $saldo,
            'estado' => $saldo <= 0 ? 'pagado' : 'pendiente',
        ]);

        return response()->json(['data' => $cuentas_por_pagar]);
    }
}
