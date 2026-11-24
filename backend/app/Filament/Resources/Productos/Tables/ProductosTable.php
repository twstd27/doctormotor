<?php

namespace App\Filament\Resources\Productos\Tables;

use App\Filament\Resources\MovimientosInventario\MovimientoInventarioResource;
use App\Filament\Resources\Productos\ProductoResource;
use App\Models\Producto;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ViewColumn::make('nombre')
                    ->label('Producto')
                    ->view('filament.productos.col-producto')
                    ->searchable(['nombre', 'sku'])
                    ->sortable(),
                TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->sortable(),
                ViewColumn::make('stock_actual')
                    ->label('Stock')
                    ->view('filament.productos.col-stock')
                    ->sortable(),
                TextColumn::make('precio_venta')
                    ->label('Precio de venta')
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->weight('semibold')
                    ->money('BOB', locale: 'es_BO')
                    ->sortable(),
                ViewColumn::make('activo')
                    ->label('Activo')
                    ->view('filament.productos.col-activo'),
            ])
            ->defaultSort('nombre')
            // Tocar la fila abre la edición solo para quien puede editar (el cajero solo da de alta).
            ->recordUrl(fn (Producto $record) => ProductoResource::canEdit($record) ? ProductoResource::getUrl('edit', ['record' => $record]) : null)
            ->searchPlaceholder('Nombre, SKU o marca')
            ->filters([
                SelectFilter::make('categoria')
                    ->label('Categoría')
                    ->options(fn () => Producto::query()
                        ->whereNotNull('categoria')
                        ->orderBy('categoria')
                        ->distinct()
                        ->pluck('categoria', 'categoria')),
                // Lo usan los accesos directos del inicio y del menú (?tableFilters[stock_bajo]…).
                Filter::make('stock_bajo')
                    ->label('Solo stock bajo')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereColumn('stock_actual', '<=', 'stock_minimo')),
            ])
            ->recordActions([
                Action::make('kardex')
                    ->label('Ver kardex')
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->color('gray')
                    ->tooltip('Ver kardex')
                    ->url(fn (Producto $record) => MovimientoInventarioResource::getUrl('index', [
                        'tableFilters' => ['producto_id' => ['value' => $record->id]],
                    ])),
                EditAction::make()->label('Editar')->hiddenLabel()->tooltip('Editar')
                    ->visible(fn (Producto $record) => ProductoResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
