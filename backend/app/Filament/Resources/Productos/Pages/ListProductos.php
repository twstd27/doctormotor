<?php

namespace App\Filament\Resources\Productos\Pages;

use App\Filament\Resources\Productos\ProductoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;

class ListProductos extends ListRecords
{
    protected static string $resource = ProductoResource::class;

    #[Url]
    public ?array $tableFilters = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
