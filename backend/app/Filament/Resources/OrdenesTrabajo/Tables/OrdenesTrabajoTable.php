<?php

namespace App\Filament\Resources\OrdenesTrabajo\Tables;

use App\Support\EstadoOrden;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdenesTrabajoTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vehiculo.placa')
                    ->label('Vehículo')
                    ->formatStateUsing(fn ($record) => "{$record->vehiculo->marca} {$record->vehiculo->modelo} ({$record->vehiculo->placa})")
                    ->searchable(['placa']),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => EstadoOrden::color($state))
                    ->formatStateUsing(fn (string $state) => EstadoOrden::etiqueta($state)),
                TextColumn::make('tecnicoAsignado.nombre')
                    ->label('Técnico')
                    ->placeholder('Sin asignar'),
                TextColumn::make('fecha_ingreso')
                    ->label('Ingreso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('fecha_ingreso', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->options(EstadoOrden::ETIQUETAS),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
