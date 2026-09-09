<?php

namespace App\Filament\Resources\CuentasPorPagar\Tables;

use App\Models\CuentaPorPagar;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CuentasPorPagarTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable(),
                TextColumn::make('compra.numero_factura')
                    ->label('Factura')
                    ->placeholder('—'),
                TextColumn::make('compra.fecha')
                    ->label('Fecha de compra')
                    ->date('d/m/Y'),
                TextColumn::make('monto_original')
                    ->label('Monto original')
                    ->money('BOB'),
                TextColumn::make('saldo_pendiente')
                    ->label('Saldo pendiente')
                    ->money('BOB')
                    ->weight('bold')
                    ->color(fn (CuentaPorPagar $record) => $record->saldo_pendiente > 0 ? 'danger' : 'success'),
                TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'gray' => 'pendiente',
                        'danger' => 'vencido',
                        'success' => 'pagado',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pendiente' => 'Pendiente',
                        'vencido' => 'Vencido',
                        'pagado' => 'Pagado',
                        default => $state,
                    }),
            ])
            ->defaultSort('fecha_vencimiento')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'vencido' => 'Vencido',
                        'pagado' => 'Pagado',
                    ]),
            ])
            ->recordActions([
                Action::make('registrarPago')
                    ->label('Registrar pago')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (CuentaPorPagar $record) => $record->saldo_pendiente > 0)
                    ->schema([
                        TextInput::make('monto')
                            ->label('Monto a abonar')
                            ->numeric()
                            ->prefix('Bs')
                            ->required()
                            ->minValue(0.01),
                    ])
                    ->fillForm(fn (CuentaPorPagar $record) => ['monto' => $record->saldo_pendiente])
                    ->action(function (CuentaPorPagar $record, array $data) {
                        if ($data['monto'] > $record->saldo_pendiente) {
                            Notification::make()
                                ->title('El monto no puede superar el saldo pendiente')
                                ->body('Saldo pendiente: Bs '.number_format($record->saldo_pendiente, 2))
                                ->danger()
                                ->send();

                            return;
                        }

                        $saldo = $record->saldo_pendiente - $data['monto'];

                        $record->update([
                            'saldo_pendiente' => $saldo,
                            'estado' => $saldo <= 0 ? 'pagado' : 'pendiente',
                        ]);

                        Notification::make()
                            ->title('Pago registrado')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
