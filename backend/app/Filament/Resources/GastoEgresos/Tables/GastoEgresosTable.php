<?php

namespace App\Filament\Resources\GastoEgresos\Tables;

use App\Filament\Resources\GastoEgresos\GastoEgresoResource;
use App\Models\GastoEgreso;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class GastoEgresosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('registradoPor'))
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('rubro')
                    ->label('Rubro')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state) => GastoEgreso::RUBROS[$state]['nombre'] ?? ucfirst($state)),
                TextColumn::make('concepto')
                    ->label('Concepto')
                    ->searchable(),
                TextColumn::make('origen')
                    ->label('Salió de')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'caja' => 'success',
                        'qr' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'caja' => 'Caja',
                        'qr' => 'QR',
                        default => 'Banco',
                    }),
                TextColumn::make('monto')
                    ->label('Monto')
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->money('BOB', locale: 'es_BO')
                    ->sortable(),
                IconColumn::make('comprobante_path')
                    ->label('Comprobante')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->url(fn (GastoEgreso $record) => $record->comprobante_path
                        ? Storage::disk('public')->url($record->comprobante_path)
                        : null, shouldOpenInNewTab: true),
                TextColumn::make('registradoPor.nombre')
                    ->label('Registrado por'),
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                SelectFilter::make('rubro')
                    ->label('Rubro')
                    ->options(collect(GastoEgreso::RUBROS)->map(fn (array $r) => $r['nombre'])->all()),
                SelectFilter::make('origen')
                    ->label('Salió de')
                    ->options(GastoEgreso::ORIGENES),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (GastoEgreso $record) => GastoEgresoResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
