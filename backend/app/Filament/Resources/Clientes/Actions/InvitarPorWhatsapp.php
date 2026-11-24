<?php

namespace App\Filament\Resources\Clientes\Actions;

use App\Models\Cliente;
use App\Models\User;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Manda al cliente el enlace para entrar a "Mi garaje". Se usa en la lista y en la ficha del
 * cliente; la lógica es una sola.
 */
class InvitarPorWhatsapp
{
    public static function make(string $nombre = 'invitar'): Action
    {
        return Action::make($nombre)
            ->label('Invitar por WhatsApp')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Invitar al cliente al portal')
            ->modalDescription(fn (Cliente $record) => "Se manda un enlace de acceso por WhatsApp al {$record->telefono_whatsapp} para que {$record->nombre} entre a \"Mi garaje\".")
            ->disabled(fn (Cliente $record) => blank($record->telefono_whatsapp))
            ->action(function (Cliente $record, WhatsAppService $whatsApp) {
                if (! $record->user_id) {
                    // Puede que ya exista una cuenta con este correo o WhatsApp (ej. el
                    // cliente se autorregistró por Google antes de que el cajero cargara
                    // su ficha) — vincular esa en vez de crear una duplicada, que choca
                    // con la restricción unique y tira un error 500.
                    $user = User::where('telefono_whatsapp', $record->telefono_whatsapp)
                        ->when($record->correo, fn ($query) => $query->orWhere('email', $record->correo))
                        ->first();

                    if (! $user) {
                        $user = User::create([
                            'nombre' => $record->nombre,
                            'email' => $record->correo,
                            'telefono_whatsapp' => $record->telefono_whatsapp,
                            'rol' => 'cliente',
                            'activo' => true,
                        ]);
                    }

                    $record->update(['user_id' => $user->id]);
                }

                $token = Str::random(48);
                Cache::put("whatsapp_login:{$token}", $record->user_id, now()->addDays(7));

                $parametros = [
                    'nombre' => $record->nombre,
                    'link' => config('services.frontend.url')."/auth/whatsapp/{$token}",
                ];

                $notificacion = $whatsApp->enviarPlantilla(
                    telefono: $record->telefono_whatsapp,
                    plantilla: 'invitacion_cuenta_cliente',
                    parametros: $parametros,
                    userId: $record->user_id,
                );

                if ($whatsApp->configurado() && $notificacion->estado === 'enviado') {
                    Notification::make()
                        ->title('Invitación enviada')
                        ->body('Meta aceptó el mensaje y lo está entregando por WhatsApp.')
                        ->success()
                        ->send();

                    return;
                }

                // Con el token configurado pero el envío rechazado (token vencido,
                // plantilla sin aprobar, número inválido...) el cajero no debe quedarse
                // creyendo que salió: se avisa y se le deja el mensaje para mandarlo a mano.
                $rechazado = $whatsApp->configurado();

                $aviso = Notification::make()
                    ->title($rechazado ? 'WhatsApp no pudo enviar la invitación' : 'WhatsApp no está configurado todavía')
                    ->body($rechazado
                        ? 'Meta rechazó el envío (el detalle está en Administración → Mensajes enviados). Abre el mensaje ya redactado y mándalo tú mismo.'
                        : 'Abre el mensaje ya redactado y mándalo tú mismo desde tu WhatsApp.')
                    ->actions([
                        Action::make('abrir')
                            ->label('Abrir en WhatsApp')
                            ->url($whatsApp->linkWaMe($record->telefono_whatsapp, 'invitacion_cuenta_cliente', $parametros))
                            ->openUrlInNewTab(),
                    ])
                    ->persistent();

                $rechazado ? $aviso->danger() : $aviso->warning();
                $aviso->send();
            });
    }
}
