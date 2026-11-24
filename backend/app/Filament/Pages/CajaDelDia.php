<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CajaCierres\CajaCierreResource;
use App\Filament\Resources\GastoEgresos\GastoEgresoResource;
use App\Filament\Resources\Pagos\PagoResource;
use App\Models\CajaArqueo;
use App\Models\CajaCierre;
use App\Models\GastoEgreso;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Caja del día (HANDOFF §4, Caja y Caja-cierre): el estado del turno abierto, sus movimientos
 * y el cierre con arqueo por billetes y monedas.
 */
class CajaDelDia extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Caja';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Caja del día';

    protected static ?string $slug = 'caja-del-dia';

    protected string $view = 'filament.pages.caja-del-dia';

    /** Filtro de la tabla de movimientos: todos, efectivo, qr o tarjeta. */
    public string $filtro = 'todos';

    /** Fondo que se propone dejar para el turno siguiente (el cajero lo puede cambiar). */
    public const FONDO_SUGERIDO = 200;

    public function getTitle(): string|Htmlable
    {
        $abierta = $this->caja() !== null;

        return new HtmlString(
            'Caja del día <span class="dm-estado dm-estado--'.($abierta ? 'en_reparacion' : 'recepcionado').' dm-estado--titulo"><i></i>'
            .($abierta ? 'Abierta' : 'Cerrada').'</span>',
        );
    }

    public function getSubheading(): string
    {
        $caja = $this->caja();

        if (! $caja) {
            return 'No tienes una caja abierta. Ábrela para cobrar en efectivo y registrar gastos.';
        }

        return ucfirst($caja->created_at->translatedFormat('l d/m')).' · abierta a las '
            .$caja->created_at->format('H:i').' por '.$caja->cajero?->nombre;
    }

    /** El turno abierto del usuario (el de otro cajero no se toca desde acá). */
    public function caja(): ?CajaCierre
    {
        return CajaCierre::with('cajero')->where('cajero_id', auth()->id())->where('estado', 'abierta')->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('egreso')
                ->label('Registrar egreso')
                ->icon('heroicon-o-arrow-up-right')
                ->color('gray')
                ->url(fn () => GastoEgresoResource::getUrl('create'))
                ->visible(fn () => $this->caja() !== null),
            Action::make('cobro')
                ->label('Registrar cobro')
                ->icon('heroicon-o-banknotes')
                ->color('gray')
                ->url(fn () => PagoResource::getUrl('create'))
                ->visible(fn () => $this->caja() !== null),
            Action::make('abrir')
                ->label('Abrir caja')
                ->icon('heroicon-o-lock-open')
                ->url(fn () => CajaCierreResource::getUrl('create'))
                ->visible(fn () => $this->caja() === null),
            $this->accionCerrar(),
        ];
    }

    /** Clave de campo para una denominación: 0.5 → d_0_5. */
    private static function campo(float|int $denominacion): string
    {
        return 'd_'.str_replace('.', '_', (string) $denominacion);
    }

    private static function etiquetaDenominacion(float|int $denominacion): string
    {
        return $denominacion < 1 ? 'Bs 0,50' : 'Bs '.(int) $denominacion;
    }

    /** @param array<string, mixed> $datos */
    private static function totalContado(array $datos): float
    {
        return collect(CajaArqueo::DENOMINACIONES)
            ->sum(fn ($den) => $den * max(0, (int) ($datos[self::campo($den)] ?? 0)));
    }

    /** Lo contado hasta ahora en el formulario del cierre, leyendo cada denominación. */
    private static function contadoDesde(Get $get): float
    {
        return (float) collect(CajaArqueo::DENOMINACIONES)
            ->sum(fn ($den) => $den * max(0, (int) $get(self::campo($den))));
    }

    private function accionCerrar(): Action
    {
        return Action::make('cerrar')
            ->label('Cerrar caja')
            ->icon('heroicon-o-lock-closed')
            ->visible(fn () => $this->caja() !== null)
            ->modalWidth('3xl')
            ->modalHeading('Cerrar caja')
            ->modalDescription('Cuenta el efectivo por billete y moneda. El sistema calcula el total.')
            ->modalSubmitActionLabel('Cerrar caja')
            ->schema(function (): array {
                $caja = $this->caja();
                $esperado = $caja?->efectivoEsperado() ?? 0;

                $contadores = collect(CajaArqueo::DENOMINACIONES)->map(function ($den) {
                    $campo = self::campo($den);

                    return TextInput::make($campo)
                        ->label(self::etiquetaDenominacion($den))
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0)
                        ->live(debounce: 300)
                        ->extraInputAttributes(['class' => 'dm-input-mono dm-input-contador'])
                        ->prefixAction(Action::make($campo.'_menos')
                            ->icon('heroicon-m-minus')
                            ->label('Quitar uno')
                            ->action(fn (Get $get, Set $set) => $set($campo, max(0, (int) $get($campo) - 1))))
                        ->suffixAction(Action::make($campo.'_mas')
                            ->icon('heroicon-m-plus')
                            ->label('Agregar uno')
                            ->action(fn (Get $get, Set $set) => $set($campo, (int) $get($campo) + 1)))
                        ->helperText(fn (Get $get) => 'Bs '.number_format($den * max(0, (int) $get($campo)), 2, ',', '.'));
                })->all();

                return [
                    Grid::make(['default' => 1, 'sm' => 2])->schema($contadores),
                    View::make('filament.caja.arqueo-totales')
                        ->viewData(fn (Get $get) => [
                            'esperado' => $esperado,
                            'contado' => self::contadoDesde($get),
                            'qr' => (float) $caja?->pagos()->where('metodo', 'qr')->sum('monto'),
                            'tarjeta' => (float) $caja?->pagos()->where('metodo', 'tarjeta')->sum('monto'),
                        ]),
                    Textarea::make('motivo_diferencia')
                        ->label('¿Por qué no cuadra?')
                        ->placeholder('Ej. se dio vuelto de más a un cliente')
                        ->rows(2)
                        ->helperText('Queda registrado en el historial de la caja con tu nombre.')
                        ->visible(fn (Get $get) => abs(round(self::contadoDesde($get) - $esperado, 2)) > 0.004)
                        ->required(fn (Get $get) => abs(round(self::contadoDesde($get) - $esperado, 2)) > 0.004),
                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                        Toggle::make('dejar_fondo')
                            ->label('Dejar fondo para el turno siguiente')
                            ->default(true)
                            ->live()
                            ->inline(false),
                        TextInput::make('fondo_siguiente')
                            ->label('Monto del fondo')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Bs')
                            ->default(self::FONDO_SUGERIDO)
                            ->extraInputAttributes(['class' => 'dm-input-mono'])
                            ->visible(fn (Get $get) => (bool) $get('dejar_fondo'))
                            ->required(fn (Get $get) => (bool) $get('dejar_fondo'))
                            ->helperText('Se propone Bs '.self::FONDO_SUGERIDO.'; puedes cambiarlo.')
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ((float) $value > self::contadoDesde($get) + 0.004) {
                                        $fail('El fondo no puede ser mayor que el efectivo contado.');
                                    }
                                },
                            ]),
                    ]),
                ];
            })
            ->action(function (array $data) {
                $this->cerrarCaja($data);
            });
    }

    /** @param array<string, mixed> $datos */
    private function cerrarCaja(array $datos): void
    {
        $caja = $this->caja();

        if (! $caja) {
            Notification::make()->title('Esa caja ya estaba cerrada')->warning()->send();

            return;
        }

        $esperado = $caja->efectivoEsperado();
        $contado = self::totalContado($datos);
        $diferencia = round($contado - $esperado, 2);

        DB::transaction(function () use ($caja, $datos, $esperado, $contado, $diferencia) {
            foreach (CajaArqueo::DENOMINACIONES as $den) {
                $cantidad = max(0, (int) ($datos[self::campo($den)] ?? 0));

                if ($cantidad > 0) {
                    $caja->arqueos()->create(['denominacion' => $den, 'cantidad' => $cantidad]);
                }
            }

            $caja->update([
                'monto_esperado' => $esperado,
                'monto_contado' => $contado,
                'diferencia' => $diferencia,
                'motivo_diferencia' => $diferencia == 0 ? null : ($datos['motivo_diferencia'] ?? null),
                'fondo_siguiente' => ! empty($datos['dejar_fondo']) ? (float) ($datos['fondo_siguiente'] ?? 0) : 0,
                'estado' => 'cerrada',
                'cerrado_at' => now(),
            ]);
        });

        Notification::make()
            ->title('Caja cerrada')
            ->body($diferencia == 0
                ? 'La caja cuadra.'
                : ($diferencia > 0
                    ? 'Sobraron Bs '.number_format($diferencia, 2, ',', '.').'.'
                    : 'Faltaron Bs '.number_format(abs($diferencia), 2, ',', '.').'.'))
            ->color($diferencia == 0 ? 'success' : 'warning')
            ->send();

        $this->redirect(static::getUrl());
    }

    /**
     * Movimientos del turno, del más reciente al más antiguo: cobros (de cualquier método),
     * gastos pagados desde caja y la apertura.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function movimientos(?CajaCierre $caja): Collection
    {
        if (! $caja) {
            return collect();
        }

        $movimientos = collect();

        foreach ($caja->pagos()->with(['cliente:id,nombre', 'ordenTrabajo:id,codigo', 'cajero:id,nombre'])->get() as $pago) {
            $movimientos->push([
                'cuando' => $pago->fecha,
                'concepto' => $pago->ordenTrabajo ? 'Cobro '.$pago->ordenTrabajo->codigo : 'Abono a cuenta',
                'detalle' => $pago->cliente?->nombre.' · '.str_replace('_', ' ', $pago->tipo),
                'metodo' => $pago->metodo,
                'por' => $pago->cajero?->nombre,
                'monto' => (float) $pago->monto,
                'signo' => '+',
            ]);
        }

        foreach ($caja->gastos()->where('origen', 'caja')->with('registradoPor:id,nombre')->get() as $gasto) {
            $movimientos->push([
                'cuando' => $gasto->created_at,
                'concepto' => $gasto->concepto,
                'detalle' => GastoEgreso::RUBROS[$gasto->rubro]['nombre'] ?? 'Gasto',
                'metodo' => 'efectivo',
                'por' => $gasto->registradoPor?->nombre,
                'monto' => (float) $gasto->monto,
                'signo' => '−',
            ]);
        }

        $movimientos->push([
            'cuando' => $caja->created_at,
            'concepto' => 'Apertura de caja',
            'detalle' => 'Fondo de cambio',
            'metodo' => 'efectivo',
            'por' => $caja->cajero?->nombre,
            'monto' => (float) $caja->monto_apertura,
            'signo' => '',
        ]);

        return $movimientos->sortByDesc('cuando')->values();
    }

    /** Últimos cierres, para el panel lateral (el super_admin ve los de todos). */
    public function cierresAnteriores(): Collection
    {
        return CajaCierre::query()
            ->with('cajero:id,nombre')
            ->where('estado', 'cerrada')
            ->when(auth()->user()?->rol !== 'super_admin', fn ($q) => $q->where('cajero_id', auth()->id()))
            ->orderByDesc('cerrado_at')
            ->limit(4)
            ->get();
    }
}
