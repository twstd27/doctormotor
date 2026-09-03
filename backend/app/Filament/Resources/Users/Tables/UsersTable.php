<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
