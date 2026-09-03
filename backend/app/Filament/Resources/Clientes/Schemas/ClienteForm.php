<?php

namespace App\Filament\Resources\Clientes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre completo')
                    ->required()
                    ->maxLength(150),
                TextInput::make('ci_nit')
                    ->label('CI / NIT')
                    ->required()
                    ->maxLength(20),
                TextInput::make('telefono_whatsapp')
                    ->label('WhatsApp')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                TextInput::make('correo')
                    ->label('Correo')
                    ->email()
                    ->maxLength(150),
                TextInput::make('direccion')
                    ->label('Dirección')
                    ->maxLength(255),
                Select::make('user_id')
                    ->label('Cuenta de portal vinculada')
                    ->relationship('user', 'nombre')
                    ->searchable()
                    ->preload()
                    ->helperText('Se vincula solo si el cliente ya creó su cuenta.'),
                Textarea::make('notas')
                    ->label('Notas')
                    ->columnSpanFull(),
            ]);
    }
}
