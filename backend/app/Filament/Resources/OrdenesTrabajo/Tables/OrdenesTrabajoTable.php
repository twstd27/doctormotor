<?php

namespace App\Filament\Resources\OrdenesTrabajo\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdenesTrabajoTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vehiculo.placa')
                    ->label('Vehículo')
                    ->formatStateUsing(fn ($record) => "{$record->vehiculo->marca} {$record->vehiculo->modelo} ({$record->vehiculo->placa})")
                    ->searchable(['placa']),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable(),
                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'gray' => ['recepcionado', 'entregado', 'cancelado'],
                        'info' => ['en_diagnostico', 'control_calidad'],
                        'warning' => 'esperando_aprobacion',
                        'success' => ['en_reparacion', 'listo_entrega'],
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'recepcionado' => 'Recepcionado',
                        'en_diagnostico' => 'En diagnóstico',
                        'esperando_aprobacion' => 'Esperando aprobación',
                        'en_reparacion' => 'En reparación',
                        'control_calidad' => 'Control de calidad',
                        'listo_entrega' => 'Listo para entrega',
                        'entregado' => 'Entregado',
                        'cancelado' => 'Cancelado',
                        default => $state,
                    }),
                TextColumn::make('tecnicoAsignado.nombre')
                    ->label('Técnico')
                    ->placeholder('Sin asignar'),
                TextColumn::make('fecha_ingreso')
                    ->label('Ingreso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('fecha_ingreso', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->options([
                        'recepcionado' => 'Recepcionado',
                        'en_diagnostico' => 'En diagnóstico',
                        'esperando_aprobacion' => 'Esperando aprobación',
                        'en_reparacion' => 'En reparación',
                        'control_calidad' => 'Control de calidad',
                        'listo_entrega' => 'Listo para entrega',
                        'entregado' => 'Entregado',
                        'cancelado' => 'Cancelado',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
