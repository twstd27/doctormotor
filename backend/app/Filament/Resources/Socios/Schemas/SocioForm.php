<?php

namespace App\Filament\Resources\Socios\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SocioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(150),
                TextInput::make('porcentaje_default')
                    ->label('Porcentaje por defecto')
                    ->required()
                    ->numeric()
                    ->suffix('%'),
                Select::make('user_id')
                    ->label('Cuenta de usuario vinculada')
                    ->relationship('user', 'nombre')
                    ->searchable()
                    ->preload()
                    ->helperText('Solo si el socio tiene acceso al sistema (super_admin).'),
                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
