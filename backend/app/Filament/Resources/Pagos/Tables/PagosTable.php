<?php

namespace App\Filament\Resources\Pagos\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PagosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('ordenTrabajo.codigo')
                    ->label('OT')
                    ->placeholder('— abono general —'),
                BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'anticipo' => 'Anticipo',
                        'parcial' => 'Parcial',
                        'completo' => 'Completo',
                        'abono_deuda' => 'Abono a deuda',
                        default => $state,
                    }),
                BadgeColumn::make('metodo')
                    ->label('Método')
                    ->colors(['success' => 'efectivo', 'info' => 'qr', 'warning' => 'tarjeta'])
                    ->formatStateUsing(fn (string $state) => strtoupper($state) === 'QR' ? 'QR' : ucfirst($state)),
                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('BOB')
                    ->sortable(),
                BadgeColumn::make('tipo_documento')
                    ->label('Comprobante')
                    ->colors(['gray' => 'recibo', 'warning' => 'factura'])
                    ->formatStateUsing(fn (string $state) => $state === 'factura' ? 'Factura (pend.)' : 'Recibo'),
                TextColumn::make('cajero.nombre')
                    ->label('Cajero')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                SelectFilter::make('metodo')
                    ->options(['efectivo' => 'Efectivo', 'qr' => 'QR', 'tarjeta' => 'Tarjeta']),
                SelectFilter::make('tipo')
                    ->options([
                        'anticipo' => 'Anticipo', 'parcial' => 'Parcial',
                        'completo' => 'Completo', 'abono_deuda' => 'Abono a deuda',
                    ]),
            ])
            ->recordActions([
                Action::make('recibo')
                    ->label('Recibo')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn ($record) => route('admin-pdf.pagos.recibo', $record))
                    ->openUrlInNewTab(),
                Action::make('ticket')
                    ->label('Ticket')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn ($record) => route('admin-pdf.pagos.ticket', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
