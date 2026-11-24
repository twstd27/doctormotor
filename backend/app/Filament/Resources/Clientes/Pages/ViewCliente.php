<?php

namespace App\Filament\Resources\Clientes\Pages;

use App\Filament\Resources\Clientes\Actions\InvitarPorWhatsapp;
use App\Filament\Resources\Clientes\ClienteResource;
use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Ficha del cliente: reemplaza a "Editar" como pantalla principal. Vehículos, historial de
 * órdenes, saldo y estado del portal en un solo lugar (HANDOFF §4, Cliente-ficha).
 */
class ViewCliente extends ViewRecord
{
    protected static string $resource = ClienteResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->nombre;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Editar datos')->color('gray'),
            Action::make('recepcion')
                ->label('Nueva recepción')
                ->icon('heroicon-o-plus')
                ->url(fn () => OrdenTrabajoResource::getUrl('create'))
                ->visible(fn () => OrdenTrabajoResource::canCreate() && ! $this->getRecord()->trashed()),
            ActionGroup::make([
                InvitarPorWhatsapp::make()->label('Reenviar enlace de acceso'),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])->label('Más acciones')->icon('heroicon-o-ellipsis-horizontal')->iconButton()->color('gray'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $cliente = $this->getRecord();

        return $schema->components([
            View::make('filament.clientes.cabecera')->viewData(['cliente' => $cliente]),
            View::make('filament.clientes.estadisticas')->viewData(['cliente' => $cliente]),
            Grid::make(['default' => 1, 'xl' => 3])->schema([
                Group::make([
                    View::make('filament.clientes.vehiculos')->viewData(['cliente' => $cliente]),
                    $this->getRelationManagersContentComponent(),
                ])->columnSpan(['xl' => 2]),
                Group::make([
                    View::make('filament.clientes.datos')->viewData(['cliente' => $cliente]),
                ])->columnSpan(['xl' => 1]),
            ]),
        ]);
    }
}
