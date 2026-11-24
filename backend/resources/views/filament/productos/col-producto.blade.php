@php($producto = $getRecord())
<div class="dm-cliente-celda">
    <span class="dm-producto-miniatura" aria-hidden="true">
        @if ($producto->imagen_url)
            <img src="{{ $producto->imagen_url }}" alt="" loading="lazy">
        @else
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8 12 3 3 8v8l9 5 9-5Z"/><path d="m3 8 9 5 9-5M12 13v8"/></svg>
        @endif
    </span>
    <div class="dm-celda">
        <span class="dm-celda-titulo">{{ $producto->nombre }}</span>
        <span class="dm-mono dm-celda-sub">{{ $producto->sku }}</span>
    </div>
</div>
