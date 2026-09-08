<?php

namespace App\Filament\Resources\OrdenesTrabajo\Pages;

use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;

class ListOrdenesTrabajo extends ListRecords
{
    protected static string $resource = OrdenTrabajoResource::class;

    #[Url]
    public ?array $tableFilters = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Recepcionar vehículo'),
        ];
    }
}
