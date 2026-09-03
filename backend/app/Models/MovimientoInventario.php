<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['producto_id', 'tipo', 'cantidad', 'referencia_id', 'referencia_tipo', 'user_id', 'fecha'])]
class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
