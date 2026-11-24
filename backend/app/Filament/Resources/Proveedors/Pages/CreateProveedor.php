<?php

namespace App\Filament\Resources\Proveedors\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Resources\Proveedors\ProveedorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProveedor extends CreateRecord
{
    use RedirigeALaLista;

    protected static string $resource = ProveedorResource::class;
}
