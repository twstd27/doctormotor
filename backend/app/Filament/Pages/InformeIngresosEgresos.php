<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\SoloSuperAdmin;
use App\Models\CostoDirecto;
use App\Models\GastoEgreso;
use App\Models\Pago;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InformeIngresosEgresos extends Page
{
    use SoloSuperAdmin;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Ingresos y egresos';

    protected static ?string $title = 'Ingresos y egresos';

    protected string $view = 'filament.pages.informe-ingresos-egresos';

    public string $desde;

    public string $hasta;

    public function mount(): void
    {
        $this->desde = now()->startOfMonth()->toDateString();
        $this->hasta = now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportar')
                ->label('Exportar CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => route('informe-ingresos-egresos.csv', ['desde' => $this->desde, 'hasta' => $this->hasta]))
                ->openUrlInNewTab(),
        ];
    }

    public function getSubheading(): string
    {
        $desde = Carbon::parse($this->desde)->translatedFormat('j \d\e F');
        $hasta = Carbon::parse($this->hasta)->translatedFormat('j \d\e F \d\e Y');
        $dias = $this->diasConMovimientos();

        return "{$desde} al {$hasta} · {$dias} ".($dias === 1 ? 'día con movimiento' : 'días con movimiento');
    }

    // ── Rangos rápidos ───────────────────────────────────────────────────────

    public function aplicarRango(string $tipo): void
    {
        match ($tipo) {
            'hoy' => [$this->desde, $this->hasta] = [now()->toDateString(), now()->toDateString()],
            '7dias' => [$this->desde, $this->hasta] = [now()->subDays(6)->toDateString(), now()->toDateString()],
            'estemes' => [$this->desde, $this->hasta] = [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'mesanterior' => [$this->desde, $this->hasta] = [
                now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'ano' => [$this->desde, $this->hasta] = [now()->startOfYear()->toDateString(), now()->toDateString()],
            default => null,
        };
    }

    public function rangoActivo(): ?string
    {
        $hoy = now()->toDateString();

        return match (true) {
            $this->desde === $hoy && $this->hasta === $hoy => 'hoy',
            $this->desde === now()->subDays(6)->toDateString() && $this->hasta === $hoy => '7dias',
            $this->desde === now()->startOfMonth()->toDateString() && $this->hasta === $hoy => 'estemes',
            $this->desde === now()->subMonthNoOverflow()->startOfMonth()->toDateString()
                && $this->hasta === now()->subMonthNoOverflow()->endOfMonth()->toDateString() => 'mesanterior',
            $this->desde === now()->startOfYear()->toDateString() && $this->hasta === $hoy => 'ano',
            default => null,
        };
    }

    // ── Totales ──────────────────────────────────────────────────────────────

    private function pagos()
    {
        return Pago::whereBetween('fecha', [$this->desde, $this->hasta.' 23:59:59']);
    }

    private function costos()
    {
        return CostoDirecto::whereBetween('created_at', [$this->desde, $this->hasta.' 23:59:59']);
    }

    private function gastos()
    {
        return GastoEgreso::whereBetween('fecha', [$this->desde, $this->hasta]);
    }

    public function totalIngresos(): float
    {
        return (float) $this->pagos()->sum('monto');
    }

    public function totalCostosDirectos(): float
    {
        return (float) $this->costos()->sum('costo_total');
    }

    public function totalGastos(): float
    {
        return (float) $this->gastos()->sum('monto');
    }

    /** Ingresos − costos directos − gastos: la misma utilidad que usa el reparto entre socios. */
    public function utilidad(): float
    {
        return $this->totalIngresos() - $this->totalCostosDirectos() - $this->totalGastos();
    }

    public function margenPorcentaje(): ?float
    {
        $ingresos = $this->totalIngresos();

        return $ingresos > 0 ? ($this->utilidad() / $ingresos) * 100 : null;
    }

    // ── Desgloses ────────────────────────────────────────────────────────────

    /** @return array<string, float> */
    public function ingresosPorMetodo(): array
    {
        $porMetodo = $this->pagos()->selectRaw('metodo, SUM(monto) as total')->groupBy('metodo')->pluck('total', 'metodo');

        return [
            'Efectivo' => (float) ($porMetodo['efectivo'] ?? 0),
            'QR' => (float) ($porMetodo['qr'] ?? 0),
            'Tarjeta' => (float) ($porMetodo['tarjeta'] ?? 0),
        ];
    }

    /** @return array<string, float> */
    public function costosPorTipo(): array
    {
        $porTipo = $this->costos()->selectRaw('tipo, SUM(costo_total) as total')->groupBy('tipo')->pluck('total', 'tipo');

        return [
            'Repuestos' => (float) ($porTipo['repuesto'] ?? 0),
            'Mano de obra' => (float) ($porTipo['mano_obra'] ?? 0),
            'Terceros' => (float) ($porTipo['tercerizado'] ?? 0),
        ];
    }

    /** @return array<string, float> los rubros con gasto, del mayor al menor */
    public function gastosPorRubro(): array
    {
        return $this->gastos()
            ->selectRaw('rubro, SUM(monto) as total')
            ->groupBy('rubro')
            ->pluck('total', 'rubro')
            ->mapWithKeys(fn ($total, $rubro) => [GastoEgreso::RUBROS[$rubro]['nombre'] ?? ucfirst((string) $rubro) => (float) $total])
            ->sortDesc()
            ->all();
    }

    /**
     * Una fila por día con movimiento (más reciente primero).
     *
     * @return array<int, array{fecha: string, ingresos: float, costos: float, gastos: float, neto: float}>
     */
    public function detalleDiario(): array
    {
        $ingresos = $this->pagos()->selectRaw('DATE(fecha) as dia, SUM(monto) as total')->groupBy('dia')->pluck('total', 'dia');
        $costos = $this->costos()->selectRaw('DATE(created_at) as dia, SUM(costo_total) as total')->groupBy('dia')->pluck('total', 'dia');
        $gastos = $this->gastos()->selectRaw('fecha as dia, SUM(monto) as total')->groupBy('dia')->pluck('total', 'dia');

        return collect($ingresos->keys())->merge($costos->keys())->merge($gastos->keys())
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->sortDesc()
            ->map(fn (string $fecha) => [
                'fecha' => $fecha,
                'ingresos' => (float) ($ingresos[$fecha] ?? 0),
                'costos' => (float) ($costos[$fecha] ?? 0),
                'gastos' => (float) ($gastos[$fecha] ?? 0),
                'neto' => (float) ($ingresos[$fecha] ?? 0) - (float) ($costos[$fecha] ?? 0) - (float) ($gastos[$fecha] ?? 0),
            ])
            ->values()
            ->all();
    }

    public function diasConMovimientos(): int
    {
        return count($this->detalleDiario());
    }

    /**
     * Serie para el gráfico: un grupo de barras por día (o por mes si el rango es largo).
     *
     * @return Collection<int, array{etiqueta: string, titulo: string, ingresos: float, egresos: float}>
     */
    public function serie(): Collection
    {
        $desde = Carbon::parse($this->desde);
        $hasta = Carbon::parse($this->hasta);
        $porMes = $desde->diffInDays($hasta) > 62;

        $clave = fn (string $fecha) => $porMes ? Carbon::parse($fecha)->format('Y-m') : Carbon::parse($fecha)->toDateString();

        $acumulado = collect($this->detalleDiario())->groupBy(fn ($d) => $clave($d['fecha']))->map(fn ($dias) => [
            'ingresos' => $dias->sum('ingresos'),
            'egresos' => $dias->sum('costos') + $dias->sum('gastos'),
        ]);

        $periodos = collect();
        $cursor = $porMes ? $desde->copy()->startOfMonth() : $desde->copy();
        while ($cursor->lte($hasta)) {
            $k = $porMes ? $cursor->format('Y-m') : $cursor->toDateString();
            $periodos->push([
                'etiqueta' => $porMes ? $cursor->translatedFormat('M') : (string) $cursor->day,
                'titulo' => $porMes ? ucfirst($cursor->translatedFormat('F Y')) : ucfirst($cursor->translatedFormat('l j F')),
                'ingresos' => (float) ($acumulado[$k]['ingresos'] ?? 0),
                'egresos' => (float) ($acumulado[$k]['egresos'] ?? 0),
            ]);
            $porMes ? $cursor->addMonth() : $cursor->addDay();
        }

        return $periodos;
    }

    /**
     * Desglose día por día (ingresos contra gastos) que usa la exportación a CSV.
     *
     * @return array<int, array{fecha: string, ingresos: float, egresos: float, resultado: float}>
     */
    public static function desglosePara(string $desde, string $hasta): array
    {
        $ingresos = Pago::selectRaw('DATE(fecha) as fecha, SUM(monto) as total')
            ->whereBetween('fecha', [$desde, $hasta.' 23:59:59'])
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        $egresos = GastoEgreso::selectRaw('fecha, SUM(monto) as total')
            ->whereBetween('fecha', [$desde, $hasta])
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        $fechas = collect($ingresos->keys())->merge($egresos->keys())->unique()->sortDesc();

        return $fechas->map(fn (string $fecha) => [
            'fecha' => $fecha,
            'ingresos' => (float) ($ingresos[$fecha] ?? 0),
            'egresos' => (float) ($egresos[$fecha] ?? 0),
            'resultado' => (float) ($ingresos[$fecha] ?? 0) - (float) ($egresos[$fecha] ?? 0),
        ])->values()->all();
    }
}
