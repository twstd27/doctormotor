@php
    use App\Filament\Pages\CajaDelDia;
use App\Filament\Resources\CajaCierres\CajaCierreResource;
    use App\Models\CajaCierre;

    $usuario = filament()->auth()->user();
    $caja = $usuario ? CajaCierre::abiertaDe($usuario->id) : null;
    // El cajero siempre ve el estado de su caja; el super_admin solo si tiene una abierta.
    $mostrar = $usuario && ($usuario->rol === 'cajero' || $caja);
@endphp

@if ($mostrar)
    <div class="dm-topbar-caja">
        @if ($caja)
            <a href="{{ CajaDelDia::getUrl() }}" class="dm-caja dm-caja--abierta" title="Efectivo esperado en tu caja">
                <span class="dm-dot dm-dot--lima" aria-hidden="true"></span>
                <span class="dm-caja-estado">Caja abierta</span>
                <span class="dm-caja-sep" aria-hidden="true"></span>
                <span class="dm-mono">Bs {{ number_format($caja->efectivoEsperado(), 2, ',', '.') }}</span>
            </a>
        @else
            <a href="{{ CajaCierreResource::getUrl('create') }}" class="dm-caja dm-caja--cerrada">
                <span class="dm-dot dm-dot--ambar" aria-hidden="true"></span>
                Caja cerrada · Abrir
            </a>
        @endif
    </div>
@endif
