<?php

namespace App\Filament\Resources\CajaCierres\Schemas;

use App\Models\CajaCierre;
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
                    ->prefix('Bs')
                    // Se propone el fondo que dejó el último cierre de este cajero (o Bs 200); se puede cambiar.
                    ->default(fn () => CajaCierre::where('cajero_id', auth()->id())
                        ->where('estado', 'cerrada')
                        ->whereNotNull('fondo_siguiente')
                        ->latest('cerrado_at')
                        ->value('fondo_siguiente') ?? 200)
                    ->helperText('Efectivo con el que empiezas el turno (fondo de cambio).')
                    ->extraInputAttributes(['class' => 'dm-input-monto']),
            ]);
    }
}
