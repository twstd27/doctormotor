<?php

namespace App\Filament\Resources\OrdenesTrabajo\Pages;

use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Models\OrdenTrabajo;
use App\Support\EstadoOrden;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListOrdenesTrabajo extends ListRecords
{
    protected static string $resource = OrdenTrabajoResource::class;

    #[Url]
    public ?array $tableFilters = null;

    public function getSubheading(): string
    {
        $enTaller = OrdenTrabajo::whereNotIn('estado', EstadoOrden::CERRADOS)->count();
        $listas = OrdenTrabajo::where('estado', 'listo_entrega')->count();

        $vehiculos = $enTaller === 1 ? '1 vehículo en el taller' : "{$enTaller} vehículos en el taller";
        $paraEntregar = $listas === 1 ? '1 listo para entregar' : "{$listas} listos para entregar";

        return "{$vehiculos} · {$paraEntregar}";
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Recepcionar vehículo')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        $cuenta = fn (array $estados) => OrdenTrabajo::whereIn('estado', $estados)->count();

        return [
            'en_taller' => Tab::make('En taller')
                ->badge(OrdenTrabajo::whereNotIn('estado', EstadoOrden::CERRADOS)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('estado', EstadoOrden::CERRADOS)),
            'por_diagnosticar' => Tab::make('Por diagnosticar')
                ->badge($cuenta(['recepcionado', 'en_diagnostico']))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('estado', ['recepcionado', 'en_diagnostico'])),
            'esperando' => Tab::make('Esperando aprobación')
                ->badge($cuenta(['esperando_aprobacion']))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'esperando_aprobacion')),
            'listas' => Tab::make('Listas para entregar')
                ->badge($cuenta(['listo_entrega']))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'listo_entrega')),
            'entregadas' => Tab::make('Entregadas')
                ->badge($cuenta(['entregado']))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'entregado')),
            'todas' => Tab::make('Todas'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        // Los enlaces del inicio filtran por un estado puntual (p. ej. "Entregado"): con la
        // pestaña "En taller" esa lista saldría vacía, así que se abre en "Todas".
        return filled($this->tableFilters['estado']['value'] ?? null) ? 'todas' : 'en_taller';
    }
}
