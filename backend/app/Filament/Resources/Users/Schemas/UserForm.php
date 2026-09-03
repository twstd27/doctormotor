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
                    ->maxLength(20),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->helperText('Dejar vacío al editar para no cambiar la contraseña actual.'),
                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
