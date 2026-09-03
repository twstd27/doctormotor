<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['proveedor_id', 'compra_id', 'monto_original', 'saldo_pendiente', 'fecha_vencimiento', 'estado'])]
class CuentaPorPagar extends Model
{
    protected $table = 'cuentas_por_pagar';

    protected function casts(): array
    {
        return [
            'monto_original' => 'decimal:2',
            'saldo_pendiente' => 'decimal:2',
            'fecha_vencimiento' => 'date',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }
}
