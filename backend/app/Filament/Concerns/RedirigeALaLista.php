<?php

namespace App\Filament\Concerns;

/**
 * Al crear un registro, Filament por defecto lleva a la página de edición del registro
 * recién creado (y el botón pasa a decir "Guardar cambios"). Acá se vuelve a la lista.
 */
trait RedirigeALaLista
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
