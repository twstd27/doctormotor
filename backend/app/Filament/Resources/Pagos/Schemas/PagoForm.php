<?php

namespace App\Filament\Resources\Pagos\Schemas;

use App\Models\CajaCierre;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
 * Formulario de cobro (HANDOFF §4, Registrar cobro): a quién se cobra, cuánto y cómo, a la
 * izquierda; el estado de cuenta del cliente, a la derecha.
 */
class PagoForm
{
    private static function bs(float $monto): string
    {
        return 'Bs '.number_format($monto, 2, ',', '.');
    }

    /** Lo que todavía se le puede cobrar: el saldo de la orden elegida o, si no hay, el del cliente. */
    private static function saldoBase(Get $get): float
    {
        if (filled($get('orden_trabajo_id'))) {
            return max(0, (float) (OrdenTrabajo::find($get('orden_trabajo_id'))?->saldoPendiente() ?? 0));
        }

        if (filled($get('cliente_id'))) {
            return max(0, (float) (Cliente::find($get('cliente_id'))?->saldoPendiente() ?? 0));
        }

        return 0;
    }

    private static function titulo(string $texto): HtmlString
    {
        return new HtmlString(e($texto));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])->schema([
                    Group::make([
                        // ── ¿A quién le cobras? ──────────────────────────────
                        Section::make()
                            ->heading(self::titulo('¿A quién le cobras?'))
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 2])->schema([
                                    Select::make('cliente_id')
                                        ->label('Cliente')
                                        ->relationship('cliente', 'nombre')
                                        ->searchable(['nombre', 'ci_nit', 'telefono_whatsapp'])
                                        ->preload()
                                        ->live()
                                        ->required()
                                        // La ficha de la orden abre este formulario con cliente y orden ya elegidos.
                                        ->default(fn () => request()->integer('cliente_id') ?: null)
                                        ->afterStateUpdated(function (Set $set) {
                                            $set('orden_trabajo_id', null);
                                            $set('monto', null);
                                        }),
                                    Select::make('orden_trabajo_id')
                                        ->label('Orden de trabajo')
                                        ->options(
                                            fn (Get $get) => $get('cliente_id')
                                                ? OrdenTrabajo::query()
                                                    ->conSaldo()
                                                    ->where('cliente_id', $get('cliente_id'))
                                                    ->orderByDesc('fecha_ingreso')
                                                    ->get()
                                                    ->mapWithKeys(fn (OrdenTrabajo $o) => [
                                                        $o->id => $o->codigo.' · '.($o->saldoPendiente() > 0.009
                                                            ? 'saldo '.self::bs($o->saldoPendiente())
                                                            : 'sin saldo'),
                                                    ])
                                                : [],
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->default(fn () => request()->integer('orden_trabajo_id') ?: null)
                                        ->disabled(fn (Get $get) => blank($get('cliente_id')))
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            // Lo más común es cobrar lo que falta: se propone ese monto.
                                            $saldo = self::saldoBase($get);
                                            $set('monto', $saldo > 0.009 ? round($saldo, 2) : null);
                                        })
                                        ->helperText('Vacío = abono a la cuenta del cliente.'),
                                ]),
                                ToggleButtons::make('tipo')
                                    ->label('Tipo de cobro')
                                    ->options([
                                        'anticipo' => 'Anticipo',
                                        'parcial' => 'Pago parcial',
                                        'completo' => 'Pago completo',
                                        'abono_deuda' => 'Abono a deuda',
                                    ])
                                    ->inline()
                                    ->required(),
                            ]),

                        // ── Monto y forma de pago ────────────────────────────
                        Section::make()
                            ->heading(self::titulo('Monto y forma de pago'))
                            ->schema([
                                TextInput::make('monto')
                                    ->label('Monto')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->prefix('Bs')
                                    // Desde la ficha de una orden se propone cobrar lo que falta.
                                    ->default(function () {
                                        $orden = request()->integer('orden_trabajo_id')
                                            ? OrdenTrabajo::find(request()->integer('orden_trabajo_id'))
                                            : null;
                                        $saldo = $orden?->saldoPendiente() ?? 0;

                                        return $saldo > 0.009 ? round($saldo, 2) : null;
                                    })
                                    ->live(debounce: 400)
                                    ->extraInputAttributes(['class' => 'dm-input-monto'])
                                    ->extraFieldWrapperAttributes(['class' => 'dm-campo-monto']),
                                Actions::make([
                                    Action::make('montoTotal')
                                        ->label(fn (Get $get) => 'Saldo total · '.self::bs(self::saldoBase($get)))
                                        ->color('gray')
                                        ->size('sm')
                                        ->visible(fn (Get $get) => self::saldoBase($get) > 0.009)
                                        ->action(fn (Get $get, Set $set) => $set('monto', round(self::saldoBase($get), 2))),
                                    Action::make('montoMitad')
                                        ->label(fn (Get $get) => '50% · '.self::bs(self::saldoBase($get) / 2))
                                        ->color('gray')
                                        ->size('sm')
                                        ->visible(fn (Get $get) => self::saldoBase($get) > 0.009)
                                        ->action(fn (Get $get, Set $set) => $set('monto', round(self::saldoBase($get) / 2, 2))),
                                ]),
                                ToggleButtons::make('metodo')
                                    ->label('Método de pago')
                                    ->options(['efectivo' => 'Efectivo', 'qr' => 'QR', 'tarjeta' => 'Tarjeta'])
                                    ->icons([
                                        'efectivo' => 'heroicon-o-banknotes',
                                        'qr' => 'heroicon-o-qr-code',
                                        'tarjeta' => 'heroicon-o-credit-card',
                                    ])
                                    ->inline()
                                    ->live()
                                    ->required()
                                    ->helperText(fn (Get $get) => match ($get('metodo')) {
                                        'efectivo' => CajaCierre::abiertaDe((int) auth()->id())
                                            ? 'Entra a tu caja abierta.'
                                            : 'Necesitas una caja abierta para cobrar en efectivo.',
                                        'qr', 'tarjeta' => 'No entra a la caja: queda registrado por fuera del efectivo.',
                                        default => null,
                                    }),
                                TextInput::make('referencia_externa')
                                    ->label('N.º de operación')
                                    ->maxLength(100)
                                    ->placeholder('Ej. 004512889')
                                    ->extraInputAttributes(['class' => 'dm-input-mono'])
                                    ->helperText('Copia el número del comprobante para poder cuadrar después.')
                                    ->visible(fn (Get $get) => in_array($get('metodo'), ['qr', 'tarjeta'], true))
                                    ->required(fn (Get $get) => in_array($get('metodo'), ['qr', 'tarjeta'], true)),
                            ]),

                        // ── Comprobante ──────────────────────────────────────
                        Section::make()
                            ->heading(self::titulo('Comprobante'))
                            ->schema([
                                ToggleButtons::make('tipo_documento')
                                    ->label('Documento')
                                    ->options(['recibo' => 'Recibo', 'factura' => 'Factura'])
                                    ->disableOptionWhen(fn (string $value) => $value === 'factura')
                                    ->default('recibo')
                                    ->inline()
                                    ->required()
                                    ->helperText('La facturación electrónica todavía no está configurada: por ahora se emite recibo.'),
                                ToggleButtons::make('detalle_tipo')
                                    ->label('Detalle en el recibo')
                                    ->options([
                                        'resumen' => 'Resumen (lo escribo yo)',
                                        'detallado' => 'Detalle de la orden',
                                    ])
                                    ->default('resumen')
                                    ->inline()
                                    ->live()
                                    ->required()
                                    ->helperText(fn (Get $get) => $get('detalle_tipo') === 'detallado' && blank($get('orden_trabajo_id'))
                                        ? 'Elige una orden de trabajo arriba para armar el detalle desde su presupuesto aprobado.'
                                        : null),
                                Textarea::make('detalle_servicio')
                                    ->label('Resumen del servicio')
                                    ->placeholder('Ej. Cambio de aceite y filtro, revisión de frenos')
                                    ->rows(2)
                                    ->visible(fn (Get $get) => $get('detalle_tipo') !== 'detallado')
                                    ->maxLength(500),
                            ]),
                    ])->columnSpan(['xl' => 2]),

                    Group::make([
                        View::make('filament.pagos.estado-cuenta')
                            ->viewData(fn (Get $get) => [
                                'cliente' => filled($get('cliente_id')) ? Cliente::find($get('cliente_id')) : null,
                                'orden' => filled($get('orden_trabajo_id')) ? OrdenTrabajo::find($get('orden_trabajo_id')) : null,
                                'monto' => (float) ($get('monto') ?: 0),
                                'metodo' => $get('metodo'),
                            ]),
                    ])->columnSpan(['xl' => 1]),
                ]),
            ]);
    }
}
