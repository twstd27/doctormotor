<?php

namespace App\Filament\Resources\GastoEgresos\Pages;

use App\Filament\Resources\GastoEgresos\GastoEgresoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGastoEgreso extends CreateRecord
{
    protected static string $resource = GastoEgresoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['registrado_por_id'] = auth()->id();

        return $data;
    }
}
