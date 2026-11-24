<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

/**
 * "Mi perfil" del panel. Filament asume que el usuario tiene `name`; en este proyecto la
 * columna es `nombre`, y el correo es opcional (hay usuarios que entran por teléfono).
 */
class EditarPerfil extends EditProfile
{
    protected function getNameFormComponent(): Component
    {
        return TextInput::make('nombre')
            ->label('Nombre')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Correo electrónico')
            ->email()
            ->maxLength(255)
            ->unique(ignoreRecord: true);
    }
}
