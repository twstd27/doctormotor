<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
