<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Resources\Clientes\ClienteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCliente extends CreateRecord
{
    use RedirigeALaLista;

    protected static string $resource = ClienteResource::class;
}
