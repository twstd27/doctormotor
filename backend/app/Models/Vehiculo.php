<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['cliente_id', 'placa', 'marca', 'modelo', 'anio', 'color', 'motor', 'kilometraje_actual'])]
class Vehiculo extends Model
{
    use HasFactory, SoftDeletes;

    // qr_token queda fuera de #[Fillable] a propósito: es el identificador público que
    // resuelve el historial sin autenticación, así que nunca debe poder setearse por
    // mass-assignment desde la API (update() del vehículo) — solo se genera acá.
    protected static function booted(): void
    {
        static::creating(function (Vehiculo $vehiculo) {
            $vehiculo->qr_token ??= Str::random(32);
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ordenesTrabajo(): HasMany
    {
        return $this->hasMany(OrdenTrabajo::class);
    }

    public function evidencias(): HasManyThrough
    {
        return $this->hasManyThrough(Evidencia::class, OrdenTrabajo::class);
    }

    public function fotos(): HasManyThrough
    {
        return $this->evidencias()->where('tipo', 'foto');
    }

    public function videos(): HasManyThrough
    {
        return $this->evidencias()->where('tipo', 'video');
    }
}
