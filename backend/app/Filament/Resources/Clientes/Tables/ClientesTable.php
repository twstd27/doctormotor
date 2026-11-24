<?php

namespace App\Filament\Resources\Clientes\Tables;

use App\Filament\Resources\Clientes\Actions\InvitarPorWhatsapp;
use App\Filament\Resources\Clientes\ClienteResource;
use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Models\Cliente;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // El saldo, la última visita y las placas salen de la propia consulta (sin una por fila).
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['vehiculos:id,cliente_id,placa', 'user:id'])
                ->conSaldo()
                ->withMax('ordenesTrabajo as ultima_visita', 'fecha_ingreso'))
            ->columns([
                ViewColumn::make('nombre')
                    ->label('Cliente')
                    ->view('filament.clientes.col-cliente')
                    ->searchable(['nombre', 'ci_nit'])
                    ->sortable(),
                ViewColumn::make('telefono_whatsapp')
                    ->label('WhatsApp')
                    ->view('filament.clientes.col-whatsapp')
                    ->searchable(),
                ViewColumn::make('vehiculos')
                    ->label('Vehículos')
                    ->view('filament.clientes.col-vehiculos')
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->orWhereHas('vehiculos', fn ($q) => $q->where('placa', 'ilike', "%{$search}%"))),
                ViewColumn::make('ultima_visita')
                    ->label('Última visita')
                    ->view('filament.clientes.col-visita')
                    ->sortable(),
                ViewColumn::make('saldo')
                    ->label('Saldo')
                    ->alignEnd()
                    ->view('filament.clientes.col-saldo'),
                ViewColumn::make('user_id')
                    ->label('Portal')
                    ->view('filament.clientes.col-portal'),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Nombre, CI/NIT, WhatsApp o placa')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordUrl(fn (Cliente $record) => ClienteResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('recepcion')
                    ->label('Nueva recepción')
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('gray')
                    ->tooltip('Recepcionar un vehículo')
                    ->url(fn () => OrdenTrabajoResource::getUrl('create'))
                    ->visible(fn () => OrdenTrabajoResource::canCreate()),
                Action::make('whatsapp')
                    ->label('Escribir por WhatsApp')
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->color('gray')
                    ->tooltip('Escribir por WhatsApp')
                    ->url(fn (Cliente $record) => 'https://wa.me/'.app(WhatsAppService::class)->normalizarTelefono((string) $record->telefono_whatsapp))
                    ->openUrlInNewTab()
                    ->visible(fn (Cliente $record) => filled($record->telefono_whatsapp)),
                ActionGroup::make([
                    ViewAction::make()->label('Ver ficha'),
                    EditAction::make()->label('Editar datos'),
                    InvitarPorWhatsapp::make(),
                ])->label('Más acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
