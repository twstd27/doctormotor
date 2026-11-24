<?php

namespace App\Filament\Resources\CajaCierres\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Pages\CajaDelDia;
use App\Filament\Resources\CajaCierres\CajaCierreResource;
use App\Models\CajaCierre;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class CreateCajaCierre extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    use RedirigeALaLista;

    protected static string $resource = CajaCierreResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Abrir caja';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Abrir caja');
    }

    protected function getRedirectUrl(): string
    {
        return CajaDelDia::getUrl();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Caja abierta';
    }

    protected function beforeCreate(): void
    {
        $yaAbierta = CajaCierre::where('cajero_id', auth()->id())
            ->where('estado', 'abierta')
            ->exists();

        if ($yaAbierta) {
            Notification::make()
                ->title('Ya tienes una caja abierta')
                ->danger()
                ->send();

            throw new Halt;
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
