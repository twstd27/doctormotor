@php
    use App\Filament\Resources\Vehiculos\VehiculoResource;

    $vehiculos = $cliente->vehiculos()->withCount('ordenesTrabajo')->orderBy('placa')->get();
@endphp

<section class="dm-panel">
    <div class="dm-panel-cabecera">
        <h2 class="dm-panel-titulo">
            Vehículos
            <span class="dm-contador dm-contador--gris">{{ $vehiculos->count() }}</span>
        </h2>
        @if (VehiculoResource::canCreate())
            <a class="dm-panel-enlace" href="{{ VehiculoResource::getUrl('create') }}">+ Agregar vehículo</a>
        @endif
    </div>

    @if ($vehiculos->isEmpty())
        <p class="dm-ingreso-problema">Este cliente todavía no tiene vehículos registrados.</p>
    @else
        <div class="dm-vehiculos-grid">
            @foreach ($vehiculos as $vehiculo)
                <a class="dm-tarjeta dm-tarjeta--vertical" href="{{ VehiculoResource::getUrl('view', ['record' => $vehiculo]) }}">
                    <div>
                        <x-dm.placa class="dm-placa--grande">{{ $vehiculo->placa }}</x-dm.placa>
                        <div class="dm-tarjeta-titulo dm-tarjeta-titulo--grande">{{ $vehiculo->marca }} {{ $vehiculo->modelo }} {{ $vehiculo->anio }}</div>
                        <div class="dm-celda-sub">{{ $vehiculo->color }}{{ $vehiculo->motor ? ' · '.$vehiculo->motor : '' }}</div>
                    </div>
                    <div class="dm-vehiculo-pie">
                        <div>
                            <div class="dm-ingreso-etiqueta">Kilometraje</div>
                            <div class="dm-mono dm-vehiculo-valor">{{ number_format((int) $vehiculo->kilometraje_actual, 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="dm-ingreso-etiqueta">Visitas</div>
                            <div class="dm-mono dm-vehiculo-valor">{{ $vehiculo->ordenes_trabajo_count }}</div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
