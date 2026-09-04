<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehiculoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vehículo')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('cliente.nombre')->label('Cliente'),
                        TextEntry::make('placa')->label('Placa'),
                        TextEntry::make('marca')->label('Marca'),
                        TextEntry::make('modelo')->label('Modelo'),
                        TextEntry::make('anio')->label('Año'),
                        TextEntry::make('color')->label('Color'),
                        TextEntry::make('motor')->label('Motor')->placeholder('—'),
                        TextEntry::make('kilometraje_actual')->label('Kilometraje')->suffix(' km'),
                    ]),
                Section::make('Fotos subidas (evidencias de sus órdenes de trabajo)')
                    ->schema([
                        RepeatableEntry::make('fotos')
                            ->label('')
                            ->schema([
                                ImageEntry::make('url')
                                    ->label('')
                                    ->height(140)
                                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),
                                TextEntry::make('ordenTrabajo.codigo')->label('OT')->size('xs'),
                                TextEntry::make('tomada_at')->label('Tomada')->dateTime('d/m/Y H:i')->size('xs'),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->fotos()->exists()),
                Section::make('Videos subidos')
                    ->schema([
                        RepeatableEntry::make('videos')
                            ->label('')
                            ->schema([
                                TextEntry::make('url')
                                    ->label('')
                                    ->formatStateUsing(fn () => 'Ver video')
                                    ->url(fn ($record) => $record->url)
                                    ->openUrlInNewTab(),
                                TextEntry::make('ordenTrabajo.codigo')->label('OT')->size('xs'),
                                TextEntry::make('tomada_at')->label('')->dateTime('d/m/Y H:i')->size('xs'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->videos()->exists()),
            ]);
    }
}
