<?php

namespace App\Filament\Resources\Productos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(50),
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(150),
                TextInput::make('categoria')
                    ->label('Categoría')
                    ->placeholder('Ej. Aceites, Filtros'),
                TextInput::make('unidad_medida')
                    ->label('Unidad de medida')
                    ->placeholder('Ej. unidad, litro')
                    ->required(),
                TextInput::make('stock_minimo')
                    ->label('Stock mínimo')
                    ->numeric()
                    ->default(0)
                    ->helperText('Genera alerta cuando el stock cae por debajo de este número.'),
                TextInput::make('precio_venta')
                    ->label('Precio de venta (Bs)')
                    ->numeric()
                    ->default(0)
                    ->prefix('Bs'),
                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
