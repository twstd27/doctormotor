<?php

namespace App\Filament\Resources\ServiciosFrecuentes\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Resources\ServiciosFrecuentes\ServicioFrecuenteResource;
use Filament\Resources\Pages\CreateRecord;

class CrearServicioFrecuente extends CreateRecord
{
    use RedirigeALaLista;

    protected static string $resource = ServicioFrecuenteResource::class;
}
