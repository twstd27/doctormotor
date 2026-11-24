<?php

namespace App\Filament\Resources\GastoEgresos\Schemas;

use App\Models\CajaCierre;
use App\Models\GastoEgreso;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Registrar gasto (HANDOFF §4, Gasto-crear): en qué se gastó, cuánto y de dónde salió la
 * plata. Si sale de la caja, descuenta del efectivo esperado del turno abierto.
 */
class GastoEgresoForm
{
    private static function bs(float $monto): string
    {
        return 'Bs '.number_format($monto, 2, ',', '.');
    }

    /** Un gasto ya cargado en una caja cerrada no cambia de monto ni de origen: rompería el arqueo. */
    private static function bloqueado(?GastoEgreso $record): bool
    {
        return $record?->cajaCierre?->estado === 'cerrada';
    }

    public static function configure(Schema $schema): Schema
    {
        $rubros = collect(GastoEgreso::RUBROS);

        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])->schema([
                    Group::make([
                        Section::make()
                            ->heading(new HtmlString('¿En qué se gastó?'))
                            ->schema([
                                ToggleButtons::make('rubro')
                                    ->label('Rubro')
                                    ->options($rubros->map(fn (array $r) => $r['nombre'])->all())
                                    ->inline()
                                    ->required()
                                    ->live()
                                    ->helperText(fn (Get $get) => GastoEgreso::RUBROS[$get('rubro')]['ayuda'] ?? 'Elige el rubro que mejor describe el gasto.'),
                                TextInput::make('concepto')
                                    ->label('Concepto')
                                    ->placeholder('Ej. Compra de lijas y masilla')
                                    ->required()
                                    ->maxLength(150),
                            ]),

                        Section::make()
                            ->heading(new HtmlString('Monto y pago'))
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 2])->schema([
                                    TextInput::make('monto')
                                        ->label('Monto')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0.01)
                                        ->prefix('Bs')
                                        ->live(debounce: 400)
                                        ->extraInputAttributes(['class' => 'dm-input-monto'])
                                        ->disabled(fn (?GastoEgreso $record) => self::bloqueado($record)),
                                    Group::make([
                                        DatePicker::make('fecha')
                                            ->label('Fecha')
                                            ->required()
                                            ->default(today())
                                            ->maxDate(today())
                                            ->live(),
                                        Actions::make([
                                            Action::make('fechaHoy')->label('Hoy')->color('gray')->size('sm')
                                                ->action(fn (Set $set) => $set('fecha', today()->toDateString())),
                                            Action::make('fechaAyer')->label('Ayer')->color('gray')->size('sm')
                                                ->action(fn (Set $set) => $set('fecha', today()->subDay()->toDateString())),
                                        ]),
                                    ]),
                                ]),
                                ToggleButtons::make('origen')
                                    ->label('¿De dónde salió el dinero?')
                                    ->options(GastoEgreso::ORIGENES)
                                    ->inline()
                                    ->required()
                                    ->live()
                                    ->default(fn () => auth()->user()?->rol === 'cajero' ? 'caja' : 'banco')
                                    ->disabled(fn (?GastoEgreso $record) => self::bloqueado($record))
                                    ->helperText(function (Get $get) {
                                        return match ($get('origen')) {
                                            'caja' => ($caja = CajaCierre::abiertaDe((int) auth()->id()))
                                                ? 'Se descuenta del efectivo de la caja abierta: quedarán '
                                                    .self::bs($caja->efectivoEsperado() - (float) ($get('monto') ?: 0)).' esperados.'
                                                : 'Necesitas una caja abierta para registrar un gasto en efectivo.',
                                            'banco', 'qr' => 'No afecta el efectivo de la caja.',
                                            default => null,
                                        };
                                    }),
                            ]),

                        Section::make()
                            ->heading(new HtmlString('Comprobante <span class="dm-opcional">Opcional</span>'))
                            ->schema([
                                FileUpload::make('comprobante_path')
                                    ->label('Foto de la factura o recibo')
                                    ->disk('public')
                                    ->directory('comprobantes-gastos')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                                    ->maxSize(5120)
                                    ->openable()
                                    ->downloadable()
                                    ->helperText('JPG, PNG o PDF de hasta 5 MB.'),
                            ]),
                    ])->columnSpan(['xl' => 2]),

                    Group::make([
                        View::make('filament.gastos.resumen-mes')
                            ->viewData(fn (Get $get) => [
                                'monto' => (float) ($get('monto') ?: 0),
                                'rubro' => $get('rubro'),
                            ]),
                    ])->columnSpan(['xl' => 1]),
                ]),
            ]);
    }
}
