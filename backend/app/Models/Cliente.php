<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'nombre', 'ci_nit', 'telefono_whatsapp', 'correo', 'direccion', 'notas'])]
class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class);
    }

    public function ordenesTrabajo(): HasMany
    {
        return $this->hasMany(OrdenTrabajo::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    /**
     * Lo que se le aprobó cobrar en total, en todas sus OTs (abiertas o cerradas): suma de
     * los ítems de presupuesto con aprobado=true — no el campo `total` del presupuesto, que
     * no se recalcula cuando hay aprobación parcial y queda desactualizado.
     */
    public function totalFacturado(): float
    {
        return (float) PresupuestoItem::whereHas(
            'presupuesto.ordenTrabajo',
            fn ($q) => $q->where('cliente_id', $this->id)
        )->where('aprobado', true)->sum('subtotal');
    }

    public function totalPagado(): float
    {
        return (float) $this->pagos()->sum('monto');
    }

    /**
     * Positivo = debe; negativo = pagó de más (anticipo por encima de lo facturado).
     */
    public function saldoPendiente(): float
    {
        return $this->totalFacturado() - $this->totalPagado();
    }
}
