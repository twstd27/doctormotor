<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CajaCierre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function actual(Request $request): JsonResponse
    {
        $caja = CajaCierre::where('cajero_id', $request->user()->id)
            ->where('estado', 'abierta')
            ->latest('fecha')
            ->first();

        return response()->json(['data' => $caja]);
    }

    public function apertura(Request $request): JsonResponse
    {
        $data = $request->validate([
            'monto_apertura' => ['required', 'numeric', 'min:0'],
        ]);

        $existente = CajaCierre::where('cajero_id', $request->user()->id)
            ->where('estado', 'abierta')
            ->exists();

        if ($existente) {
            return response()->json(['message' => 'Ya tenés una caja abierta.'], 422);
        }

        $caja = CajaCierre::create([
            'cajero_id' => $request->user()->id,
            'fecha' => now()->toDateString(),
            'monto_apertura' => $data['monto_apertura'],
            'estado' => 'abierta',
        ]);

        return response()->json(['data' => $caja], 201);
    }

    public function cierre(Request $request, CajaCierre $caja): JsonResponse
    {
        $data = $request->validate([
            'monto_contado' => ['required', 'numeric', 'min:0'],
        ]);

        $montoEsperado = $caja->monto_apertura + $caja->pagos()->where('metodo', 'efectivo')->sum('monto');

        $caja->update([
            'monto_esperado' => $montoEsperado,
            'monto_contado' => $data['monto_contado'],
            'diferencia' => $data['monto_contado'] - $montoEsperado,
            'estado' => 'cerrada',
            'cerrado_at' => now(),
        ]);

        return response()->json(['data' => $caja]);
    }

    public function cierres(): JsonResponse
    {
        return response()->json(
            CajaCierre::with('cajero:id,nombre')->orderByDesc('fecha')->paginate(30),
        );
    }
}
