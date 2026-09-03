<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'presupuesto_id', 'tipo', 'producto_id', 'descripcion', 'cantidad',
    'precio_unitario', 'subtotal', 'es_adicional', 'aprobado',
])]
class PresupuestoItem extends Model
{
    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'es_adicional' => 'boolean',
            'aprobado' => 'boolean',
        ];
    }

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
