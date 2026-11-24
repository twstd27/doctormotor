<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class VehiculoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading(new HtmlString('<span class="dm-num">1</span>Vehículo'))
                    ->description('Lo básico para reconocerlo en el taller.')
                    ->schema(VehiculoCampos::componentes()),

                Section::make()
                    ->heading(new HtmlString('<span class="dm-num">2</span>Dueño'))
                    ->description('Si el dueño todavía no es cliente, regístralo primero desde Clientes.')
                    ->schema([
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'nombre')
                            ->searchable()
                            ->preload()
                            ->placeholder('Buscar por nombre')
                            ->required(),
                    ]),
            ]);
    }
}
