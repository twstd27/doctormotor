<?php

namespace App\Filament\Resources\CajaCierres\Pages;

use App\Filament\Resources\CajaCierres\CajaCierreResource;
use App\Models\CajaCierre;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCajaCierres extends ListRecords
{
    protected static string $resource = CajaCierreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Abrir caja')
                ->disabled(fn () => CajaCierre::where('cajero_id', auth()->id())->where('estado', 'abierta')->exists())
                ->tooltip(fn () => CajaCierre::where('cajero_id', auth()->id())->where('estado', 'abierta')->exists()
                    ? 'Ya tenés una caja abierta.'
                    : null),
        ];
    }
}
