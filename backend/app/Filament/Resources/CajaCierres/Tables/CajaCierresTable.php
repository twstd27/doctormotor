<?php

namespace App\Filament\Resources\CajaCierres\Tables;

use App\Models\CajaCierre;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                    ->money('BOB'),
                TextColumn::make('monto_esperado')
                    ->label('Esperado')
                    ->money('BOB')
                    ->placeholder('—'),
                TextColumn::make('monto_contado')
                    ->label('Contado')
                    ->money('BOB')
                    ->placeholder('—'),
                TextColumn::make('diferencia')
                    ->label('Diferencia')
                    ->money('BOB')
                    ->placeholder('—')
                    ->color(fn ($state) => $state === null ? 'gray' : ($state < 0 ? 'danger' : ($state > 0 ? 'warning' : 'success'))),
                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors(['success' => 'abierta', 'gray' => 'cerrada'])
                    ->formatStateUsing(fn (string $state) => $state === 'abierta' ? 'Abierta' : 'Cerrada'),
            ])
            ->defaultSort('fecha', 'desc')
            ->recordActions([
                Action::make('cerrar')
                    ->label('Cerrar caja')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (CajaCierre $record) => $record->estado === 'abierta')
                    ->schema([
                        TextInput::make('monto_contado')
                            ->label('Monto contado en efectivo')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Bs'),
                    ])
                    ->action(function (CajaCierre $record, array $data) {
                        $montoEsperado = $record->monto_apertura + $record->pagos()->where('metodo', 'efectivo')->sum('monto');
                        $diferencia = $data['monto_contado'] - $montoEsperado;

                        $record->update([
                            'monto_esperado' => $montoEsperado,
                            'monto_contado' => $data['monto_contado'],
                            'diferencia' => $diferencia,
                            'estado' => 'cerrada',
                            'cerrado_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Caja cerrada')
                            ->body($diferencia == 0
                                ? 'Cuadró exacto.'
                                : ($diferencia > 0 ? "Sobraron Bs {$diferencia}." : 'Faltaron Bs '.abs($diferencia).'.'))
                            ->color($diferencia == 0 ? 'success' : 'warning')
                            ->send();
                    }),
            ]);
    }
}
