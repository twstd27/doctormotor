<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['orden_trabajo_id', 'accesorios', 'rayones_previos', 'observaciones', 'firma_cliente_url', 'firmado_at'])]
class Inspeccion extends Model
{
    protected $table = 'inspecciones';

    protected function casts(): array
    {
        return [
            'accesorios' => 'array',
            'rayones_previos' => 'array',
            'firmado_at' => 'datetime',
        ];
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }
}
