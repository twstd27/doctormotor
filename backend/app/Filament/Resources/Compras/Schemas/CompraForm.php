<?php

namespace App\Filament\Resources\Compras\Schemas;

use App\Models\Producto;
use App\Models\Proveedor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CompraForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('proveedor_id')
                    ->label('Proveedor')
                    ->options(fn () => Proveedor::where('activo', true)->pluck('nombre', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('numero_factura')
                    ->label('Número de factura')
                    ->maxLength(50),
                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->default(now())
                    ->required(),
                Repeater::make('items')
                    ->label('Ítems comprados')
                    ->schema([
                        Select::make('producto_id')
                            ->label('Producto')
                            ->options(fn () => Producto::where('activo', true)->pluck('nombre', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $precio = Producto::find($state)?->precio_compra_promedio;
                                if ($precio) {
                                    $set('precio_unitario', (float) $precio);
                                }
                            })
                            ->columnSpan(2),
                        TextInput::make('cantidad')
                            ->label('Cantidad')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->default(1)
                            ->required()
                            ->live(),
                        TextInput::make('precio_unitario')
                            ->label('Precio unitario')
                            ->numeric()
                            ->prefix('Bs')
                            ->minValue(0)
                            ->required()
                            ->live(),
                    ])
                    ->columns(4)
                    ->addActionLabel('Agregar ítem')
                    ->live()
                    ->columnSpanFull()
                    ->minItems(1)
                    ->required(),
                TextInput::make('total_calculado')
                    ->label('Total')
                    ->prefix('Bs')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn (Get $get) => number_format(
                        collect($get('items') ?? [])->sum(fn ($item) => (float) ($item['cantidad'] ?? 0) * (float) ($item['precio_unitario'] ?? 0)),
                        2
                    )),
            ]);
    }
}
