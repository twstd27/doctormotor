<?php

namespace App\Filament\Resources\ServiciosFrecuentes\Pages;

use App\Filament\Resources\ServiciosFrecuentes\ServicioFrecuenteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListarServiciosFrecuentes extends ListRecords
{
    protected static string $resource = ServicioFrecuenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
