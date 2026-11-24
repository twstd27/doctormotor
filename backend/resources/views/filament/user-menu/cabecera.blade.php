@php
    use App\Filament\Resources\CajaCierres\CajaCierreResource;
    use App\Models\CajaCierre;

    $usuario = filament()->auth()->user();
    $nombre = filament()->getUserName($usuario);
    $iniciales = collect(preg_split('/\s+/', trim($nombre)))->filter()->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $rol = ['super_admin' => 'Administrador', 'cajero' => 'Cajero'][$usuario->rol] ?? $usuario->rol;
    $caja = CajaCierre::abiertaDe($usuario->id);
@endphp

<div class="dm-um-cabecera">
    <span class="dm-avatar dm-avatar--lg" aria-hidden="true">{{ $iniciales }}</span>
    <div class="dm-um-datos">
        <div class="dm-um-nombre">{{ $nombre }}</div>
        @if ($usuario->email)
            <div class="dm-um-correo">{{ $usuario->email }}</div>
        @endif
    </div>
    <span class="dm-chip dm-chip--info">{{ $rol }}</span>
</div>

@if ($caja)
    <div class="dm-um-caja">
        <div class="dm-um-caja-fila">
            <span class="dm-um-caja-titulo"><span class="dm-dot dm-dot--lima" aria-hidden="true"></span>Tu caja está abierta</span>
            <span class="dm-mono dm-um-caja-monto">Bs {{ number_format($caja->efectivoEsperado(), 2, ',', '.') }}</span>
        </div>
        <div class="dm-um-caja-nota">
            Desde {{ $caja->created_at->format('H:i') }} · {{ $caja->pagos()->count() }} {{ $caja->pagos()->count() === 1 ? 'cobro' : 'cobros' }}.
            Ciérrala antes de salir para cuadrar el turno.
        </div>
        <a href="{{ CajaCierreResource::getUrl('index') }}" class="dm-btn dm-btn--secundario dm-btn--bloque">Ir a cerrar la caja</a>
    </div>
@endif
