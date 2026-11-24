<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\RedirigeALaLista;
use App\Filament\Resources\Users\Actions\InvitarUsuario;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateUser extends CreateRecord
{
    use RedirigeALaLista;

    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'Invitar usuario';
    }

    public function getBreadcrumb(): string
    {
        return 'Invitar';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Enviar invitación');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Usuario creado';
    }

    // Sin contraseña definida, la invitación por WhatsApp sale al crear: no hace falta ir a
    // buscar al usuario en la lista para mandársela.
    protected function afterCreate(): void
    {
        $usuario = $this->getRecord();

        if (blank($usuario->password) && filled($usuario->telefono_whatsapp)) {
            InvitarUsuario::enviar($usuario);
        }
    }
}
