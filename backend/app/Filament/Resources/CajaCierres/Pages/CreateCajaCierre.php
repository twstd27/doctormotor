<?php

namespace App\Filament\Resources\CajaCierres\Pages;

use App\Filament\Resources\CajaCierres\CajaCierreResource;
use App\Models\CajaCierre;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateCajaCierre extends CreateRecord
{
    protected static string $resource = CajaCierreResource::class;

    protected function beforeCreate(): void
    {
        $yaAbierta = CajaCierre::where('cajero_id', auth()->id())
            ->where('estado', 'abierta')
            ->exists();

        if ($yaAbierta) {
            Notification::make()
                ->title('Ya tenés una caja abierta')
                ->danger()
                ->send();

            throw new Halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['cajero_id'] = auth()->id();
        $data['fecha'] = now()->toDateString();
        $data['estado'] = 'abierta';

        return $data;
    }
}
