<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Resources\Clientes\ClienteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCliente extends CreateRecord
{
    use RedirigeALaLista;

    protected static string $resource = ClienteResource::class;

    // El cliente recién creado queda a un clic de recepcionar su vehículo desde su ficha.
    protected function getRedirectUrl(): string
    {
        return ClienteResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Cliente registrado';
    }
}
