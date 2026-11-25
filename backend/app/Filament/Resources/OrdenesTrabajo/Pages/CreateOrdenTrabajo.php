<?php

namespace App\Filament\Resources\OrdenesTrabajo\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Models\OrdenTrabajo;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateOrdenTrabajo extends CreateRecord
{
    use RedirigeALaLista;

    protected static string $resource = OrdenTrabajoResource::class;

    // Cada recepción es un vehículo distinto: no tiene sentido "crear y crear otro".
    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'Recepción de vehículo';
    }

    public function getSubheading(): string
    {
        return 'Empieza por la placa: el cliente y su historial se cargan solos.';
    }

    public function getBreadcrumb(): string
    {
        return 'Nueva recepción';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Recepcionar vehículo');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Vehículo recepcionado';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['codigo'] = OrdenTrabajo::generarCodigo();
        $data['recibido_por_id'] = auth()->id();
        $data['estado'] = 'recepcionado';
        $data['fecha_ingreso'] = now();

        // Los chips de "Servicio solicitado" no son columna: se anotan al final del motivo.
        $servicios = array_filter((array) ($this->form->getRawState()['servicios_solicitados'] ?? []));

        if ($servicios !== []) {
            $linea = 'Servicio solicitado: '.implode(', ', $servicios);
            $data['descripcion_problema'] = trim(($data['descripcion_problema'] ?? '')."\n".$linea);
        }

        return $data;
    }
}
