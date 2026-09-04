<?php

namespace App\Filament\Pages;

use App\Models\GastoEgreso;
use App\Models\Pago;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

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

    /**
     * Desglose día por día — mismo cálculo que ReporteController::ingresosEgresos, pero
     * ya combinado en una sola fila por fecha para la tabla.
     *
     * @return array<int, array{fecha: string, ingresos: float, egresos: float, resultado: float}>
     */
    public function desglose(): array
    {
        $ingresos = Pago::selectRaw('DATE(fecha) as fecha, SUM(monto) as total')
            ->whereBetween('fecha', [$this->desde, $this->hasta.' 23:59:59'])
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        $egresos = GastoEgreso::selectRaw('fecha, SUM(monto) as total')
            ->whereBetween('fecha', [$this->desde, $this->hasta])
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
