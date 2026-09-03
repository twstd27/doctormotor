<?php

namespace App\Filament\Resources\Proveedors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProveedorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(150),
                TextInput::make('nit')
                    ->label('NIT'),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel(),
                TextInput::make('direccion')
                    ->label('Dirección'),
                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
