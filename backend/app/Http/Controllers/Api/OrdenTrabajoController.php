<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdenTrabajo;
use App\Services\OrdenTrabajoEstadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenTrabajoController extends Controller
{
    public function __construct(private OrdenTrabajoEstadoService $estadoService) {}

    public function index(Request $request): JsonResponse
    {
        $ordenes = OrdenTrabajo::query()
            ->when($request->string('estado')->toString(), fn ($q, $estado) => $q->where('estado', $estado))
            ->when($request->integer('tecnico_asignado_id'), fn ($q, $id) => $q->where('tecnico_asignado_id', $id))
            ->with(['vehiculo:id,placa,marca,modelo', 'cliente:id,nombre', 'tecnicoAsignado:id,nombre'])
            ->orderByDesc('fecha_ingreso')
            ->paginate(min($request->integer('per_page', 30), 100));

        return response()->json($ordenes);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehiculo_id' => ['required', 'exists:vehiculos,id'],
            'cliente_id' => ['required', 'exists:clientes,id'],
            'tecnico_asignado_id' => ['nullable', 'exists:users,id'],
            'descripcion_problema' => ['required', 'string'],
            'kilometraje_ingreso' => ['required', 'integer', 'min:0'],
            'nivel_gasolina' => ['required', 'in:E,1/4,1/2,3/4,F'],
            'fecha_entrega_estimada' => ['nullable', 'date'],
        ]);

        $orden = DB::transaction(function () use ($data, $request) {
            $data['codigo'] = OrdenTrabajo::generarCodigo();
            $data['recibido_por_id'] = $request->user()->id;
            $data['estado'] = 'recepcionado';
            $data['fecha_ingreso'] = now();

            return OrdenTrabajo::create($data);
        });

        return response()->json(['data' => $orden], 201);
    }

    public function show(OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        $ordenes_trabajo->load([
            'vehiculo', 'cliente', 'recibidoPor:id,nombre', 'tecnicoAsignado:id,nombre',
            'inspeccion', 'evidencias', 'presupuestos.items', 'estadosHistorial.user:id,nombre',
        ]);

        return response()->json(['data' => $ordenes_trabajo]);
    }

    public function update(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        if ($ordenes_trabajo->estaCerrada()) {
            return response()->json(['message' => 'Esta OT ya está entregada o cancelada, no se puede editar.'], 422);
        }

        $data = $request->validate([
            'descripcion_problema' => ['sometimes', 'string'],
            'kilometraje_ingreso' => ['sometimes', 'integer', 'min:0'],
            'nivel_gasolina' => ['sometimes', 'in:E,1/4,1/2,3/4,F'],
            'fecha_entrega_estimada' => ['nullable', 'date'],
        ]);

        $ordenes_trabajo->update($data);

        return response()->json(['data' => $ordenes_trabajo]);
    }

    /**
     * Cambia el estado de la OT (drag & drop del Kanban) y dispara el evento en tiempo real.
     * Una vez entregada o cancelada, la OT queda cerrada — ya no se puede mover a otro estado.
     */
    public function cambiarEstado(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        if ($ordenes_trabajo->estaCerrada()) {
            return response()->json(['message' => 'Esta OT ya está entregada o cancelada, no se puede cambiar su estado.'], 422);
        }

        $data = $request->validate([
            'estado' => ['required', 'in:'.implode(',', OrdenTrabajo::ESTADOS)],
            'comentario' => ['nullable', 'string'],
        ]);

        $ordenes_trabajo = $this->estadoService->cambiarA(
            $ordenes_trabajo,
            $data['estado'],
            $request->user()->id,
            $data['comentario'] ?? null,
        );

        return response()->json(['data' => $ordenes_trabajo]);
    }

    public function asignarTecnico(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        if ($ordenes_trabajo->estaCerrada()) {
            return response()->json(['message' => 'Esta OT ya está entregada o cancelada, no se puede reasignar técnico.'], 422);
        }

        $data = $request->validate([
            'tecnico_asignado_id' => ['required', 'exists:users,id'],
        ]);

        $ordenes_trabajo->update($data);
        broadcast(new OrdenTrabajoActualizada($ordenes_trabajo->fresh()));

        return response()->json(['data' => $ordenes_trabajo]);
    }

    public function historialEstados(OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        return response()->json(['data' => $ordenes_trabajo->estadosHistorial()->with('user:id,nombre')->get()]);
    }

    public function mias(Request $request): JsonResponse
    {
        $ordenes = OrdenTrabajo::query()
            ->where('tecnico_asignado_id', $request->user()->id)
            ->whereNotIn('estado', ['entregado', 'cancelado'])
            ->with(['vehiculo:id,placa,marca,modelo', 'cliente:id,nombre'])
            ->orderBy('fecha_ingreso')
            ->get();

        return response()->json(['data' => $ordenes]);
    }

    public function misOrdenesCliente(Request $request): JsonResponse
    {
        $cliente = $request->user()->cliente;

        if (! $cliente) {
            return response()->json(['data' => []]);
        }

        $ordenes = $cliente->ordenesTrabajo()
            ->with([
                'vehiculo:id,placa,marca,modelo',
                'tecnicoAsignado:id,nombre',
                'presupuestos' => fn ($q) => $q->latest('version')->limit(1),
            ])
            ->orderByDesc('fecha_ingreso')
            ->get();

        return response()->json(['data' => $ordenes]);
    }

}
