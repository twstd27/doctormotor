<?php

namespace App\Filament\Resources\Compras\Pages;

use App\Filament\Resources\Compras\CompraResource;
use App\Http\Controllers\Api\ProductoController;
use App\Models\Compra;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateCompra extends CreateRecord
{
    protected static string $resource = CompraResource::class;

    /**
     * Réplica exacta de CompraController::store(): la compra, sus ítems, el movimiento de
     * kardex de cada ítem (entrada_compra) y la cuenta por pagar se crean juntos y atómicos.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        return DB::transaction(function () use ($data, $items) {
            $total = collect($items)->sum(fn (array $item) => (float) $item['cantidad'] * (float) $item['precio_unitario']);

            $compra = Compra::create([
                ...$data,
                'registrado_por_id' => auth()->id(),
                'total' => $total,
                'estado_pago' => 'pendiente',
            ]);

            foreach ($items as $item) {
                $compra->items()->create([
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => (float) $item['cantidad'] * (float) $item['precio_unitario'],
                ]);

                app(ProductoController::class)->registrarMovimiento(
                    productoId: $item['producto_id'],
                    tipo: 'entrada_compra',
                    cantidad: (float) $item['cantidad'],
                    referenciaId: $compra->id,
                    referenciaTipo: 'compra',
                    userId: auth()->id(),
                );
            }

            $compra->cuentaPorPagar()->create([
                'proveedor_id' => $compra->proveedor_id,
                'monto_original' => $total,
                'saldo_pendiente' => $total,
                'estado' => 'pendiente',
            ]);

            return $compra;
        });
    }
}
