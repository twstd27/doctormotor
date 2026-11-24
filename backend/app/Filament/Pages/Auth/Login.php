<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

/**
 * Login del panel con el diseño «arranque de motor» (tacómetro a la izquierda, formulario a
 * la derecha; en el celular, hoja inferior). La autenticación sigue siendo la de Filament:
 * solo cambian la vista, el layout y los textos.
 */
class Login extends BaseLogin
{
    protected string $view = 'filament.auth.login';

    protected static string $layout = 'filament.auth.layout';

    public function getTitle(): string
    {
        return 'Entrar';
    }

    public function getHeading(): string
    {
        return 'Entra a tu taller';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Correo electrónico')
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus()
            ->placeholder('tu@taller.bo');
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Contraseña')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required();
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')->label('Mantener la sesión en este equipo');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Arrancar')
            ->icon(Heroicon::OutlinedKey)
            ->extraAttributes(['class' => 'dm-login-go'])
            ->submit('authenticate');
    }
}
