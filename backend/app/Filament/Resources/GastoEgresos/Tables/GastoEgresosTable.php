<?php

namespace App\Filament\Resources\GastoEgresos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GastoEgresosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                BadgeColumn::make('categoria')
                    ->label('Categoría')
                    ->colors(['warning' => 'variable', 'gray' => 'fijo']),
                TextColumn::make('concepto')
                    ->label('Concepto')
                    ->searchable(),
                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('BOB')
                    ->sortable(),
                TextColumn::make('registradoPor.nombre')
                    ->label('Registrado por'),
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                SelectFilter::make('categoria')
                    ->label('Categoría')
                    ->options(['fijo' => 'Fijo', 'variable' => 'Variable']),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
