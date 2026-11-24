<?php

namespace App\Filament\Resources\Vehiculos\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Resources\Vehiculos\VehiculoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVehiculo extends CreateRecord
{
    use RedirigeALaLista;

    protected static string $resource = VehiculoResource::class;
}
