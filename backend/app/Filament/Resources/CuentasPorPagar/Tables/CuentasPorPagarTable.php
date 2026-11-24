<?php

namespace App\Filament\Resources\CuentasPorPagar\Tables;

use App\Models\CuentaPorPagar;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
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
                    ->weight('semibold')
                    ->description(fn (CuentaPorPagar $record) => $record->compra?->numero_factura
                        ? 'Factura '.$record->compra->numero_factura
                        : 'Sin número de factura')
                    ->searchable(),
                TextColumn::make('compra.fecha')
                    ->label('Fecha de compra')
                    ->date('d/m/Y')
                    ->fontFamily('mono'),
                ViewColumn::make('pagado')
                    ->label('Pagado')
                    ->view('filament.cuentas.col-pagado'),
                TextColumn::make('saldo_pendiente')
                    ->label('Saldo')
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->money('BOB', locale: 'es_BO')
                    ->weight('bold')
                    ->color(fn (CuentaPorPagar $record) => $record->saldo_pendiente > 0 ? 'warning' : 'success')
                    ->sortable(),
                TextColumn::make('fecha_vencimiento')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->fontFamily('mono')
                    ->placeholder('Sin fecha')
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'vencido' => 'danger',
                        'pagado' => 'success',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pendiente' => 'Por pagar',
                        'vencido' => 'Vencida',
                        'pagado' => 'Pagada',
                        default => $state,
                    }),
            ])
            ->defaultSort('created_at', 'desc')
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
