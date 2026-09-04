<?php

namespace App\Filament\Resources\OrdenesTrabajo\Pages;

use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Models\OrdenTrabajo;
use Filament\Resources\Pages\CreateRecord;

class CreateOrdenTrabajo extends CreateRecord
{
    protected static string $resource = OrdenTrabajoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['codigo'] = OrdenTrabajo::generarCodigo();
        $data['recibido_por_id'] = auth()->id();
        $data['estado'] = 'recepcionado';
        $data['fecha_ingreso'] = now();

        return $data;
    }
}
