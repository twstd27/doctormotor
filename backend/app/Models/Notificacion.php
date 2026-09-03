<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'telefono_destino', 'canal', 'plantilla', 'orden_trabajo_id',
    'payload', 'estado', 'enviado_at', 'error',
])]
class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'enviado_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }
}
