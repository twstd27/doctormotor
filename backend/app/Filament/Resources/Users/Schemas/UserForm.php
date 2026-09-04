<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(150),
                Select::make('rol')
                    ->label('Rol')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'cajero' => 'Cajero',
                        'operador_tecnico' => 'Operador técnico',
                        'cliente' => 'Cliente',
                    ])
                    ->required(),
                TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->maxLength(150),
                TextInput::make('telefono_whatsapp')
                    ->label('WhatsApp')
                    ->tel()
                    ->maxLength(20)
                    ->helperText('Necesario para poder invitar por WhatsApp si se deja la contraseña vacía.'),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText('Dejar vacío para invitar por WhatsApp (el usuario define su propia contraseña al aceptar), o al editar para no cambiar la actual.'),
                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
