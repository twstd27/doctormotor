@php
    use App\Support\EstadoOrden;

    $vehiculo = $orden->vehiculo;
    $cliente = $orden->cliente;
    $entrega = $orden->fecha_entrega_estimada;
    $cerrada = $orden->estaCerrada();

    // Aviso de entrega: solo mientras la orden sigue abierta.
    $aviso = null;
    if ($entrega && ! $cerrada) {
        $dias = (int) today()->diffInDays($entrega, false);
        $aviso = match (true) {
            $dias < 0 => ['Entrega vencida hace '.abs($dias).($dias === -1 ? ' día' : ' días'), 'rojo'],
            $dias === 0 => ['Entrega hoy', 'ambar'],
            $dias === 1 => ['Entrega mañana', 'ambar'],
            default => ['Entrega en '.$dias.' días', 'gris'],
        };
    }

    $pasos = ['Recepcionado', 'Diagnóstico', 'Esperando aprobación', 'En reparación', 'Control de calidad', 'Listo para entrega', 'Entregado'];
    $indice = array_search($orden->estado, ['recepcionado', 'en_diagnostico', 'esperando_aprobacion', 'en_reparacion', 'control_calidad', 'listo_entrega', 'entregado'], true);
    // Entregada: todo el recorrido queda completo. Cancelada: ningún paso (indice = false).
    $actual = $orden->estado === 'entregado' ? count($pasos) : $indice;
@endphp

<section class="dm-ficha">
    <div class="dm-ficha-cuerpo">
        <div class="dm-ficha-meta">
            <x-dm.estado :estado="$orden->estado" />
            @if ($aviso)
                <span class="dm-chip-aviso dm-chip-aviso--{{ $aviso[1] }}">{{ $aviso[0] }}</span>
            @endif
        </div>

        <div class="dm-ficha-titulo">
            <h2>{{ $vehiculo?->marca }} {{ $vehiculo?->modelo }} {{ $vehiculo?->anio }}</h2>
            <x-dm.placa class="dm-placa--grande">{{ $vehiculo?->placa }}</x-dm.placa>
        </div>

        <dl class="dm-ficha-datos">
            <div>
                <dt>Cliente</dt>
                <dd>
                    {{ $cliente?->nombre }}
                    @if ($cliente?->telefono_whatsapp)
                        <a href="https://wa.me/{{ app(\App\Services\WhatsAppService::class)->normalizarTelefono($cliente->telefono_whatsapp) }}"
                           target="_blank" rel="noopener" class="dm-icono-link" aria-label="Escribir a {{ $cliente->nombre }} por WhatsApp">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                        </a>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Técnico</dt>
                <dd>{{ $orden->tecnicoAsignado?->nombre ?? 'Sin asignar' }}</dd>
            </div>
            <div>
                <dt>Ingreso</dt>
                <dd class="dm-mono">{{ $orden->fecha_ingreso?->format('d/m · H:i') }}</dd>
            </div>
            <div>
                <dt>Entrega estimada</dt>
                <dd class="dm-mono {{ $aviso && $aviso[1] !== 'gris' ? 'dm-texto-'.$aviso[1] : '' }}">
                    {{ $entrega ? $entrega->format('d/m') : 'Sin fecha' }}
                </dd>
            </div>
        </dl>
    </div>

    @if ($orden->estado === 'cancelado')
        <div class="dm-progreso-cancelada">Esta orden fue cancelada.</div>
    @else
        <ol class="dm-progreso" aria-label="Progreso de la orden">
            @foreach ($pasos as $i => $paso)
                @php($clase = $i < $actual ? 'hecho' : ($i === $actual ? 'ahora' : 'pendiente'))
                <li class="dm-paso dm-paso--{{ $clase }}" @if ($clase === 'ahora') aria-current="step" @endif>
                    <span class="dm-paso-barra"></span>
                    <span class="dm-paso-texto">{{ $paso }}</span>
                </li>
            @endforeach
        </ol>
    @endif
</section>
