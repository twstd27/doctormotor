@php($notificacion = $getRecord())
@php($porEnviar = $notificacion->estado === 'por_enviar')
{{-- «Abrir chat» pasa por una ruta que marca el mensaje como enviado y redirige a wa.me con el
     texto. «Copiar texto» deja el mensaje en el portapapeles con su formato de WhatsApp. --}}
<div class="dm-chat-acciones">
    <a
        href="{{ route('mensajes.abrir', $notificacion) }}"
        target="_blank"
        rel="noopener"
        @class(['dm-btn', 'dm-btn--chico', 'dm-btn--primario' => $porEnviar, 'dm-btn--secundario' => ! $porEnviar])
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 21l2.1-5.6A8.4 8.4 0 1 1 21 11.5z"/></svg>
        {{ $porEnviar ? 'Abrir chat' : 'Abrir de nuevo' }}
    </a>
    <button
        type="button"
        x-data="{ copiado: false }"
        x-on:click="navigator.clipboard.writeText(@js($notificacion->textoMensaje())); copiado = true; setTimeout(() => copiado = false, 1800)"
        class="dm-btn dm-btn--chico dm-btn--secundario"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
        <span x-text="copiado ? 'Copiado' : 'Copiar texto'">Copiar texto</span>
    </button>
</div>
