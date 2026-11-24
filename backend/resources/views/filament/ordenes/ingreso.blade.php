@php
    use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;

    $niveles = ['E' => [0, 'Tanque vacío'], '1/4' => [1, 'Un cuarto de tanque'], '1/2' => [2, 'Medio tanque'], '3/4' => [3, 'Tres cuartos de tanque'], 'F' => [4, 'Tanque lleno']];
    [$llenos, $textoNivel] = $niveles[$orden->nivel_gasolina] ?? [0, 'Sin dato'];
@endphp

<section class="dm-panel">
    <div class="dm-panel-cabecera">
        <h2 class="dm-panel-titulo">Datos de ingreso</h2>
        @if (! $orden->estaCerrada() && OrdenTrabajoResource::canEdit($orden))
            <a class="dm-panel-enlace" href="{{ OrdenTrabajoResource::getUrl('edit', ['record' => $orden]) }}">Editar</a>
        @endif
    </div>

    <div class="dm-ingreso-celdas">
        <div class="dm-ingreso-celda">
            <div class="dm-ingreso-etiqueta">Kilometraje</div>
            <div class="dm-mono dm-ingreso-valor">{{ $orden->kilometraje_ingreso !== null ? number_format($orden->kilometraje_ingreso, 0, ',', '.') : '—' }}</div>
        </div>
        <div class="dm-ingreso-celda">
            <div class="dm-ingreso-etiqueta">Combustible</div>
            <div class="dm-combustible" role="img" aria-label="{{ $textoNivel }}">
                @for ($i = 0; $i < 4; $i++)
                    <span class="{{ $i < $llenos ? 'dm-combustible--lleno' : '' }}"></span>
                @endfor
            </div>
        </div>
    </div>

    <div>
        <div class="dm-ingreso-etiqueta">Lo que reportó el cliente</div>
        <p class="dm-ingreso-problema">{{ $orden->descripcion_problema ?: 'Sin descripción.' }}</p>
    </div>
</section>
