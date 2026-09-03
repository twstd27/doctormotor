<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sku', 'nombre', 'categoria', 'unidad_medida', 'stock_actual', 'stock_minimo',
    'precio_compra_promedio', 'precio_venta', 'activo',
])]
class Producto extends Model
{
    protected function casts(): array
    {
        return [
            'stock_actual' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'precio_compra_promedio' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function compraItems(): HasMany
    {
        return $this->hasMany(CompraItem::class);
    }
}
