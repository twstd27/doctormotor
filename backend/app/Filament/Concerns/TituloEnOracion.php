<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Str;

/**
 * Filament arma los títulos y migas con ucwords ("Órdenes De Trabajo"). El diseño pide
 * minúscula de oración ("Órdenes de trabajo"): solo la primera letra en mayúscula.
 * También afecta al menú lateral cuando el recurso no define su propia etiqueta.
 */
trait TituloEnOracion
{
    public static function getTitleCaseModelLabel(): string
    {
        return Str::ucfirst(static::getModelLabel());
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }
}
