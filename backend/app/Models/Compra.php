<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['proveedor_id', 'registrado_por_id', 'numero_factura', 'total', 'estado_pago', 'fecha'])]
class Compra extends Model
{
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompraItem::class);
    }

    public function cuentaPorPagar(): HasMany
    {
        return $this->hasMany(CuentaPorPagar::class);
    }
}
