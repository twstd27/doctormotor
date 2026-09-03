<?php

namespace App\Http\Controllers\Api;

use App\Events\OrdenTrabajoActualizada;
use App\Http\Controllers\Controller;
use App\Models\OrdenTrabajo;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenTrabajoController extends Controller
{
    public function __construct(private WhatsAppService $whatsApp) {}

    /**
     * Plantilla de WhatsApp por cada estado del Kanban — solo se notifican los que
     * realmente le importan al cliente, no cada micro-transición interna.
     */
    private const PLANTILLAS_POR_ESTADO = [
        'en_diagnostico' => 'ot_en_diagnostico',
        'esperando_aprobacion' => 'ot_esperando_aprobacion',
        'en_reparacion' => 'ot_en_reparacion',
        'listo_entrega' => 'ot_lista_entrega',
    ];
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
            $data['codigo'] = $this->generarCodigo();
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
     */
    public function cambiarEstado(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
        $data = $request->validate([
            'estado' => ['required', 'in:'.implode(',', OrdenTrabajo::ESTADOS)],
            'comentario' => ['nullable', 'string'],
        ]);

        $estadoAnterior = $ordenes_trabajo->estado;

        DB::transaction(function () use ($ordenes_trabajo, $data, $request, $estadoAnterior) {
            $ordenes_trabajo->update([
                'estado' => $data['estado'],
                'fecha_entrega_real' => $data['estado'] === 'entregado' ? now() : $ordenes_trabajo->fecha_entrega_real,
            ]);

            $ordenes_trabajo->estadosHistorial()->create([
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $data['estado'],
                'user_id' => $request->user()->id,
                'comentario' => $data['comentario'] ?? null,
            ]);
        });

        $ordenes_trabajo = $ordenes_trabajo->fresh();
        broadcast(new OrdenTrabajoActualizada($ordenes_trabajo));
        $this->notificarCambioEstado($ordenes_trabajo);

        return response()->json(['data' => $ordenes_trabajo]);
    }

    public function asignarTecnico(Request $request, OrdenTrabajo $ordenes_trabajo): JsonResponse
    {
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

    private function notificarCambioEstado(OrdenTrabajo $ordenTrabajo): void
    {
        $plantilla = self::PLANTILLAS_POR_ESTADO[$ordenTrabajo->estado] ?? null;
        if (! $plantilla) {
            return;
        }

        $ordenTrabajo->loadMissing('cliente', 'vehiculo');

        $this->whatsApp->enviarPlantilla(
            telefono: $ordenTrabajo->cliente->telefono_whatsapp,
            plantilla: $plantilla,
            parametros: [
                'vehiculo' => "{$ordenTrabajo->vehiculo->marca} {$ordenTrabajo->vehiculo->modelo}",
                'codigo_ot' => $ordenTrabajo->codigo,
            ],
            userId: $ordenTrabajo->cliente->user_id,
            ordenTrabajoId: $ordenTrabajo->id,
        );
    }

    private function generarCodigo(): string
    {
        $anio = now()->year;
        $ultimo = OrdenTrabajo::withTrashed()
            ->where('codigo', 'like', "OT-{$anio}-%")
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('codigo');

        $siguiente = $ultimo ? ((int) substr($ultimo, -4)) + 1 : 1;

        return sprintf('OT-%d-%04d', $anio, $siguiente);
    }
}
