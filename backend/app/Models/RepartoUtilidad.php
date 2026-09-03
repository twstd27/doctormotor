<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'periodo_inicio', 'periodo_fin', 'ingresos_total', 'costos_directos_total',
    'gastos_total', 'utilidad_neta', 'generado_por_id', 'generado_at',
])]
class RepartoUtilidad extends Model
{
    protected $table = 'reparto_utilidades';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date',
            'periodo_fin' => 'date',
            'ingresos_total' => 'decimal:2',
            'costos_directos_total' => 'decimal:2',
            'gastos_total' => 'decimal:2',
            'utilidad_neta' => 'decimal:2',
            'generado_at' => 'datetime',
        ];
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por_id');
    }

    public function detalle(): HasMany
    {
        return $this->hasMany(RepartoUtilidadDetalle::class);
    }
}
