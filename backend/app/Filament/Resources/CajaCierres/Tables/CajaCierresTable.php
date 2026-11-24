<?php

namespace App\Filament\Resources\CajaCierres\Tables;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CajaCierresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('cajero.nombre')
                    ->label('Cajero'),
                TextColumn::make('monto_apertura')
                    ->label('Apertura')
                    ->money('BOB', locale: 'es_BO'),
                TextColumn::make('monto_esperado')
                    ->label('Esperado')
                    ->money('BOB', locale: 'es_BO')
                    ->placeholder('—'),
                TextColumn::make('monto_contado')
                    ->label('Contado')
                    ->money('BOB', locale: 'es_BO')
                    ->placeholder('—'),
                TextColumn::make('diferencia')
                    ->label('Diferencia')
                    ->money('BOB', locale: 'es_BO')
                    ->placeholder('—')
                    ->color(fn ($state) => $state === null ? 'gray' : ($state < 0 ? 'danger' : ($state > 0 ? 'warning' : 'success'))),
                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors(['success' => 'abierta', 'gray' => 'cerrada'])
                    ->formatStateUsing(fn (string $state) => $state === 'abierta' ? 'Abierta' : 'Cerrada'),
            ])
            ->defaultSort('fecha', 'desc')
            ->recordActions([])
            ->emptyStateHeading('Todavía no hay cierres');
    }
}
