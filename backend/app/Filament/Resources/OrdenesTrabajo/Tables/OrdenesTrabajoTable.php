<?php

namespace App\Filament\Resources\OrdenesTrabajo\Tables;

use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Models\OrdenTrabajo;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Support\EstadoOrden;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdenesTrabajoTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // El saldo de cada fila sale de columnas calculadas en la propia consulta.
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['vehiculo', 'cliente', 'tecnicoAsignado'])
                ->conSaldo())
            ->columns([
                ViewColumn::make('codigo')
                    ->label('Orden')
                    ->view('filament.ordenes.col-orden')
                    ->searchable()
                    ->sortable(),
                ViewColumn::make('vehiculo.placa')
                    ->label('Vehículo')
                    ->view('filament.ordenes.col-vehiculo')
                    ->searchable(['placa', 'marca', 'modelo']),
                ViewColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->view('filament.ordenes.col-cliente')
                    ->searchable(['nombre', 'telefono_whatsapp']),
                ViewColumn::make('estado')
                    ->label('Estado')
                    ->view('filament.ordenes.col-estado')
                    ->sortable(),
                ViewColumn::make('tecnicoAsignado.nombre')
                    ->label('Técnico')
                    ->view('filament.ordenes.col-tecnico'),
                ViewColumn::make('fecha_entrega_estimada')
                    ->label('Entrega')
                    ->view('filament.ordenes.col-entrega')
                    ->sortable(),
                ViewColumn::make('saldo')
                    ->label('Saldo')
                    ->alignEnd()
                    ->view('filament.ordenes.col-saldo'),
            ])
            ->defaultSort('fecha_ingreso', 'desc')
            ->searchPlaceholder('Placa, cliente o código')
            ->filters([
                // Lo usan los enlaces del inicio (?tableFilters[estado][value]=…).
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoOrden::ETIQUETAS),
                SelectFilter::make('tecnico_asignado_id')
                    ->label('Técnico')
                    ->placeholder('Todos')
                    ->options(fn () => User::where('rol', 'operador_tecnico')->orderBy('nombre')->pluck('nombre', 'id')),
                SelectFilter::make('entrega')
                    ->label('Entrega')
                    ->placeholder('Cualquiera')
                    ->options([
                        'vencida' => 'Vencida',
                        'hoy' => 'Hoy',
                        'semana' => 'Esta semana',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'vencida' => $query->whereDate('fecha_entrega_estimada', '<', today())
                            ->whereNotIn('estado', EstadoOrden::CERRADOS),
                        'hoy' => $query->whereDate('fecha_entrega_estimada', today()),
                        'semana' => $query->whereBetween('fecha_entrega_estimada', [today(), today()->addDays(7)]),
                        default => $query,
                    }),
                Filter::make('con_saldo')
                    ->label('Con saldo pendiente')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->conSaldoPendiente()),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->deferFilters(false)
            ->filtersFormColumns(['default' => 1, 'sm' => 2, 'lg' => 4])
            ->recordUrl(fn (OrdenTrabajo $record) => OrdenTrabajoResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                Action::make('whatsapp')
                    ->label('Escribir al cliente por WhatsApp')
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->color('gray')
                    ->tooltip('Escribir al cliente por WhatsApp')
                    ->url(fn (OrdenTrabajo $record) => 'https://wa.me/'.app(WhatsAppService::class)->normalizarTelefono(
                        (string) $record->cliente?->telefono_whatsapp,
                    ))
                    ->openUrlInNewTab()
                    ->visible(fn (OrdenTrabajo $record) => filled($record->cliente?->telefono_whatsapp)),
                ActionGroup::make([
                    ViewAction::make()->label('Ver ficha'),
                    EditAction::make(),
                ])->label('Más acciones'),
            ]);
    }
}
