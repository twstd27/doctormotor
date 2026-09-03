<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid_cliente', 'orden_trabajo_id', 'subido_por_id', 'tipo', 'url', 'etiqueta', 'tomada_at'])]
class Evidencia extends Model
{
    protected function casts(): array
    {
        return ['tomada_at' => 'datetime'];
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por_id');
    }
}
