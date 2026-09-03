<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VehiculoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('placa')
                    ->label('Placa')
                    ->required()
                    ->maxLength(15),
                TextInput::make('marca')
                    ->label('Marca')
                    ->required()
                    ->maxLength(50),
                TextInput::make('modelo')
                    ->label('Modelo')
                    ->required()
                    ->maxLength(50),
                TextInput::make('anio')
                    ->label('Año')
                    ->required()
                    ->numeric()
                    ->minValue(1950)
                    ->maxValue(2100),
                TextInput::make('color')
                    ->label('Color')
                    ->required()
                    ->maxLength(30),
                TextInput::make('motor')
                    ->label('Motor')
                    ->maxLength(50),
                TextInput::make('kilometraje_actual')
                    ->label('Kilometraje actual')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('km'),
            ]);
    }
}
