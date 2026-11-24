<?php

namespace App\Filament\Resources\Proveedors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ProveedorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->heading(new HtmlString('<span class="dm-num">1</span>Datos del proveedor'))
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('nombre')
                                ->label('Nombre')
                                ->required()
                                ->autofocus()
                                ->maxLength(150),
                            TextInput::make('nit')
                                ->label('NIT')
                                ->placeholder('Opcional')
                                ->extraInputAttributes(['class' => 'dm-input-mono']),
                        ]),
                    ]),

                Section::make()
                    ->heading(new HtmlString('<span class="dm-num dm-num--gris">2</span>Contacto <span class="dm-opcional">Opcional</span>'))
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->extraInputAttributes(['class' => 'dm-input-mono']),
                            TextInput::make('direccion')
                                ->label('Dirección'),
                        ]),
                        Toggle::make('activo')
                            ->label('Activo — disponible al registrar compras')
                            ->default(true),
                    ]),
            ]);
    }
}
