<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
        // Si la consulta vino con scopeConSaldo, el total ya está calculado (sin otra consulta).
        return (float) ($this->total_facturado ?? PresupuestoItem::whereHas(
            'presupuesto.ordenTrabajo',
            fn ($q) => $q->where('cliente_id', $this->id)
        )->where('aprobado', true)->sum('subtotal'));
    }

    public function totalPagado(): float
    {
        return (float) ($this->total_pagado ?? $this->pagos()->sum('monto'));
    }

    /**
     * Agrega `total_facturado` y `total_pagado` como columnas calculadas, para mostrar el
     * saldo de muchos clientes sin una consulta por fila (misma regla que totalFacturado()).
     */
    public function scopeConSaldo(Builder $query): void
    {
        $query->addSelect([
            'total_facturado' => PresupuestoItem::query()
                ->selectRaw('COALESCE(SUM(presupuesto_items.subtotal), 0)')
                ->join('presupuestos', 'presupuestos.id', '=', 'presupuesto_items.presupuesto_id')
                ->join('ordenes_trabajo', 'ordenes_trabajo.id', '=', 'presupuestos.orden_trabajo_id')
                ->whereColumn('ordenes_trabajo.cliente_id', 'clientes.id')
                ->where('presupuesto_items.aprobado', true),
            'total_pagado' => Pago::query()
                ->selectRaw('COALESCE(SUM(pagos.monto), 0)')
                ->whereColumn('pagos.cliente_id', 'clientes.id'),
        ]);
    }

    /** Clientes a los que todavía se les debe cobrar algo (facturado > pagado). */
    public function scopeConSaldoPendiente(Builder $query): void
    {
        $query->whereRaw(
            '(SELECT COALESCE(SUM(pi.subtotal), 0) FROM presupuesto_items pi'
            .' JOIN presupuestos p ON p.id = pi.presupuesto_id'
            .' JOIN ordenes_trabajo o ON o.id = p.orden_trabajo_id'
            .' WHERE o.cliente_id = clientes.id AND pi.aprobado = true)'
            .' > (SELECT COALESCE(SUM(pg.monto), 0) FROM pagos pg WHERE pg.cliente_id = clientes.id) + 0.009',
        );
    }

    /** Clientes que tienen un vehículo en el taller en este momento. */
    public function scopeEnElTaller(Builder $query): void
    {
        $query->whereHas('ordenesTrabajo', fn ($q) => $q->whereNotIn('estado', ['entregado', 'cancelado']));
    }

    /**
     * Positivo = debe; negativo = pagó de más (anticipo por encima de lo facturado).
     */
    public function saldoPendiente(): float
    {
        return $this->totalFacturado() - $this->totalPagado();
    }
}
