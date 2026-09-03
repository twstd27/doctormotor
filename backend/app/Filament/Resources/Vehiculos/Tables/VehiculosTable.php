<?php

namespace App\Filament\Resources\Vehiculos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class VehiculosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('placa')
                    ->label('Placa')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('marca')
                    ->label('Marca')
                    ->searchable(),
                TextColumn::make('modelo')
                    ->label('Modelo')
                    ->searchable(),
                TextColumn::make('anio')
                    ->label('Año')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('color')
                    ->label('Color'),
                TextColumn::make('kilometraje_actual')
                    ->label('Kilometraje')
                    ->numeric()
                    ->sortable()
                    ->suffix(' km'),
            ])
            ->defaultSort('placa')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
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
