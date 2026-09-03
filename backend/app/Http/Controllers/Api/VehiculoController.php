<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VehiculoController extends Controller
{
    public function show(Vehiculo $vehiculo): JsonResponse
    {
        return response()->json(['data' => $vehiculo->load('cliente')]);
    }

    public function update(Request $request, Vehiculo $vehiculo): JsonResponse
    {
        $data = $request->validate([
            'placa' => ['sometimes', 'string', 'max:15'],
            'marca' => ['sometimes', 'string', 'max:50'],
            'modelo' => ['sometimes', 'string', 'max:50'],
            'anio' => ['sometimes', 'integer', 'min:1950', 'max:2100'],
            'color' => ['sometimes', 'string', 'max:30'],
            'motor' => ['nullable', 'string', 'max:50'],
            'kilometraje_actual' => ['sometimes', 'integer', 'min:0'],
        ]);

        $vehiculo->update($data);

        return response()->json(['data' => $vehiculo]);
    }

    public function destroy(Vehiculo $vehiculo): JsonResponse
    {
        $vehiculo->delete();

        return response()->json(null, 204);
    }

    /**
     * Historial clínico automotriz (todas las OT del vehículo).
     */
    public function historial(Vehiculo $vehiculo): JsonResponse
    {
        $ordenes = $vehiculo->ordenesTrabajo()
            ->with(['tecnicoAsignado:id,nombre', 'presupuestos'])
            ->orderByDesc('fecha_ingreso')
            ->get();

        return response()->json(['data' => $ordenes]);
    }

    public function historialPdf(Vehiculo $vehiculo): Response
    {
        $vehiculo->load('cliente');
        $ordenes = $vehiculo->ordenesTrabajo()->orderByDesc('fecha_ingreso')->get();

        return Pdf::loadView('pdf.historial-clinico', ['vehiculo' => $vehiculo, 'ordenes' => $ordenes])
            ->stream("historial-{$vehiculo->placa}.pdf");
    }

    /**
     * "Mi garaje" — vehículos del cliente autenticado.
     */
    public function mios(Request $request): JsonResponse
    {
        $cliente = $request->user()->cliente;

        if (! $cliente) {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => $cliente->vehiculos]);
    }
}
