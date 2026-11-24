<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Usuarios del personal: se invita por WhatsApp (el usuario crea su propia contraseña). Los
 * clientes no se crean acá: se manejan desde su ficha.
 */
class UserForm
{
    public const ROLES = [
        'super_admin' => ['nombre' => 'Administrador', 'ayuda' => 'Ve y puede todo: reportes, reparto de utilidades, usuarios y configuración.'],
        'cajero' => ['nombre' => 'Cajero', 'ayuda' => 'Recibe vehículos, cobra, maneja su caja y registra compras y gastos. No ve reportes ni márgenes.'],
        'operador_tecnico' => ['nombre' => 'Técnico', 'ayuda' => 'Trabaja desde el celular: ve solo las órdenes que tiene asignadas, sube fotos y arma presupuestos.'],
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->heading(new HtmlString('<span class="dm-num">1</span>Quién es'))
                    ->description('Le llega un enlace por WhatsApp y crea su propia contraseña.')
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->autofocus()
                            ->maxLength(150),
                        ToggleButtons::make('rol')
                            ->label('Rol')
                            ->options(collect(self::ROLES)->map(fn (array $r) => $r['nombre'])->all())
                            ->inline()
                            ->required()
                            ->live()
                            ->helperText(fn (Get $get) => self::ROLES[$get('rol')]['ayuda'] ?? 'El rol decide qué ve y qué puede hacer.'),
                    ]),

                Section::make()
                    ->heading(new HtmlString('<span class="dm-num dm-num--gris">2</span>Cómo se le contacta'))
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('telefono_whatsapp')
                                ->label('WhatsApp')
                                ->tel()
                                ->prefix('+591')
                                ->maxLength(20)
                                ->required(fn (Get $get) => blank($get('password')))
                                ->extraInputAttributes(['class' => 'dm-input-mono'])
                                ->helperText('Por acá le llega la invitación.'),
                            TextInput::make('email')
                                ->label('Correo')
                                ->email()
                                ->maxLength(150)
                                ->placeholder('Opcional: para recuperar la cuenta'),
                        ]),
                    ]),

                Section::make()
                    ->heading(new HtmlString('<span class="dm-num dm-num--gris">3</span>Acceso <span class="dm-opcional">Opcional</span>'))
                    ->collapsible()
                    ->collapsed(fn (string $operation) => $operation === 'create')
                    ->schema([
                        TextInput::make('password')
                            ->label('Definir la contraseña yo')
                            ->password()
                            ->revealable()
                            ->live(onBlur: true)
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText('Solo si no tiene WhatsApp: crea una contraseña temporal y entrégasela en persona. Al editar, vacío = no cambiarla.'),
                        Toggle::make('activo')
                            ->label('Activo — puede entrar al sistema')
                            ->default(true),
                    ]),
            ]);
    }
}
