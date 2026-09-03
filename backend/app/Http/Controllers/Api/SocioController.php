<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Socio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Socio::orderBy('nombre')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'porcentaje_default' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $socio = Socio::create([...$data, 'activo' => true]);

        return response()->json(['data' => $socio], 201);
    }
}
