<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReglaReparto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReglaRepartoController extends Controller
{
    public function index(): JsonResponse
    {
        $reglas = ReglaReparto::with('socio:id,nombre')
            ->whereNull('vigente_hasta')
            ->orderBy('socio_id')
            ->get();

        return response()->json(['data' => $reglas]);
    }

    /**
     * Reemplaza las reglas vigentes: cierra las anteriores (vigente_hasta = ayer) y
     * crea las nuevas. Valida que los porcentajes sumen 100.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reglas' => ['required', 'array', 'min:1'],
            'reglas.*.socio_id' => ['required', 'exists:socios,id'],
            'reglas.*.porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $suma = collect($data['reglas'])->sum('porcentaje');
        if (round($suma, 2) !== 100.0) {
            return response()->json(['message' => "Los porcentajes deben sumar 100 (suman {$suma})."], 422);
        }

        $reglas = DB::transaction(function () use ($data) {
            ReglaReparto::whereNull('vigente_hasta')->update(['vigente_hasta' => now()->subDay()]);

            return collect($data['reglas'])->map(fn ($r) => ReglaReparto::create([
                'socio_id' => $r['socio_id'],
                'porcentaje' => $r['porcentaje'],
                'vigente_desde' => now()->toDateString(),
            ]));
        });

        return response()->json(['data' => $reglas]);
    }
}
