<?php

namespace App\Filament\Resources\Clientes\Tables;

use App\Models\Cliente;
use App\Models\User;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ClientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ci_nit')
                    ->label('CI / NIT')
                    ->searchable(),
                TextColumn::make('telefono_whatsapp')
                    ->label('WhatsApp')
                    ->searchable(),
                TextColumn::make('correo')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('vehiculos_count')
                    ->label('Vehículos')
                    ->counts('vehiculos')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('invitar')
                    ->label('Invitar por WhatsApp')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Cliente $record) => "Se manda un enlace de acceso por WhatsApp al {$record->telefono_whatsapp} para que {$record->nombre} entre a \"Mi garaje\".")
                    ->disabled(fn (Cliente $record) => blank($record->telefono_whatsapp))
                    ->action(function (Cliente $record, WhatsAppService $whatsApp) {
                        if (! $record->user_id) {
                            $user = User::create([
                                'nombre' => $record->nombre,
                                'email' => $record->correo,
                                'telefono_whatsapp' => $record->telefono_whatsapp,
                                'rol' => 'cliente',
                                'activo' => true,
                            ]);
                            $record->update(['user_id' => $user->id]);
                        }

                        $token = Str::random(48);
                        Cache::put("whatsapp_login:{$token}", $record->user_id, now()->addDays(7));

                        $whatsApp->enviarPlantilla(
                            telefono: $record->telefono_whatsapp,
                            plantilla: 'invitacion_cuenta',
                            parametros: [
                                'nombre' => $record->nombre,
                                'link' => config('services.frontend.url')."/auth/whatsapp/{$token}",
                            ],
                            userId: $record->user_id,
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
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
