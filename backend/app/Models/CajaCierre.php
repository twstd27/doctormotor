<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'cajero_id', 'fecha', 'monto_apertura', 'monto_esperado', 'monto_contado',
    'diferencia', 'estado', 'cerrado_at',
])]
class CajaCierre extends Model
{
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto_apertura' => 'decimal:2',
            'monto_esperado' => 'decimal:2',
            'monto_contado' => 'decimal:2',
            'diferencia' => 'decimal:2',
            'cerrado_at' => 'datetime',
        ];
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cajero_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'caja_cierre_id');
    }

    /**
     * Efectivo que debería haber en la caja: apertura + cobros en efectivo del turno.
     * Es la única fórmula: la usan el cierre (API y panel) y el indicador de la barra superior.
     */
    public function efectivoEsperado(): float
    {
        return (float) $this->monto_apertura + (float) $this->pagos()->where('metodo', 'efectivo')->sum('monto');
    }

    public static function abiertaDe(int $cajeroId): ?self
    {
        return static::where('cajero_id', $cajeroId)->where('estado', 'abierta')->first();
    }
}
