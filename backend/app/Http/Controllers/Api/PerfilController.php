<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->load('cliente')]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:150'],
            'correo' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono_whatsapp' => ['sometimes', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $user->update(array_intersect_key($data, array_flip(['nombre', 'telefono_whatsapp'])));

        if ($user->cliente) {
            $user->cliente->update(array_intersect_key($data, array_flip(['correo', 'direccion', 'telefono_whatsapp'])));
        }

        return response()->json(['data' => $user->fresh()->load('cliente')]);
    }
}
