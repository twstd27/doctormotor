<?php

namespace App\Filament\Resources\Clientes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Formulario del cliente en secciones: primero lo imprescindible (quién es y cómo se le
 * escribe) y, aparte, lo que es opcional.
 */
class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->heading(new HtmlString('<span class="dm-num">1</span>Datos del cliente'))
                    ->description('Con esto se identifica al cliente en recibos y facturas.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('nombre')
                                ->label('Nombre completo')
                                ->required()
                                ->autofocus()
                                ->maxLength(150),
                            TextInput::make('ci_nit')
                                ->label('CI / NIT')
                                ->required()
                                ->maxLength(20)
                                ->extraInputAttributes(['class' => 'dm-input-mono']),
                        ]),
                    ]),

                Section::make()
                    ->heading(new HtmlString('<span class="dm-num">2</span>Contacto'))
                    ->description('El WhatsApp es por donde le llegan los avisos del taller.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('telefono_whatsapp')
                                ->label('WhatsApp')
                                ->tel()
                                ->required()
                                ->maxLength(20)
                                ->placeholder('Ej. 70123456')
                                ->extraInputAttributes(['class' => 'dm-input-mono']),
                            TextInput::make('correo')
                                ->label('Correo')
                                ->email()
                                ->maxLength(150)
                                ->placeholder('Opcional'),
                        ]),
                        TextInput::make('direccion')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->placeholder('Opcional'),
                    ]),

                Section::make()
                    ->heading(new HtmlString('<span class="dm-num dm-num--gris">3</span>Notas y portal <span class="dm-opcional">Opcional</span>'))
                    ->collapsible()
                    ->collapsed(fn (string $operation) => $operation === 'create')
                    ->schema([
                        Textarea::make('notas')
                            ->label('Notas')
                            ->rows(3)
                            ->placeholder('Cualquier cosa que convenga recordar de este cliente'),
                        Select::make('user_id')
                            ->label('Cuenta de portal vinculada')
                            ->relationship('user', 'nombre')
                            ->searchable()
                            ->preload()
                            ->helperText('Se vincula sola cuando invitas al cliente por WhatsApp; solo cámbiala si hay que corregirla.'),
                    ]),
            ]);
    }
}
