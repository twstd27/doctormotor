<?php

namespace App\Filament\Resources\Vehiculos\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class VehiculoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ficha del vehículo')
                    ->schema([
                        View::make('filament.infolists.vehiculo-ficha')
                            ->viewData(fn ($record) => ['vehiculo' => $record]),
                    ]),
                Section::make('Evidencias fotográficas')
                    ->description(fn ($record) => 'Subidas desde sus órdenes de trabajo · '.$record->fotos()->count().' '.Str::plural('foto', $record->fotos()->count()))
                    ->schema([
                        View::make('filament.infolists.vehiculo-galeria')
                            ->viewData(fn ($record) => [
                                'fotos' => $record->fotos()->with('ordenTrabajo')->latest('tomada_at')->get(),
                                'ordenActiva' => $record->ordenesTrabajo()->whereNotIn('estado', ['entregado', 'cancelado'])->latest('fecha_ingreso')->first(),
                            ]),
                    ]),
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
