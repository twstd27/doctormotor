<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdenTrabajo;
use App\Models\Presupuesto;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PresupuestoController extends Controller
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function pdf(Presupuesto $presupuesto): Response
    {
        $presupuesto->load('items', 'ordenTrabajo.cliente', 'ordenTrabajo.vehiculo');

        return Pdf::loadView('pdf.presupuesto', ['presupuesto' => $presupuesto])
            ->stream("presupuesto-{$presupuesto->ordenTrabajo->codigo}-v{$presupuesto->version}.pdf");
    }

    public function index(OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        return response()->json([
            'data' => $ordenes_trabajo->presupuestos()->with('items')->orderByDesc('version')->get(),
        ]);
    }

    public function store(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.tipo' => ['required', 'in:repuesto,mano_obra,tercerizado'],
            'items.*.producto_id' => ['nullable', 'exists:productos,id'],
            'items.*.descripcion' => ['required', 'string', 'max:255'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
        ]);

        $presupuesto = DB::transaction(function () use ($data, $ordenes_trabajo, $request) {
            $version = ($ordenes_trabajo->presupuestos()->max('version') ?? 0) + 1;
            $subtotal = collect($data['items'])->sum(fn ($item) => $item['cantidad'] * $item['precio_unitario']);
            $descuento = $data['descuento'] ?? 0;

            $presupuesto = $ordenes_trabajo->presupuestos()->create([
                'creado_por_id' => $request->user()->id,
                'version' => $version,
                'estado' => 'borrador',
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $subtotal - $descuento,
            ]);

            foreach ($data['items'] as $item) {
                $presupuesto->items()->create([
                    ...$item,
                    'subtotal' => $item['cantidad'] * $item['precio_unitario'],
                ]);
            }

            return $presupuesto;
        });

        return response()->json(['data' => $presupuesto->load('items')], 201);
    }

    public function show(Presupuesto $presupuesto): JsonResponse
    {
        return response()->json(['data' => $presupuesto->load('items', 'ordenTrabajo.vehiculo')]);
    }

    public function update(Request $request, Presupuesto $presupuesto): JsonResponse
    {
        if ($presupuesto->estado !== 'borrador') {
            return response()->json(['message' => 'Solo se puede editar un presupuesto en borrador.'], 422);
        }

        $data = $request->validate([
            'descuento' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (isset($data['descuento'])) {
            $presupuesto->update([
                'descuento' => $data['descuento'],
                'total' => $presupuesto->subtotal - $data['descuento'],
            ]);
        }

        return response()->json(['data' => $presupuesto]);
    }

    public function enviar(Presupuesto $presupuesto): JsonResponse
    {
        $presupuesto->update(['estado' => 'enviado']);
        $presupuesto->load('ordenTrabajo.cliente');

        $this->whatsApp->enviarPlantilla(
            telefono: $presupuesto->ordenTrabajo->cliente->telefono_whatsapp,
            plantilla: 'presupuesto_enviado',
            parametros: [
                'codigo_ot' => $presupuesto->ordenTrabajo->codigo,
                'total' => number_format($presupuesto->total, 2),
            ],
            userId: $presupuesto->ordenTrabajo->cliente->user_id,
            ordenTrabajoId: $presupuesto->ordenTrabajo->id,
        );

        return response()->json(['data' => $presupuesto]);
    }

    public function responderItem(Request $request, Presupuesto $presupuesto, int $item): JsonResponse
    {
        $data = $request->validate(['aprobado' => ['required', 'boolean']]);

        $presupuestoItem = $presupuesto->items()->findOrFail($item);
        $presupuestoItem->update(['aprobado' => $data['aprobado']]);

        return response()->json(['data' => $presupuestoItem]);
    }

    public function responder(Request $request, Presupuesto $presupuesto): JsonResponse
    {
        $data = $request->validate(['aprobado' => ['required', 'boolean']]);

        $presupuesto->update([
            'estado' => $data['aprobado'] ? 'aprobado' : 'rechazado',
            'respondido_at' => now(),
            'respondido_por_id' => $request->user()->id,
        ]);

        if ($data['aprobado']) {
            $presupuesto->items()->whereNull('aprobado')->update(['aprobado' => true]);
        }

        return response()->json(['data' => $presupuesto->fresh(['items', 'ordenTrabajo.vehiculo'])]);
    }

    /**
     * El técnico reporta un hallazgo/costo adicional durante el diagnóstico.
     * Se agrega como ítem `es_adicional=true` al presupuesto vigente (o crea uno nuevo).
     */
    public function adicionales(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        $data = $request->validate([
            'descripcion' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'in:repuesto,mano_obra,tercerizado'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        $presupuesto = $ordenes_trabajo->presupuestos()->latest('version')->first();

        if (! $presupuesto) {
            return response()->json(['message' => 'La OT todavía no tiene un presupuesto base.'], 422);
        }

        $item = $presupuesto->items()->create([
            ...$data,
            'subtotal' => $data['cantidad'] * $data['precio_unitario'],
            'es_adicional' => true,
            'aprobado' => null,
        ]);

        $ordenes_trabajo->update(['estado' => 'esperando_aprobacion']);

        return response()->json(['data' => $item], 201);
    }
}
