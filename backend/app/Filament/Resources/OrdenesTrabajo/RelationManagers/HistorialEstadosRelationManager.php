<?php

namespace App\Filament\Resources\OrdenesTrabajo\RelationManagers;

use App\Support\EstadoOrden;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Línea de tiempo de cambios de estado de la orden (pestaña "Historial"). */
class HistorialEstadosRelationManager extends RelationManager
{
    protected static string $relationship = 'estadosHistorial';

    protected static ?string $title = 'Historial';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Cuándo')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('estado_nuevo')
                    ->label('Pasó a')
                    ->badge()
                    ->color(fn (string $state) => EstadoOrden::color($state))
                    ->formatStateUsing(fn (string $state) => EstadoOrden::etiqueta($state)),
                TextColumn::make('estado_anterior')
                    ->label('Venía de')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => $state ? EstadoOrden::etiqueta($state) : null),
                TextColumn::make('user.nombre')
                    ->label('Quién')
                    ->placeholder('Sistema'),
                TextColumn::make('comentario')
                    ->label('Comentario')
                    ->placeholder('—')
                    ->wrap(),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
}
