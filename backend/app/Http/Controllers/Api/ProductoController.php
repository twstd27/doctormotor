<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $productos = Producto::query()
            ->when($request->boolean('stock_bajo'), fn ($q) => $q->whereColumn('stock_actual', '<=', 'stock_minimo'))
            ->when($request->string('buscar')->toString(), fn ($q, $b) => $q->where('nombre', 'ilike', "%{$b}%"))
            ->orderBy('nombre')
            ->paginate(30);

        return response()->json($productos);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:productos,sku'],
            'nombre' => ['required', 'string', 'max:150'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'unidad_medida' => ['required', 'string', 'max:20'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'precio_compra_promedio' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
        ]);

        $producto = Producto::create($data);

        return response()->json(['data' => $producto], 201);
    }

    public function show(Producto $producto): JsonResponse
    {
        return response()->json([
            'data' => $producto->load(['movimientos' => fn ($q) => $q->latest('fecha')->limit(20)]),
        ]);
    }

    public function update(Request $request, Producto $producto): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:150'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'unidad_medida' => ['sometimes', 'string', 'max:20'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'precio_compra_promedio' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $producto->update($data);

        return response()->json(['data' => $producto]);
    }

    public function movimientos(Producto $producto): JsonResponse
    {
        return response()->json(['data' => $producto->movimientos()->orderByDesc('fecha')->paginate(30)]);
    }

    public function ajuste(Request $request, Producto $producto): JsonResponse
    {
        $data = $request->validate([
            'cantidad' => ['required', 'numeric'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $this->registrarMovimiento(
            productoId: $producto->id,
            tipo: 'ajuste',
            cantidad: $data['cantidad'],
            referenciaId: null,
            referenciaTipo: null,
            userId: $request->user()->id,
        );

        return response()->json(['data' => $producto->fresh()]);
    }

    public function alertasStock(): JsonResponse
    {
        $productos = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json(['data' => $productos]);
    }

    /**
     * Registra un movimiento de kardex y actualiza el stock_actual del producto.
     * Usado tanto por ajustes manuales como por otros módulos (compras, costos directos).
     */
    public function registrarMovimiento(
        int $productoId,
        string $tipo,
        float $cantidad,
        ?int $referenciaId,
        ?string $referenciaTipo,
        int $userId,
    ): MovimientoInventario {
        return DB::transaction(function () use ($productoId, $tipo, $cantidad, $referenciaId, $referenciaTipo, $userId) {
            $movimiento = MovimientoInventario::create([
                'producto_id' => $productoId,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'referencia_id' => $referenciaId,
                'referencia_tipo' => $referenciaTipo,
                'user_id' => $userId,
                'fecha' => now(),
            ]);

            Producto::where('id', $productoId)->increment('stock_actual', $cantidad);

            return $movimiento;
        });
    }
}
