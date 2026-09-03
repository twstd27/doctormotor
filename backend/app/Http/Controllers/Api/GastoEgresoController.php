<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GastoEgreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GastoEgresoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $gastos = GastoEgreso::query()
            ->when($request->string('categoria')->toString(), fn ($q, $c) => $q->where('categoria', $c))
            ->when($request->date('desde'), fn ($q, $d) => $q->whereDate('fecha', '>=', $d))
            ->when($request->date('hasta'), fn ($q, $d) => $q->whereDate('fecha', '<=', $d))
            ->orderByDesc('fecha')
            ->paginate(30);

        return response()->json($gastos);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'categoria' => ['required', 'in:fijo,variable'],
            'concepto' => ['required', 'string', 'max:150'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'comprobante_url' => ['nullable', 'string', 'max:255'],
            'fecha' => ['required', 'date'],
        ]);

        $gasto = GastoEgreso::create([
            ...$data,
            'registrado_por_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $gasto], 201);
    }

    public function update(Request $request, GastoEgreso $gasto_egreso): JsonResponse
    {
        $data = $request->validate([
            'categoria' => ['sometimes', 'in:fijo,variable'],
            'concepto' => ['sometimes', 'string', 'max:150'],
            'monto' => ['sometimes', 'numeric', 'min:0.01'],
            'comprobante_url' => ['nullable', 'string', 'max:255'],
            'fecha' => ['sometimes', 'date'],
        ]);

        $gasto_egreso->update($data);

        return response()->json(['data' => $gasto_egreso]);
    }

    public function destroy(GastoEgreso $gasto_egreso): JsonResponse
    {
        $gasto_egreso->delete();

        return response()->json(null, 204);
    }
}
