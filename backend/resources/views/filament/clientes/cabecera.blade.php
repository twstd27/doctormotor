@php
    $iniciales = collect(preg_split('/\s+/', trim($cliente->nombre)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $whatsapp = $cliente->telefono_whatsapp
        ? 'https://wa.me/'.app(\App\Services\WhatsAppService::class)->normalizarTelefono($cliente->telefono_whatsapp)
        : null;
@endphp

<section class="dm-ficha">
    <div class="dm-ficha-cuerpo dm-cliente-cabecera">
        <span class="dm-avatar dm-avatar--xl {{ $cliente->user_id ? 'dm-tarjeta-avatar' : '' }}" aria-hidden="true">{{ $iniciales }}</span>
        <div class="dm-cliente-cabecera-datos">
            <div class="dm-ficha-meta">
                @if ($cliente->user_id)
                    <span class="dm-estado dm-estado--en_reparacion"><i aria-hidden="true"></i>Portal activo</span>
                @else
                    <span class="dm-estado dm-estado--recepcionado"><i aria-hidden="true"></i>Sin cuenta en el portal</span>
                @endif
                @if ($cliente->trashed())
                    <span class="dm-chip-aviso dm-chip-aviso--rojo">Eliminado</span>
                @endif
            </div>
            <div class="dm-cliente-contacto">
                @if ($cliente->ci_nit)
                    <span class="dm-mono">CI/NIT {{ $cliente->ci_nit }}</span>
                @endif
                @if ($whatsapp)
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="dm-icono-link-texto">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                        <span class="dm-mono">{{ $cliente->telefono_whatsapp }}</span>
                    </a>
                @endif
                @if ($cliente->correo)
                    <span>{{ $cliente->correo }}</span>
                @endif
            </div>
        </div>
    </div>
</section>
