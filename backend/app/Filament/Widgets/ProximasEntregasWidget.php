<?php

namespace App\Filament\Widgets;

use App\Models\OrdenTrabajo;
use App\Support\EstadoOrden;
use Filament\Widgets\Widget;

class ProximasEntregasWidget extends Widget
{
    protected string $view = 'filament.widgets.proximas-entregas';

    protected static ?int $sort = 50;

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        return [
            'entregas' => OrdenTrabajo::query()
                ->with(['vehiculo', 'cliente'])
                ->whereNotIn('estado', EstadoOrden::CERRADOS)
                ->whereNotNull('fecha_entrega_estimada')
                ->whereDate('fecha_entrega_estimada', '>=', today())
                ->orderBy('fecha_entrega_estimada')
                ->limit(5)
                ->get(),
        ];
    }
}
