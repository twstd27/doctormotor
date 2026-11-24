@php
    $cliente = $vehiculo?->cliente;
    $iniciales = $cliente
        ? collect(preg_split('/\s+/', trim($cliente->nombre)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('')
        : null;

    $ultima = $vehiculo
        ? $vehiculo->ordenesTrabajo()->orderByDesc('fecha_ingreso')->first()
        : null;
    $saldo = $cliente?->saldoPendiente() ?? 0;
    $visitas = (int) ($vehiculo?->ordenes_trabajo_count ?? 0);
    $busco = filled($buscada) && strlen(preg_replace('/[^A-Za-z0-9]/', '', (string) $buscada)) >= 3;
@endphp

<div class="dm-recepcion-vehiculo">
    @if ($vehiculo)
        @if ($buscada)
            <div class="dm-estado-busqueda dm-estado-busqueda--ok">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Vehículo encontrado: el cliente y su historial se cargaron solos.
            </div>
        @endif

        <div class="dm-tarjetas">
            <div class="dm-tarjeta">
                <span class="dm-tarjeta-icono" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                </span>
                <div class="dm-tarjeta-cuerpo">
                    <div class="dm-tarjeta-titulo">{{ $vehiculo->marca }} {{ $vehiculo->modelo }} {{ $vehiculo->anio }}</div>
                    <div class="dm-tarjeta-sub">
                        <x-dm.placa>{{ $vehiculo->placa }}</x-dm.placa>
                        · {{ $vehiculo->color }} · {{ $visitas === 0 ? 'primera visita' : ($visitas === 1 ? '1 visita' : $visitas.' visitas') }}
                    </div>
                </div>
            </div>

            <div class="dm-tarjeta">
                <span class="dm-avatar dm-tarjeta-avatar" aria-hidden="true">{{ $iniciales }}</span>
                <div class="dm-tarjeta-cuerpo">
                    <div class="dm-tarjeta-titulo">{{ $cliente?->nombre }}</div>
                    <div class="dm-tarjeta-sub dm-mono">{{ $cliente?->telefono_whatsapp }}</div>
                </div>
            </div>
        </div>

        @if ($ultima || $saldo > 0.009)
            <div class="dm-aviso {{ $saldo > 0.009 ? 'dm-aviso--ambar' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                <span>
                    @if ($ultima)
                        Última visita {{ $ultima->fecha_ingreso?->format('d/m/Y') }} — {{ \Illuminate\Support\Str::limit($ultima->descripcion_problema, 70) }}.
                    @endif
                    @if ($saldo > 0.009)
                        Saldo pendiente del cliente: <strong class="dm-mono dm-texto-ambar">Bs {{ number_format($saldo, 2, ',', '.') }}</strong>
                    @endif
                </span>
            </div>
        @endif
    @elseif ($busco)
        <div class="dm-estado-busqueda dm-estado-busqueda--no">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
            No hay un vehículo con la placa «{{ strtoupper($buscada) }}». Usa «Vehículo nuevo» para registrarlo.
        </div>
    @endif
</div>
