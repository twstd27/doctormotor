<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Filament\Resources\Pagos\PagoResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Inicio';

    protected static ?string $navigationLabel = 'Inicio';

    // El título de la página es el saludo; el menú sigue diciendo "Inicio".
    public function getTitle(): string|Htmlable
    {
        $hora = (int) now()->format('G');
        $saludo = match (true) {
            $hora < 12 => 'Buenos días',
            $hora < 19 => 'Buenas tardes',
            default => 'Buenas noches',
        };

        $nombre = trim((string) explode(' ', trim((string) auth()->user()?->nombre))[0]);

        return $nombre !== '' ? "{$saludo}, {$nombre}" : $saludo;
    }

    public function getSubheading(): string
    {
        return ucfirst(now()->translatedFormat('l j \d\e F'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cobro')
                ->label('Registrar cobro')
                ->icon('heroicon-o-banknotes')
                ->color('gray')
                ->url(fn () => PagoResource::getUrl('create'))
                ->visible(fn () => PagoResource::canCreate()),
            Action::make('recepcion')
                ->label('Recepcionar vehículo')
                ->icon('heroicon-o-plus')
                ->url(fn () => OrdenTrabajoResource::getUrl('create'))
                ->visible(fn () => OrdenTrabajoResource::canCreate()),
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 3,
        ];
    }
}
