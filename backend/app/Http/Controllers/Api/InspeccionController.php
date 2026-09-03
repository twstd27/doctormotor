<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdenTrabajo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InspeccionController extends Controller
{
    public function store(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        $data = $request->validate([
            'accesorios' => ['nullable', 'array'],
            'rayones_previos' => ['nullable', 'array'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $inspeccion = $ordenes_trabajo->inspeccion()->updateOrCreate([], $data);

        return response()->json(['data' => $inspeccion], 201);
    }

    public function update(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        $data = $request->validate([
            'accesorios' => ['nullable', 'array'],
            'rayones_previos' => ['nullable', 'array'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $inspeccion = $ordenes_trabajo->inspeccion;

        if (! $inspeccion) {
            return response()->json(['message' => 'La OT todavía no tiene inspección registrada.'], 404);
        }

        $inspeccion->update($data);

        return response()->json(['data' => $inspeccion]);
    }

    /**
     * Sube la firma digital del cliente (imagen en base64) y cierra la inspección.
     */
    public function firma(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        $data = $request->validate([
            'firma_base64' => ['required', 'string'],
        ]);

        $inspeccion = $ordenes_trabajo->inspeccion;

        if (! $inspeccion) {
            return response()->json(['message' => 'La OT todavía no tiene inspección registrada.'], 404);
        }

        // Fase 1: se guarda en el disco local. El cambio a DigitalOcean Spaces (S3) es
        // configuración de FILESYSTEM_DISK en producción, sin tocar este controlador.
        [, $contenido] = explode(',', $data['firma_base64'], 2) + [null, $data['firma_base64']];
        $nombreArchivo = "firmas/ot-{$ordenes_trabajo->id}-".now()->timestamp.'.png';
        \Illuminate\Support\Facades\Storage::disk('public')->put($nombreArchivo, base64_decode($contenido));

        $inspeccion->update([
            'firma_cliente_url' => \Illuminate\Support\Facades\Storage::disk('public')->url($nombreArchivo),
            'firmado_at' => now(),
        ]);

        return response()->json(['data' => $inspeccion]);
    }
}
