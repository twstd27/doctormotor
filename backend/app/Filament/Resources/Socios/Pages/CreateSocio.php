<?php

namespace App\Filament\Resources\Socios\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Resources\Socios\SocioResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSocio extends CreateRecord
{
    use RedirigeALaLista;

    protected static string $resource = SocioResource::class;
}
