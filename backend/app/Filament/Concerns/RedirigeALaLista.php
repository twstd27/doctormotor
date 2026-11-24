<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;

/**
 * Al crear un registro, Filament por defecto lleva a la página de edición del registro
 * recién creado (y el botón pasa a decir "Guardar cambios"). Acá se vuelve a la lista.
 *
 * También corrige el título: Filament arma "Crear Cliente" (mayúscula en cada palabra) y
 * acá va "Crear cliente".
 */
trait RedirigeALaLista
{
    public function getTitle(): string|Htmlable
    {
        return 'Crear '.static::getResource()::getModelLabel();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
