<?php

namespace App\Filament\Resources\ServiciosFrecuentes\Pages;

use App\Filament\Resources\ServiciosFrecuentes\ServicioFrecuenteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditarServicioFrecuente extends EditRecord
{
    protected static string $resource = ServicioFrecuenteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
