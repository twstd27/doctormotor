<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Proveedor::orderBy('nombre')->paginate(30));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $proveedor = Proveedor::create([...$data, 'activo' => true]);

        return response()->json(['data' => $proveedor], 201);
    }

    public function show(Proveedor $proveedor): JsonResponse
    {
        return response()->json(['data' => $proveedor->load('compras')]);
    }

    public function update(Request $request, Proveedor $proveedor): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:150'],
            'nit' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $proveedor->update($data);

        return response()->json(['data' => $proveedor]);
    }
}
