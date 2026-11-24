<?php

namespace App\Filament\Resources\Productos\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Resources\Productos\ProductoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProducto extends CreateRecord
{
    use RedirigeALaLista;

    protected static string $resource = ProductoResource::class;
}
