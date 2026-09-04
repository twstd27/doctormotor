<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Resources\Pagos\PagoResource;
use App\Models\CajaCierre;
use Filament\Resources\Pages\CreateRecord;

class CreatePago extends CreateRecord
{
    protected static string $resource = PagoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['cajero_id'] = auth()->id();
        $data['caja_cierre_id'] = CajaCierre::abiertaDe(auth()->id())?->id;
        $data['fecha'] = now();

        return $data;
    }
}
