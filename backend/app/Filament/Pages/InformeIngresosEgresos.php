<?php

namespace App\Filament\Pages;

use App\Models\GastoEgreso;
use App\Models\Pago;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class InformeIngresosEgresos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?string $navigationLabel = 'Informe de ingresos y egresos';

    protected static ?string $title = 'Ingresos y egresos';

    protected string $view = 'filament.pages.informe-ingresos-egresos';

    public string $desde;

    public string $hasta;

    public function mount(): void
    {
        $this->desde = now()->subMonth()->toDateString();
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
        $desde = Carbon::parse($this->desde)->format('d/m/Y');
        $hasta = Carbon::parse($this->hasta)->format('d/m/Y');
        $dias = $this->diasConMovimientos();

        return "{$desde} – {$hasta} · {$dias} ".($dias === 1 ? 'día con movimientos' : 'días con movimientos');
    }

    public function aplicarRango(string $tipo): void
    {
        match ($tipo) {
            'hoy' => [$this->desde, $this->hasta] = [now()->toDateString(), now()->toDateString()],
            '7dias' => [$this->desde, $this->hasta] = [now()->subDays(6)->toDateString(), now()->toDateString()],
            'estemes' => [$this->desde, $this->hasta] = [now()->startOfMonth()->toDateString(), now()->toDateString()],
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
            $this->desde === now()->startOfYear()->toDateString() && $this->hasta === $hoy => 'ano',
            default => null,
        };
    }

    public function totalIngresos(): float
    {
        return (float) Pago::whereBetween('fecha', [$this->desde, $this->hasta.' 23:59:59'])->sum('monto');
    }

    public function totalEgresos(): float
    {
        return (float) GastoEgreso::whereBetween('fecha', [$this->desde, $this->hasta])->sum('monto');
    }

    public function resultado(): float
    {
        return $this->totalIngresos() - $this->totalEgresos();
    }

    public function margenPorcentaje(): ?float
    {
        $ingresos = $this->totalIngresos();

        return $ingresos > 0 ? ($this->resultado() / $ingresos) * 100 : null;
    }

    /**
     * Desglose día por día — mismo cálculo que ReporteController::ingresosEgresos, pero
     * ya combinado en una sola fila por fecha para la tabla.
     *
     * @return array<int, array{fecha: string, ingresos: float, egresos: float, resultado: float}>
     */
    public function desglose(): array
    {
        return static::desglosePara($this->desde, $this->hasta);
    }

    public function diasConMovimientos(): int
    {
        return count($this->desglose());
    }

    /**
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
