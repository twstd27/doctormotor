<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['socio_id', 'porcentaje', 'vigente_desde', 'vigente_hasta'])]
class ReglaReparto extends Model
{
    protected $table = 'reglas_reparto';

    protected function casts(): array
    {
        return [
            'porcentaje' => 'decimal:2',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }
}
