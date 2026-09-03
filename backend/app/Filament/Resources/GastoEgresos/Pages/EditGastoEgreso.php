<?php

namespace App\Filament\Resources\GastoEgresos\Pages;

use App\Filament\Resources\GastoEgresos\GastoEgresoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGastoEgreso extends EditRecord
{
    protected static string $resource = GastoEgresoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
