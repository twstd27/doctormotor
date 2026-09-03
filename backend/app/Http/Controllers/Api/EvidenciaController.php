<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evidencia;
use App\Models\OrdenTrabajo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenciaController extends Controller
{
    public function index(OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        return response()->json(['data' => $ordenes_trabajo->evidencias()->latest('tomada_at')->get()]);
    }

    public function store(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        $evidencia = $this->guardarEvidencia($request, $ordenes_trabajo->id);

        return response()->json(['data' => $evidencia], 201);
    }

    /**
     * Sincroniza un lote de evidencias tomadas offline. Upsert por uuid_cliente para
     * que reintentar un lote parcialmente enviado no duplique archivos.
     */
    public function syncBatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'evidencias' => ['required', 'array'],
            'evidencias.*.uuid_cliente' => ['required', 'uuid'],
            'evidencias.*.orden_trabajo_id' => ['required', 'exists:ordenes_trabajo,id'],
            'evidencias.*.tipo' => ['required', 'in:foto,video'],
            'evidencias.*.url' => ['required', 'string'],
            'evidencias.*.etiqueta' => ['nullable', 'string', 'max:100'],
            'evidencias.*.tomada_at' => ['required', 'date'],
        ]);

        $resultado = collect($data['evidencias'])->map(function (array $item) use ($request) {
            return Evidencia::updateOrCreate(
                ['uuid_cliente' => $item['uuid_cliente']],
                [
                    'orden_trabajo_id' => $item['orden_trabajo_id'],
                    'subido_por_id' => $request->user()->id,
                    'tipo' => $item['tipo'],
                    'url' => $item['url'],
                    'etiqueta' => $item['etiqueta'] ?? null,
                    'tomada_at' => $item['tomada_at'],
                ],
            );
        });

        return response()->json(['data' => $resultado]);
    }

    public function destroy(Evidencia $evidencia): JsonResponse
    {
        $evidencia->delete();

        return response()->json(null, 204);
    }

    private function guardarEvidencia(Request $request, int $ordenTrabajoId): Evidencia
    {
        $data = $request->validate([
            'uuid_cliente' => ['required', 'uuid'],
            'tipo' => ['required', 'in:foto,video'],
            'archivo' => ['required', 'file', 'max:51200'],
            'etiqueta' => ['nullable', 'string', 'max:100'],
            'tomada_at' => ['nullable', 'date'],
        ]);

        $ruta = $request->file('archivo')->store('evidencias', 'public');

        return Evidencia::updateOrCreate(
            ['uuid_cliente' => $data['uuid_cliente']],
            [
                'orden_trabajo_id' => $ordenTrabajoId,
                'subido_por_id' => $request->user()->id,
                'tipo' => $data['tipo'],
                'url' => Storage::disk('public')->url($ruta),
                'etiqueta' => $data['etiqueta'] ?? null,
                'tomada_at' => $data['tomada_at'] ?? now(),
            ],
        );
    }
}
