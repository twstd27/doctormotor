<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Filament\Resources\Pagos\PagoResource;
use App\Models\CajaCierre;
use App\Models\OrdenTrabajo;
use App\Models\Pago;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class CreatePago extends CreateRecord
{
    use RedirigeALaLista;

    protected static string $resource = PagoResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'Registrar cobro';
    }

    public function getBreadcrumb(): string
    {
        return 'Registrar';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Registrar cobro');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Cobro registrado';
    }

    // Si el cobro se hizo desde la ficha de una orden, se vuelve a esa ficha.
    protected function getRedirectUrl(): string
    {
        $ordenId = $this->getRecord()?->orden_trabajo_id;

        return $ordenId
            ? OrdenTrabajoResource::getUrl('view', ['record' => $ordenId])
            : static::getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        // El efectivo cobrado sin turno de caja abierto nunca se podría sumar en ningún
        // cierre (ver CajaController::cierre, que suma $caja->pagos()) — quedaría cobrado
        // pero invisible para el arqueo. Tarjeta y QR sí quedan trazados por fuera.
        if ($data['metodo'] === 'efectivo' && ! CajaCierre::abiertaDe(auth()->id())) {
            Notification::make()
                ->title('No tienes una caja abierta')
                ->body('Abre tu turno de caja antes de registrar cobros en efectivo.')
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['cajero_id'] = auth()->id();
        $data['caja_cierre_id'] = CajaCierre::abiertaDe(auth()->id())?->id;
        $data['fecha'] = now();

        if (($data['detalle_tipo'] ?? 'resumen') === 'detallado' && ! empty($data['orden_trabajo_id'])) {
            $data['detalle_items'] = Pago::itemsAprobadosDe(OrdenTrabajo::find($data['orden_trabajo_id']));
        }

        return $data;
    }
}
