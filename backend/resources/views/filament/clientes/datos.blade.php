@php
    use App\Filament\Resources\Clientes\ClienteResource;
@endphp

<section class="dm-panel">
    <div class="dm-panel-cabecera">
        <h2 class="dm-panel-titulo">Datos</h2>
        @if (ClienteResource::canEdit($cliente) && ! $cliente->trashed())
            <a class="dm-panel-enlace" href="{{ ClienteResource::getUrl('edit', ['record' => $cliente]) }}">Editar</a>
        @endif
    </div>
    <dl class="dm-cuenta-lineas dm-datos-lista">
        <div><dt>Dirección</dt><dd>{{ $cliente->direccion ?: '—' }}</dd></div>
        <div><dt>CI / NIT</dt><dd class="dm-mono">{{ $cliente->ci_nit ?: '—' }}</dd></div>
        <div><dt>Correo</dt><dd>{{ $cliente->correo ?: '—' }}</dd></div>
        <div><dt>Registrado</dt><dd>{{ $cliente->created_at?->translatedFormat('j M Y') }}</dd></div>
    </dl>
    @if (filled($cliente->notas))
        <div>
            <div class="dm-ingreso-etiqueta">Notas</div>
            <p class="dm-ingreso-problema">{{ $cliente->notas }}</p>
        </div>
    @endif
</section>

<section class="dm-panel">
    <h2 class="dm-panel-titulo">Portal del cliente</h2>
    @if ($cliente->user_id)
        <div class="dm-portal-estado"><span class="dm-dot dm-dot--lima" aria-hidden="true"></span>Cuenta vinculada</div>
        <p class="dm-celda-sub">{{ $cliente->user?->ultimo_login_at ? 'Último acceso '.$cliente->user->ultimo_login_at->diffForHumans().'.' : 'Todavía no ha entrado al portal.' }}</p>
    @else
        <div class="dm-portal-estado"><span class="dm-dot" style="background: var(--dm-muted)" aria-hidden="true"></span>Sin cuenta todavía</div>
        <p class="dm-celda-sub">Invítalo por WhatsApp desde «Más acciones» para que vea el avance de su vehículo.</p>
    @endif
</section>
