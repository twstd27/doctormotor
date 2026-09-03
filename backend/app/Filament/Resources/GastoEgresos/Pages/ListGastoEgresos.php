<?php

namespace App\Filament\Resources\GastoEgresos\Pages;

use App\Filament\Resources\GastoEgresos\GastoEgresoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGastoEgresos extends ListRecords
{
    protected static string $resource = GastoEgresoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
