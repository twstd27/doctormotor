<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'codigo', 'vehiculo_id', 'cliente_id', 'recibido_por_id', 'tecnico_asignado_id',
    'estado', 'descripcion_problema', 'kilometraje_ingreso', 'nivel_gasolina',
    'fecha_ingreso', 'fecha_entrega_estimada', 'fecha_entrega_real',
])]
class OrdenTrabajo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ordenes_trabajo';

    public const ESTADOS = [
        'recepcionado', 'en_diagnostico', 'esperando_aprobacion', 'en_reparacion',
        'control_calidad', 'listo_entrega', 'entregado', 'cancelado',
    ];

    private const ESTADOS_CERRADOS = ['entregado', 'cancelado'];

    /**
     * Una OT entregada o cancelada es de solo lectura: no se le suman costos, presupuestos,
     * evidencias ni cambios de datos — el historial de lo que ya pasó no debe alterarse.
     */
    public function estaCerrada(): bool
    {
        return in_array($this->estado, self::ESTADOS_CERRADOS, true);
    }

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'datetime',
            'fecha_entrega_estimada' => 'date',
            'fecha_entrega_real' => 'datetime',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por_id');
    }

    public function tecnicoAsignado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_asignado_id');
    }

    public function inspeccion(): HasOne
    {
        return $this->hasOne(Inspeccion::class, 'orden_trabajo_id');
    }

    public function estadosHistorial(): HasMany
    {
        return $this->hasMany(OtEstadoHistorial::class, 'orden_trabajo_id')->latest('created_at');
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(Evidencia::class, 'orden_trabajo_id');
    }

    public function presupuestos(): HasMany
    {
        return $this->hasMany(Presupuesto::class, 'orden_trabajo_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'orden_trabajo_id');
    }

    public function costosDirectos(): HasMany
    {
        return $this->hasMany(CostoDirecto::class, 'orden_trabajo_id');
    }

    /**
     * Agrega a la consulta `total_aprobado` (suma de ítems de presupuesto aprobados) y
     * `total_pagado` (suma de pagos) como columnas calculadas, para mostrar el saldo de
     * muchas órdenes sin una consulta por fila. Misma regla que Cliente::totalFacturado().
     */
    public function scopeConSaldo(Builder $query): void
    {
        $query->addSelect([
            'total_aprobado' => PresupuestoItem::query()
                ->selectRaw('COALESCE(SUM(presupuesto_items.subtotal), 0)')
                ->join('presupuestos', 'presupuestos.id', '=', 'presupuesto_items.presupuesto_id')
                ->whereColumn('presupuestos.orden_trabajo_id', 'ordenes_trabajo.id')
                ->where('presupuesto_items.aprobado', true),
            'total_pagado' => Pago::query()
                ->selectRaw('COALESCE(SUM(pagos.monto), 0)')
                ->whereColumn('pagos.orden_trabajo_id', 'ordenes_trabajo.id'),
        ]);
    }

    /** Órdenes cuyo cliente todavía les debe plata (aprobado > pagado). */
    public function scopeConSaldoPendiente(Builder $query): void
    {
        $query->whereRaw(
            '(SELECT COALESCE(SUM(pi.subtotal), 0) FROM presupuesto_items pi'
            .' JOIN presupuestos p ON p.id = pi.presupuesto_id'
            .' WHERE p.orden_trabajo_id = ordenes_trabajo.id AND pi.aprobado = true)'
            .' > (SELECT COALESCE(SUM(pg.monto), 0) FROM pagos pg WHERE pg.orden_trabajo_id = ordenes_trabajo.id) + 0.009',
        );
    }

    public function totalAprobado(): float
    {
        return (float) ($this->total_aprobado ?? PresupuestoItem::whereHas(
            'presupuesto',
            fn ($q) => $q->where('orden_trabajo_id', $this->id),
        )->where('aprobado', true)->sum('subtotal'));
    }

    public function totalPagado(): float
    {
        return (float) ($this->total_pagado ?? $this->pagos()->sum('monto'));
    }

    /** Positivo = el cliente debe; negativo = pagó de más. */
    public function saldoPendiente(): float
    {
        return $this->totalAprobado() - $this->totalPagado();
    }

    /**
     * Próximo estado del flujo normal (el que propone el botón "Pasar a…" de la ficha);
     * null si la orden ya terminó.
     */
    public function siguienteEstado(): ?string
    {
        $flujo = [
            'recepcionado', 'en_diagnostico', 'esperando_aprobacion', 'en_reparacion',
            'control_calidad', 'listo_entrega', 'entregado',
        ];

        $posicion = array_search($this->estado, $flujo, true);

        return $posicion === false ? null : ($flujo[$posicion + 1] ?? null);
    }

    public static function generarCodigo(): string
    {
        $anio = now()->year;
        $ultimo = static::withTrashed()
            ->where('codigo', 'like', "OT-{$anio}-%")
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('codigo');

        $siguiente = $ultimo ? ((int) substr($ultimo, -4)) + 1 : 1;

        return sprintf('OT-%d-%04d', $anio, $siguiente);
    }
}
