<?php

namespace App\Filament\Resources\Pagos\Pages;

use App\Filament\Resources\Pagos\PagoResource;
use App\Models\CajaCierre;
use App\Models\OrdenTrabajo;
use App\Models\Pago;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreatePago extends CreateRecord
{
    protected static string $resource = PagoResource::class;

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

            throw new Halt();
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
