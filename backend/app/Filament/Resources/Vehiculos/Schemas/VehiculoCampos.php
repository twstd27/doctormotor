<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use App\Support\CatalogoVehiculos;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Los datos del vehículo, tal como los muestra el diseño («Vehículo nuevo»): placa grande,
 * marca y modelo con sugerencias, año, kilometraje, color en círculos y motor. Se usan en la
 * pantalla de Vehículos y en el panel de la recepción, para que sean una sola cosa.
 */
class VehiculoCampos
{
    /** @return list<\Filament\Schemas\Components\Component> */
    public static function componentes(): array
    {
        return [
            TextInput::make('placa')
                ->label('Placa')
                ->required()
                ->maxLength(15)
                ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state)))
                ->extraInputAttributes(['class' => 'dm-input-placa']),

            Grid::make(['default' => 1, 'sm' => 2])->schema([
                TextInput::make('marca')
                    ->label('Marca')
                    ->required()
                    ->maxLength(50)
                    ->datalist(CatalogoVehiculos::marcas())
                    ->live(onBlur: true),
                TextInput::make('modelo')
                    ->label('Modelo')
                    ->required()
                    ->maxLength(50)
                    ->datalist(fn (Get $get) => CatalogoVehiculos::modelos($get('marca')))
                    ->helperText('Sugerencias según la marca. Puedes escribir uno nuevo.'),
                TextInput::make('anio')
                    ->label('Año')
                    ->required()
                    ->numeric()
                    ->minValue(1950)
                    ->maxValue(2100)
                    ->extraInputAttributes(['class' => 'dm-input-mono']),
                TextInput::make('kilometraje_actual')
                    ->label('Kilometraje actual')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('km')
                    ->extraInputAttributes(['class' => 'dm-input-mono']),
            ]),

            ToggleButtons::make('color')
                ->label('Color')
                ->required()
                ->inline()
                ->live()
                ->extraAttributes(['class' => 'dm-colores'])
                // Si el vehículo ya tenía un color escrito a mano (ej. «Gris plata») se conserva como opción.
                ->options(function ($state) {
                    $colores = array_combine(array_keys(CatalogoVehiculos::COLORES), array_keys(CatalogoVehiculos::COLORES));

                    if (filled($state) && ! isset($colores[$state])) {
                        $colores[$state] = $state;
                    }

                    return $colores;
                })
                ->helperText(fn (Get $get) => $get('color') ?: null),

            TextInput::make('motor')
                ->label('Motor')
                ->maxLength(50)
                ->placeholder('Ej: 2.4 L diésel, 2GD-FTV')
                ->hint('Opcional'),
        ];
    }
}
