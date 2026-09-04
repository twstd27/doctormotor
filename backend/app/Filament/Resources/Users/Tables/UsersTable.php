<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('rol')
                    ->label('Rol')
                    ->colors([
                        'primary' => 'super_admin',
                        'warning' => 'cajero',
                        'info' => 'operador_tecnico',
                        'gray' => 'cliente',
                    ]),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('telefono_whatsapp')
                    ->label('WhatsApp')
                    ->searchable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('password')
                    ->label('Cuenta')
                    ->state(fn (User $record) => $record->password ? 'Activada' : 'Invitación pendiente')
                    ->badge()
                    ->color(fn (User $record) => $record->password ? 'success' : 'warning'),
            ])
            ->defaultSort('nombre')
            ->filters([
                SelectFilter::make('rol')
                    ->label('Rol')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'cajero' => 'Cajero',
                        'operador_tecnico' => 'Operador técnico',
                        'cliente' => 'Cliente',
                    ]),
            ])
            ->recordActions([
                Action::make('invitar')
                    ->label('Enviar invitación')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (User $record) => ! $record->password)
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record) => "Se manda un enlace por WhatsApp al {$record->telefono_whatsapp} para que {$record->nombre} defina su contraseña.")
                    ->disabled(fn (User $record) => blank($record->telefono_whatsapp))
                    ->action(function (User $record, WhatsAppService $whatsApp) {
                        $token = Str::random(48);
                        Cache::put("invitacion_tecnico:{$token}", $record->id, now()->addDays(3));

                        $whatsApp->enviarPlantilla(
                            telefono: $record->telefono_whatsapp,
                            plantilla: 'invitacion_tecnico',
                            parametros: ['link' => config('services.frontend.url')."/invitacion/{$token}"],
                            userId: $record->id,
                        );

                        Notification::make()
                            ->title('Invitación enviada')
                            ->body($whatsApp->configurado()
                                ? 'Se mandó el enlace por WhatsApp.'
                                : 'WhatsApp no está configurado todavía — el enlace quedó en el log de la aplicación.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
