<?php

namespace App\Filament\Resources\Notificaciones\Tables;

use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Models\Notificacion;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotificacionesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user:id,nombre', 'ordenTrabajo:id,codigo']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->description(fn (Notificacion $record) => $record->created_at?->format('H:i'))
                    ->fontFamily('mono')
                    ->sortable(),
                TextColumn::make('user.nombre')
                    ->label('Para')
                    ->description(fn (Notificacion $record) => $record->telefono_destino)
                    ->placeholder('—')
                    ->searchable(['telefono_destino']),
                ViewColumn::make('plantilla')
                    ->label('Mensaje')
                    ->view('filament.notificaciones.col-mensaje')
                    ->searchable(),
                TextColumn::make('ordenTrabajo.codigo')
                    ->label('Orden')
                    ->fontFamily('mono')
                    ->weight('semibold')
                    ->placeholder('—')
                    ->url(fn (Notificacion $record) => $record->orden_trabajo_id
                        ? OrdenTrabajoResource::getUrl('view', ['record' => $record->orden_trabajo_id])
                        : null),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'enviado' => 'success',
                        'fallido' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'enviado' => 'Enviado',
                        'fallido' => 'Fallido',
                        'pendiente' => 'Pendiente',
                        default => $state,
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Destinatario, teléfono o mensaje')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'enviado' => 'Enviado',
                        'fallido' => 'Fallido',
                    ]),
            ])
            ->recordActions([
                Action::make('reintentar')
                    ->label('Reintentar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn (Notificacion $record) => $record->estado === 'fallido')
                    ->requiresConfirmation()
                    ->modalHeading('Reintentar el envío')
                    ->modalDescription('Se vuelve a mandar el mismo mensaje por WhatsApp.')
                    ->action(function (Notificacion $record, WhatsAppService $whatsApp) {
                        $whatsApp->despachar($record);
                        $record->refresh();

                        $record->estado === 'enviado'
                            ? Notification::make()->title('Mensaje enviado')->success()->send()
                            : Notification::make()
                                ->title('Sigue sin poder enviarse')
                                ->body($record->errorLegible() ?? 'Revisa la conexión con WhatsApp.')
                                ->danger()
                                ->send();
                    }),
            ]);
    }
}
