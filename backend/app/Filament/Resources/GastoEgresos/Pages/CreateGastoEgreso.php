<?php

namespace App\Filament\Resources\GastoEgresos\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Resources\GastoEgresos\GastoEgresoResource;
use App\Models\CajaCierre;
use App\Models\GastoEgreso;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class CreateGastoEgreso extends CreateRecord
{
    use RedirigeALaLista;

    protected static string $resource = GastoEgresoResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Registrar gasto';
    }

    public function getBreadcrumb(): string
    {
        return 'Registrar';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Registrar gasto');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->label('Registrar y cargar otro');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Gasto registrado';
    }

    protected function beforeCreate(): void
    {
        $datos = $this->form->getState();

        if ($datos['origen'] !== 'caja') {
            return;
        }

        // Un gasto en efectivo sin caja abierta no descontaría de ningún arqueo: quedaría
        // pagado pero invisible para el cierre.
        if (! CajaCierre::abiertaDe((int) auth()->id())) {
            Notification::make()
                ->title('No tienes una caja abierta')
                ->body('Abre tu turno de caja antes de registrar un gasto en efectivo, o elige otro origen.')
                ->danger()
                ->send();

            throw new Halt;
        }

        // La caja es del turno de hoy: un gasto de otro día no puede salir de ella.
        if (! Carbon::parse($datos['fecha'])->isToday()) {
            Notification::make()
                ->title('Un gasto en efectivo sale de la caja de hoy')
                ->body('Cambia la fecha a hoy o elige otro origen (banco o QR).')
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['registrado_por_id'] = auth()->id();
        // La categoría que usan los reportes (fijo/variable) se deriva del rubro elegido.
        $data['categoria'] = GastoEgreso::categoriaDeRubro($data['rubro'] ?? null);
        $data['caja_cierre_id'] = $data['origen'] === 'caja'
            ? CajaCierre::abiertaDe((int) auth()->id())?->id
            : null;

        return $data;
    }
}
