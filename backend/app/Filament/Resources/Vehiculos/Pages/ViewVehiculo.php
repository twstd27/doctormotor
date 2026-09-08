<?php

namespace App\Filament\Resources\Vehiculos\Pages;

use App\Filament\Resources\Vehiculos\VehiculoResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ViewVehiculo extends ViewRecord
{
    protected static string $resource = VehiculoResource::class;

    public function getTitle(): string|Htmlable
    {
        $vehiculo = $this->getRecord();

        return new HtmlString(
            e("{$vehiculo->marca} {$vehiculo->modelo} {$vehiculo->anio}")
            .' <span class="ms-2 rounded-full border border-lime-800 bg-gray-800 px-2.5 py-1 align-middle font-mono text-xs tracking-wider text-lime-300">'
            .e($vehiculo->placa)
            .'</span>'
        );
    }

    public function getSubheading(): string
    {
        $vehiculo = $this->getRecord();
        $totalEvidencias = $vehiculo->evidencias()->count();

        return "{$vehiculo->cliente?->nombre} · {$totalEvidencias} ".Str::plural('evidencia', $totalEvidencias).' en órdenes de trabajo';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nuevaOrden')
                ->label('Nueva orden')
                ->color('gray')
                ->url(fn () => route('filament.admin.resources.ordenes-trabajo.create')),
            EditAction::make(),
        ];
    }
}
