<?php

namespace App\Filament\Resources\GastoEgresos\Pages;

use App\Filament\Resources\GastoEgresos\GastoEgresoResource;
use App\Models\GastoEgreso;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGastoEgreso extends EditRecord
{
    protected static string $resource = GastoEgresoResource::class;

    // Por defecto Filament se queda en el formulario tras guardar — acá se prefiere volver
    // al listado, consistente en todos los recursos del panel.
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['categoria'] = GastoEgreso::categoriaDeRubro($data['rubro'] ?? null);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
