<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reparto_utilidad_id', 'socio_id', 'porcentaje_aplicado', 'monto'])]
class RepartoUtilidadDetalle extends Model
{
    protected $table = 'reparto_utilidad_detalle';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'porcentaje_aplicado' => 'decimal:2',
            'monto' => 'decimal:2',
        ];
    }

    public function repartoUtilidad(): BelongsTo
    {
        return $this->belongsTo(RepartoUtilidad::class);
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }
}
