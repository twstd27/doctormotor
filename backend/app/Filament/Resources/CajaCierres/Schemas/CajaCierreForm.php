<?php

namespace App\Filament\Resources\CajaCierres\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CajaCierreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('monto_apertura')
                    ->label('Monto de apertura')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Bs'),
            ]);
    }
}
