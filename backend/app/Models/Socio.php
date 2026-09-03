<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'nombre', 'porcentaje_default', 'activo'])]
class Socio extends Model
{
    protected function casts(): array
    {
        return [
            'porcentaje_default' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reglasReparto(): HasMany
    {
        return $this->hasMany(ReglaReparto::class);
    }
}
