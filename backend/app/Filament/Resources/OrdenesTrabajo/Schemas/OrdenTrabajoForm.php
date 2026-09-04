<?php

namespace App\Filament\Resources\OrdenesTrabajo\Schemas;

use App\Models\User;
use App\Models\Vehiculo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrdenTrabajoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->disabled(fn (string $operation) => $operation === 'edit')
                    ->afterStateUpdated(fn (Set $set) => $set('vehiculo_id', null))
                    ->createOptionForm([
                        TextInput::make('nombre')->label('Nombre completo')->required()->maxLength(150),
                        TextInput::make('ci_nit')->label('CI / NIT')->required()->maxLength(20),
                        TextInput::make('telefono_whatsapp')->label('WhatsApp')->tel()->required()->maxLength(20),
                        TextInput::make('correo')->label('Correo')->email()->maxLength(150),
                    ]),
                Select::make('vehiculo_id')
                    ->label('Vehículo')
                    ->options(
                        fn (Get $get) => $get('cliente_id')
                            ? Vehiculo::where('cliente_id', $get('cliente_id'))
                                ->get()
                                ->mapWithKeys(fn (Vehiculo $v) => [$v->id => "{$v->placa} — {$v->marca} {$v->modelo}"])
                            : [],
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (Get $get, string $operation) => $operation === 'edit' || blank($get('cliente_id')))
                    ->helperText('Elegí primero el cliente.')
                    ->createOptionForm([
                        TextInput::make('placa')->label('Placa')->required()->maxLength(15),
                        TextInput::make('marca')->label('Marca')->required()->maxLength(50),
                        TextInput::make('modelo')->label('Modelo')->required()->maxLength(50),
                        TextInput::make('anio')->label('Año')->required()->numeric()->minValue(1950)->maxValue(2100),
                        TextInput::make('color')->label('Color')->required()->maxLength(30),
                        TextInput::make('motor')->label('Motor')->maxLength(50),
                        TextInput::make('kilometraje_actual')->label('Kilometraje actual')->required()->numeric()->minValue(0),
                    ])
                    ->createOptionUsing(function (array $data, Get $get) {
                        return Vehiculo::create([...$data, 'cliente_id' => $get('cliente_id')])->id;
                    }),
                Textarea::make('descripcion_problema')
                    ->label('Descripción del problema')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('kilometraje_ingreso')
                    ->label('Kilometraje de ingreso')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix('km'),
                Select::make('nivel_gasolina')
                    ->label('Nivel de gasolina')
                    ->options(['E' => 'Vacío', '1/4' => '1/4', '1/2' => '1/2', '3/4' => '3/4', 'F' => 'Lleno'])
                    ->required(),
                Select::make('tecnico_asignado_id')
                    ->label('Técnico asignado')
                    ->options(fn () => User::where('rol', 'operador_tecnico')->pluck('nombre', 'id'))
                    ->searchable()
                    ->helperText('Se puede asignar después desde el tablero.'),
                DatePicker::make('fecha_entrega_estimada')
                    ->label('Entrega estimada'),
            ]);
    }
}
