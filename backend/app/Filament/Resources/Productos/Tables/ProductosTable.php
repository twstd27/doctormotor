<?php

namespace App\Filament\Resources\Productos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class ProductosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('categoria')
                    ->label('Categoría'),
                TextColumn::make('stock_actual')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => $record->stock_actual <= $record->stock_minimo ? 'danger' : null)
                    ->weight(fn ($record) => $record->stock_actual <= $record->stock_minimo ? 'bold' : null),
                TextColumn::make('stock_minimo')
                    ->label('Mínimo')
                    ->numeric(),
                TextColumn::make('precio_venta')
                    ->label('Precio venta')
                    ->money('BOB')
                    ->sortable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('nombre')
            ->filters([
                Filter::make('stock_bajo')
                    ->label('Stock bajo')
                    ->query(fn ($query) => $query->whereColumn('stock_actual', '<=', 'stock_minimo')),
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
