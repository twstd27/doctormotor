<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['caja_cierre_id', 'denominacion', 'cantidad'])]
class CajaArqueo extends Model
{
    protected $table = 'caja_arqueos';

    /** Billetes y monedas que se cuentan al cerrar la caja, de mayor a menor. */
    public const DENOMINACIONES = [200, 100, 50, 20, 10, 5, 2, 1, 0.5];

    protected function casts(): array
    {
        return [
            'denominacion' => 'decimal:2',
            'cantidad' => 'integer',
        ];
    }

    public function cajaCierre(): BelongsTo
    {
        return $this->belongsTo(CajaCierre::class, 'caja_cierre_id');
    }
}
