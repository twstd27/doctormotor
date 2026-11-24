<?php

namespace App\Filament\Resources\Users\Actions;

use App\Models\User;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Manda al usuario del personal el enlace para que defina su propia contraseña. Se usa al
 * crear el usuario y como acción de la lista; la lógica es una sola.
 */
class InvitarUsuario
{
    public static function enviar(User $usuario): void
    {
        $whatsApp = app(WhatsAppService::class);

        $token = Str::random(48);
        Cache::put("invitacion_tecnico:{$token}", $usuario->id, now()->addDays(3));

        $parametros = ['link' => config('services.frontend.url')."/invitacion/{$token}"];

        $notificacion = $whatsApp->enviarPlantilla(
            telefono: $usuario->telefono_whatsapp,
            plantilla: 'invitacion_tecnico',
            parametros: $parametros,
            userId: $usuario->id,
        );

        // Modo manual: el mensaje ya quedó armado en «Mensajes → Por enviar»; se ofrece abrirlo de una vez.
        if ($whatsApp->modoManual()) {
            Notification::make()
                ->title('Invitación lista para enviar')
                ->body('Ábrela en WhatsApp y apreta enviar. También queda en Mensajes → Por enviar.')
                ->success()
                ->actions([
                    Action::make('abrir')
                        ->label('Abrir en WhatsApp')
                        ->url(route('mensajes.abrir', $notificacion))
                        ->openUrlInNewTab(),
                ])
                ->persistent()
                ->send();

            return;
        }

        if ($whatsApp->configurado() && $notificacion->estado === 'enviado') {
            Notification::make()
                ->title('Invitación enviada')
                ->body('Meta aceptó el mensaje y lo está entregando por WhatsApp.')
                ->success()
                ->send();

            return;
        }

        // Con el token configurado pero el envío rechazado (token vencido, plantilla sin
        // aprobar, número inválido…) no hay que quedarse creyendo que salió: se avisa y se
        // deja el mensaje redactado para mandarlo a mano.
        $rechazado = $whatsApp->configurado();

        $aviso = Notification::make()
            ->title($rechazado ? 'WhatsApp no pudo enviar la invitación' : 'WhatsApp no está configurado todavía')
            ->body($rechazado
                ? ($notificacion->errorLegible() ?? 'Meta rechazó el envío.').' Abre el mensaje ya redactado y mándalo tú mismo.'
                : 'Abre el mensaje ya redactado y mándalo tú mismo desde tu WhatsApp.')
            ->actions([
                Action::make('abrir')
                    ->label('Abrir en WhatsApp')
                    ->url($whatsApp->linkWaMe($usuario->telefono_whatsapp, 'invitacion_tecnico', $parametros))
                    ->openUrlInNewTab(),
            ])
            ->persistent();

        $rechazado ? $aviso->danger() : $aviso->warning();
        $aviso->send();
    }

    public static function make(): Action
    {
        return Action::make('invitar')
            ->label('Enviar invitación')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->visible(fn (User $record) => ! $record->password)
            ->requiresConfirmation()
            ->modalHeading('Enviar la invitación')
            ->modalDescription(fn (User $record) => "Se manda un enlace por WhatsApp al {$record->telefono_whatsapp} para que {$record->nombre} defina su contraseña.")
            ->disabled(fn (User $record) => blank($record->telefono_whatsapp))
            ->action(fn (User $record) => self::enviar($record));
    }
}
