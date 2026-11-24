<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServicioFrecuente;
use Illuminate\Http\JsonResponse;

class ServicioFrecuenteController extends Controller
{
    /** Servicios activos para los atajos de «Agregar ítem» en la app del técnico y del cajero. */
    public function index(): JsonResponse
    {
        $servicios = ServicioFrecuente::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo', 'descripcion', 'precio_unitario']);

        return response()->json(['data' => $servicios]);
    }
}
