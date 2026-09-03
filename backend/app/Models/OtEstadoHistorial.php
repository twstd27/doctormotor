<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['orden_trabajo_id', 'estado_anterior', 'estado_nuevo', 'user_id', 'comentario'])]
class OtEstadoHistorial extends Model
{
    protected $table = 'ot_estados_historial';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
