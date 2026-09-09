<?php

namespace App\Filament\Pages;

use App\Models\GastoEgreso;
use App\Models\OrdenTrabajo;
use App\Models\Pago;
use App\Models\ReglaReparto;
use App\Models\RepartoUtilidad;
use App\Models\Socio;
use BackedEnum;
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
        $suma = $this->sumaPorcentajes();

        if ($suma !== 100.0) {
            Notification::make()
                ->title('Los porcentajes deben sumar 100')
                ->body("Ahora mismo suman {$suma}.")
                ->danger()
                ->send();

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

        $ingresos = (float) Pago::whereBetween('fecha', [$this->periodoInicio, $this->periodoFin.' 23:59:59'])->sum('monto');

        $costosDirectos = (float) OrdenTrabajo::query()
            ->join('costos_directos', 'costos_directos.orden_trabajo_id', '=', 'ordenes_trabajo.id')
            ->whereBetween('costos_directos.created_at', [$this->periodoInicio, $this->periodoFin.' 23:59:59'])
            ->sum('costos_directos.costo_total');

        $gastos = (float) GastoEgreso::whereBetween('fecha', [$this->periodoInicio, $this->periodoFin])->sum('monto');

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

    public function repartos(): Collection
    {
        return RepartoUtilidad::with('detalle.socio:id,nombre', 'generadoPor:id,nombre')
            ->orderByDesc('periodo_inicio')
            ->get();
    }
}
