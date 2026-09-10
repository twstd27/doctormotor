<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Socios\SocioResource;
use App\Models\GastoEgreso;
use App\Models\OrdenTrabajo;
use App\Models\Pago;
use App\Models\ReglaReparto;
use App\Models\RepartoUtilidad;
use App\Models\Socio;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RepartoUtilidades extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?string $navigationLabel = 'Reparto de utilidades';

    protected static ?string $title = 'Reparto de utilidades';

    protected string $view = 'filament.pages.reparto-utilidades';

    /** @var array<int, array{socio_id: int, nombre: string, porcentaje: float}> */
    public array $reglas = [];

    public string $periodoInicio;

    public string $periodoFin;

    public function mount(): void
    {
        $this->periodoInicio = now()->startOfMonth()->toDateString();
        $this->periodoFin = now()->toDateString();
        $this->cargarReglas();
    }

    public function getSubheading(): string
    {
        return 'Porcentaje de cada socio sobre la utilidad neta, y generación del reparto por período.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('anadirSocio')
                ->label('Añadir socio')
                ->color('gray')
                ->url(fn () => SocioResource::getUrl('create')),
        ];
    }

    private function cargarReglas(): void
    {
        $vigentes = ReglaReparto::whereNull('vigente_hasta')->pluck('porcentaje', 'socio_id');

        $this->reglas = Socio::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Socio $socio) => [
                'socio_id' => $socio->id,
                'nombre' => $socio->nombre,
                'porcentaje' => (float) ($vigentes[$socio->id] ?? $socio->porcentaje_default),
            ])
            ->values()
            ->all();
    }

    public function sumaPorcentajes(): float
    {
        return round(collect($this->reglas)->sum(fn (array $r) => (float) $r['porcentaje']), 2);
    }

    public function guardarReglas(): void
    {
        if ($this->sumaPorcentajes() !== 100.0) {
            return;
        }

        DB::transaction(function () {
            ReglaReparto::whereNull('vigente_hasta')->update(['vigente_hasta' => now()->subDay()]);

            foreach ($this->reglas as $regla) {
                ReglaReparto::create([
                    'socio_id' => $regla['socio_id'],
                    'porcentaje' => $regla['porcentaje'],
                    'vigente_desde' => now()->toDateString(),
                ]);
            }
        });

        Notification::make()->title('Reglas de reparto actualizadas')->success()->send();
    }

    public function aplicarRango(string $tipo): void
    {
        match ($tipo) {
            'hoy' => [$this->periodoInicio, $this->periodoFin] = [now()->toDateString(), now()->toDateString()],
            '7dias' => [$this->periodoInicio, $this->periodoFin] = [now()->subDays(6)->toDateString(), now()->toDateString()],
            'estemes' => [$this->periodoInicio, $this->periodoFin] = [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'ano' => [$this->periodoInicio, $this->periodoFin] = [now()->startOfYear()->toDateString(), now()->toDateString()],
            default => null,
        };
    }

    public function rangoActivo(): ?string
    {
        $hoy = now()->toDateString();

        return match (true) {
            $this->periodoInicio === $hoy && $this->periodoFin === $hoy => 'hoy',
            $this->periodoInicio === now()->subDays(6)->toDateString() && $this->periodoFin === $hoy => '7dias',
            $this->periodoInicio === now()->startOfMonth()->toDateString() && $this->periodoFin === $hoy => 'estemes',
            $this->periodoInicio === now()->startOfYear()->toDateString() && $this->periodoFin === $hoy => 'ano',
            default => null,
        };
    }

    public function totalIngresos(): float
    {
        return (float) Pago::whereBetween('fecha', [$this->periodoInicio, $this->periodoFin.' 23:59:59'])->sum('monto');
    }

    public function totalCostosDirectos(): float
    {
        return (float) OrdenTrabajo::query()
            ->join('costos_directos', 'costos_directos.orden_trabajo_id', '=', 'ordenes_trabajo.id')
            ->whereBetween('costos_directos.created_at', [$this->periodoInicio, $this->periodoFin.' 23:59:59'])
            ->sum('costos_directos.costo_total');
    }

    public function totalGastos(): float
    {
        return (float) GastoEgreso::whereBetween('fecha', [$this->periodoInicio, $this->periodoFin])->sum('monto');
    }

    public function utilidadPreview(): float
    {
        return $this->totalIngresos() - $this->totalCostosDirectos() - $this->totalGastos();
    }

    public function generarReparto(): void
    {
        $this->validate([
            'periodoInicio' => ['required', 'date'],
            'periodoFin' => ['required', 'date', 'after_or_equal:periodoInicio'],
        ]);

        $reglas = ReglaReparto::whereNull('vigente_hasta')->get();

        if ($reglas->isEmpty()) {
            Notification::make()
                ->title('No hay reglas de reparto configuradas')
                ->body('Guarda las reglas de arriba antes de generar un reparto.')
                ->danger()
                ->send();

            return;
        }

        $ingresos = $this->totalIngresos();
        $costosDirectos = $this->totalCostosDirectos();
        $gastos = $this->totalGastos();
        $utilidadNeta = $ingresos - $costosDirectos - $gastos;

        DB::transaction(function () use ($ingresos, $costosDirectos, $gastos, $utilidadNeta, $reglas) {
            $reparto = RepartoUtilidad::create([
                'periodo_inicio' => $this->periodoInicio,
                'periodo_fin' => $this->periodoFin,
                'ingresos_total' => $ingresos,
                'costos_directos_total' => $costosDirectos,
                'gastos_total' => $gastos,
                'utilidad_neta' => $utilidadNeta,
                'generado_por_id' => auth()->id(),
                'generado_at' => now(),
            ]);

            foreach ($reglas as $regla) {
                $reparto->detalle()->create([
                    'socio_id' => $regla->socio_id,
                    'porcentaje_aplicado' => $regla->porcentaje,
                    'monto' => $utilidadNeta * ($regla->porcentaje / 100),
                ]);
            }
        });

        Notification::make()
            ->title('Reparto generado')
            ->body('Utilidad neta del período: Bs '.number_format($utilidadNeta, 2))
            ->success()
            ->send();
    }

    public function anularReparto(int $repartoUtilidadId): void
    {
        RepartoUtilidad::findOrFail($repartoUtilidadId)->delete();

        Notification::make()->title('Reparto anulado')->success()->send();
    }

    public function repartos(): Collection
    {
        return RepartoUtilidad::with('detalle.socio:id,nombre', 'generadoPor:id,nombre')
            ->orderByDesc('generado_at')
            ->get();
    }

    public static function iniciales(string $nombre): string
    {
        return collect(explode(' ', trim($nombre)))
            ->filter()
            ->take(2)
            ->map(fn (string $palabra) => mb_strtoupper(mb_substr($palabra, 0, 1)))
            ->implode('');
    }
}
