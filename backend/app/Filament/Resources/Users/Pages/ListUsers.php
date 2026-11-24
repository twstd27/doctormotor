<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getSubheading(): string
    {
        return 'El personal del taller que entra al sistema. Los clientes se manejan desde su ficha.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Invitar usuario')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        $personal = fn () => UserResource::getEloquentQuery();

        return [
            'todos' => Tab::make('Todos')
                ->badge($personal()->count()),
            'tecnicos' => Tab::make('Técnicos')
                ->badge($personal()->where('rol', 'operador_tecnico')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rol', 'operador_tecnico')),
            'invitaciones' => Tab::make('Invitaciones pendientes')
                ->badge($personal()->whereNull('password')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('password')),
            'inactivos' => Tab::make('Inactivos')
                ->badge($personal()->where('activo', false)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('activo', false)),
        ];
    }
}
