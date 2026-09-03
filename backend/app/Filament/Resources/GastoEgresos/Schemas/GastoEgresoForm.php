<?php

namespace App\Filament\Resources\GastoEgresos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GastoEgresoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('categoria')
                    ->label('Categoría')
                    ->options(['fijo' => 'Fijo', 'variable' => 'Variable'])
                    ->required(),
                TextInput::make('concepto')
                    ->label('Concepto')
                    ->placeholder('Ej. Alquiler, luz/agua, sueldos, insumos químicos')
                    ->required()
                    ->maxLength(150),
                TextInput::make('monto')
                    ->label('Monto (Bs)')
                    ->required()
                    ->numeric()
                    ->prefix('Bs'),
                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->required()
                    ->default(now()),
                TextInput::make('comprobante_url')
                    ->label('Comprobante (URL)')
                    ->url(),
            ]);
    }
}
