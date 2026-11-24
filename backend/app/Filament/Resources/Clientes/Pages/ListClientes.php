<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Resources\Clientes\ClienteResource;
use App\Models\Cliente;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListClientes extends ListRecords
{
    protected static string $resource = ClienteResource::class;

    public function getSubheading(): string
    {
        $total = Cliente::count();
        $conSaldo = Cliente::conSaldoPendiente()->count();

        $clientes = $total === 1 ? '1 cliente' : "{$total} clientes";
        $saldo = $conSaldo === 1 ? '1 con saldo pendiente' : "{$conSaldo} con saldo pendiente";

        return "{$clientes} · {$saldo}";
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo cliente')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->badge(Cliente::count()),
            'con_saldo' => Tab::make('Con saldo')
                ->badge(Cliente::conSaldoPendiente()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->conSaldoPendiente()),
            'en_taller' => Tab::make('En el taller ahora')
                ->badge(Cliente::enElTaller()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->enElTaller()),
            'sin_portal' => Tab::make('Sin cuenta en el portal')
                ->badge(Cliente::whereNull('user_id')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('user_id')),
        ];
    }
}
