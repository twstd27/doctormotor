<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Inicio';

    protected static ?string $navigationLabel = 'Inicio';

    public function getSubheading(): string
    {
        return 'Resumen del taller al día de hoy';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nuevaOrden')
                ->label('Nueva orden')
                ->url(fn () => route('filament.admin.resources.ordenes-trabajo.create')),
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
