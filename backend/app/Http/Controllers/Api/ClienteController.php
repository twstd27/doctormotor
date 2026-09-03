<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function index(Request $request): JsonResponse
    {
        $clientes = Cliente::query()
            ->when($request->string('buscar')->toString(), function ($query, $buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'ilike', "%{$buscar}%")
                        ->orWhere('ci_nit', 'ilike', "%{$buscar}%")
                        ->orWhere('telefono_whatsapp', 'ilike', "%{$buscar}%");
                });
            })
            ->withCount('vehiculos')
            ->orderBy('nombre')
            ->paginate(20);

        return response()->json($clientes);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'ci_nit' => ['required', 'string', 'max:20'],
            'telefono_whatsapp' => ['required', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string'],
        ]);

        $cliente = Cliente::create($data);

        return response()->json(['data' => $cliente], 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json(['data' => $cliente->load('vehiculos')]);
    }

    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:150'],
            'ci_nit' => ['sometimes', 'string', 'max:20'],
            'telefono_whatsapp' => ['sometimes', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string'],
        ]);

        $cliente->update($data);

        return response()->json(['data' => $cliente]);
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $cliente->delete();

        return response()->json(null, 204);
    }

    /**
     * Envía un enlace de creación de cuenta por WhatsApp.
     */
    public function invitar(Cliente $cliente): JsonResponse
    {
        $this->whatsApp->enviarPlantilla(
            telefono: $cliente->telefono_whatsapp,
            plantilla: 'invitacion_cuenta',
            parametros: ['nombre' => $cliente->nombre],
        );

        return response()->json(['message' => 'Invitación enviada por WhatsApp.']);
    }

    public function vehiculos(Cliente $cliente): JsonResponse
    {
        return response()->json(['data' => $cliente->vehiculos]);
    }

    public function storeVehiculo(Request $request, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'placa' => ['required', 'string', 'max:15'],
            'marca' => ['required', 'string', 'max:50'],
            'modelo' => ['required', 'string', 'max:50'],
            'anio' => ['required', 'integer', 'min:1950', 'max:2100'],
            'color' => ['required', 'string', 'max:30'],
            'motor' => ['nullable', 'string', 'max:50'],
            'kilometraje_actual' => ['required', 'integer', 'min:0'],
        ]);

        $vehiculo = $cliente->vehiculos()->create($data);

        return response()->json(['data' => $vehiculo], 201);
    }
}
