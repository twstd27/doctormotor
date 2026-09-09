<?php

namespace App\Filament\Resources\Notificaciones\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NotificacionesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('user.nombre')
                    ->label('Destinatario')
                    ->placeholder('—'),
                TextColumn::make('telefono_destino')
                    ->label('Teléfono')
                    ->placeholder('—'),
                TextColumn::make('plantilla')
                    ->label('Plantilla'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'enviado' => 'success',
                        'fallido' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'enviado' => 'Enviado',
                        'fallido' => 'Fallido',
                        'pendiente' => 'Pendiente',
                        default => $state,
                    }),
                TextColumn::make('error')
                    ->label('Error de Meta')
                    ->placeholder('—')
                    ->limit(60)
                    ->tooltip(fn (?string $state) => $state)
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'enviado' => 'Enviado',
                        'fallido' => 'Fallido',
                    ]),
                SelectFilter::make('canal')
                    ->label('Canal')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                    ]),
            ]);
    }
}
