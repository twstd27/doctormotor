<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Atajo para armar presupuestos (lavado, diagnóstico…). Ver migración 2026_11_24_200000. */
#[Fillable(['nombre', 'tipo', 'descripcion', 'precio_unitario', 'activo', 'orden'])]
class ServicioFrecuente extends Model
{
    // Eloquent pluralizaría mal el nombre en español (servicio_frecuentes): se fija a mano.
    protected $table = 'servicios_frecuentes';

    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }
}
