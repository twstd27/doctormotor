<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'orden_trabajo_id', 'cliente_id', 'cajero_id', 'caja_cierre_id', 'tipo', 'metodo',
    'monto', 'referencia_externa', 'comprobante_url', 'fecha',
])]
class Pago extends Model
{
    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cajero_id');
    }

    public function cajaCierre(): BelongsTo
    {
        return $this->belongsTo(CajaCierre::class, 'caja_cierre_id');
    }
}
