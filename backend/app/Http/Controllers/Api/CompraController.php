<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Compra::with('proveedor:id,nombre')->orderByDesc('fecha')->paginate(30),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'numero_factura' => ['nullable', 'string', 'max:50'],
            'fecha' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.01'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        $compra = DB::transaction(function () use ($data, $request) {
            $total = collect($data['items'])->sum(fn ($i) => $i['cantidad'] * $i['precio_unitario']);

            $compra = Compra::create([
                'proveedor_id' => $data['proveedor_id'],
                'registrado_por_id' => $request->user()->id,
                'numero_factura' => $data['numero_factura'] ?? null,
                'total' => $total,
                'estado_pago' => 'pendiente',
                'fecha' => $data['fecha'],
            ]);

            foreach ($data['items'] as $item) {
                $compra->items()->create([
                    ...$item,
                    'subtotal' => $item['cantidad'] * $item['precio_unitario'],
                ]);

                app(ProductoController::class)->registrarMovimiento(
                    productoId: $item['producto_id'],
                    tipo: 'entrada_compra',
                    cantidad: $item['cantidad'],
                    referenciaId: $compra->id,
                    referenciaTipo: 'compra',
                    userId: $request->user()->id,
                );
            }

            $compra->cuentaPorPagar()->create([
                'proveedor_id' => $data['proveedor_id'],
                'monto_original' => $total,
                'saldo_pendiente' => $total,
                'estado' => 'pendiente',
            ]);

            return $compra;
        });

        return response()->json(['data' => $compra->load('items')], 201);
    }

    public function show(Compra $compra): JsonResponse
    {
        return response()->json(['data' => $compra->load('items.producto', 'proveedor')]);
    }
}
