<?php

namespace App\Filament\Resources\MovimientosInventario\Tables;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MovimientosInventarioTable
{
    private const LABELS_TIPO = [
        'entrada_compra' => 'Entrada por compra',
        'salida_ot' => 'Salida por orden de trabajo',
        'ajuste' => 'Ajuste manual',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'entrada_compra' => 'success',
                        'salida_ot' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => self::LABELS_TIPO[$state] ?? $state),
                TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->numeric(2)
                    ->color(fn (MovimientoInventario $record) => $record->cantidad >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => ($state >= 0 ? '+' : '').number_format($state, 2)),
                TextColumn::make('referencia_tipo')
                    ->label('Referencia')
                    ->formatStateUsing(fn (MovimientoInventario $record) => match ($record->referencia_tipo) {
                        'compra' => "Compra #{$record->referencia_id}",
                        'orden_trabajo' => "OT #{$record->referencia_id}",
                        default => $record->referencia_tipo ? "{$record->referencia_tipo} #{$record->referencia_id}" : '—',
                    })
                    ->url(fn (MovimientoInventario $record) => match ($record->referencia_tipo) {
                        'compra' => route('filament.admin.resources.compras.view', ['record' => $record->referencia_id]),
                        'orden_trabajo' => route('filament.admin.resources.ordenes-trabajo.edit', ['record' => $record->referencia_id]),
                        default => null,
                    }),
                TextColumn::make('user.nombre')
                    ->label('Registrado por')
                    ->placeholder('—'),
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                SelectFilter::make('producto_id')
                    ->label('Producto')
                    ->options(fn () => Producto::pluck('nombre', 'id'))
                    ->searchable(),
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(self::LABELS_TIPO),
            ]);
    }
}
