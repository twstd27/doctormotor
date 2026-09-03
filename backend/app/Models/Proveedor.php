<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'nit', 'telefono', 'direccion', 'activo'])]
class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }

    public function cuentasPorPagar(): HasMany
    {
        return $this->hasMany(CuentaPorPagar::class);
    }
}
