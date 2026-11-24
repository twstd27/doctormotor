<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\Actions\InvitarUsuario;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ViewColumn::make('nombre')
                    ->label('Usuario')
                    ->view('filament.usuarios.col-usuario')
                    ->searchable(['nombre', 'email', 'telefono_whatsapp'])
                    ->sortable(),
                TextColumn::make('rol')
                    ->label('Rol')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'super_admin' => 'primary',
                        'cajero' => 'warning',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'super_admin' => 'Administrador',
                        'cajero' => 'Cajero',
                        'operador_tecnico' => 'Técnico',
                        default => ucfirst($state),
                    }),
                ViewColumn::make('activo')
                    ->label('Estado')
                    ->view('filament.usuarios.col-estado'),
                TextColumn::make('ultimo_login_at')
                    ->label('Último acceso')
                    ->since()
                    ->placeholder('Nunca ha entrado')
                    ->sortable(),
            ])
            ->defaultSort('nombre')
            ->searchPlaceholder('Nombre, correo o WhatsApp')
            ->recordActions([
                InvitarUsuario::make()->hiddenLabel()->tooltip('Enviar invitación por WhatsApp'),
                ActionGroup::make([
                    EditAction::make()->label('Editar'),
                ])->label('Más acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
