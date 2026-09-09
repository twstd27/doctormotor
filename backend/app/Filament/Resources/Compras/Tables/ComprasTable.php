<?php

namespace App\Filament\Resources\Compras\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ComprasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable(),
                TextColumn::make('numero_factura')
                    ->label('N.° factura')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('BOB')
                    ->sortable(),
                BadgeColumn::make('estado_pago')
                    ->label('Estado')
                    ->colors([
                        'gray' => 'pendiente',
                        'warning' => 'parcial',
                        'success' => 'pagado',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pendiente' => 'Pendiente',
                        'parcial' => 'Pago parcial',
                        'pagado' => 'Pagado',
                        default => $state,
                    }),
                TextColumn::make('registradoPor.nombre')
                    ->label('Registrado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                SelectFilter::make('estado_pago')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'parcial' => 'Pago parcial',
                        'pagado' => 'Pagado',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
