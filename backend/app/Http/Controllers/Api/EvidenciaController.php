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
        return response()->json([
            'data' => $ordenes_trabajo->evidencias()->with('subidoPor:id,nombre')->latest('tomada_at')->get(),
        ]);
    }

    public function store(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        if ($ordenes_trabajo->estaCerrada()) {
            return response()->json(['message' => 'Esta OT ya está entregada o cancelada, no se le pueden agregar más evidencias.'], 422);
        }

        $evidencia = $this->guardarEvidencia($request, $ordenes_trabajo->id);

        return response()->json(['data' => $evidencia], 201);
    }

    /**
     * Sincroniza un lote de evidencias tomadas offline. Upsert por uuid_cliente para
     * que reintentar un lote parcialmente enviado no duplique archivos. Los ítems cuya OT
     * ya cerró entre que se tomó la foto y que volvió la señal se descartan (se reportan
     * aparte) en vez de fallar todo el lote.
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

        $otIds = collect($data['evidencias'])->pluck('orden_trabajo_id')->unique();
        $otsCerradas = OrdenTrabajo::whereIn('id', $otIds)->get()->filter(fn ($ot) => $ot->estaCerrada())->pluck('id')->all();

        $rechazados = [];
        $resultado = collect($data['evidencias'])
            ->reject(function (array $item) use ($otsCerradas, &$rechazados) {
                $cerrada = in_array($item['orden_trabajo_id'], $otsCerradas, true);
                if ($cerrada) {
                    $rechazados[] = $item['uuid_cliente'];
                }

                return $cerrada;
            })
            ->map(function (array $item) use ($request) {
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

        return response()->json(['data' => $resultado, 'rechazados' => $rechazados]);
    }

    public function destroy(Evidencia $evidencia): JsonResponse
    {
        if ($evidencia->ordenTrabajo->estaCerrada()) {
            return response()->json(['message' => 'Esta OT ya está entregada o cancelada, no se pueden eliminar sus evidencias.'], 422);
        }

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
