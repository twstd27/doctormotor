<?php

namespace App\Filament\Resources\OrdenesTrabajo\Pages;

use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use Filament\Resources\Pages\EditRecord;

class EditOrdenTrabajo extends EditRecord
{
    protected static string $resource = OrdenTrabajoResource::class;

    // Por defecto Filament se queda en el formulario tras guardar — acá se prefiere volver
    // al listado, consistente en todos los recursos del panel.
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
