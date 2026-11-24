<section class="dm-panel">
    <div class="dm-ingreso-etiqueta">Así lo verá el técnico al cargarlo en una orden</div>
    <div class="dm-tarjeta">
        <span class="dm-producto-miniatura" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8 12 3 3 8v8l9 5 9-5Z"/><path d="m3 8 9 5 9-5M12 13v8"/></svg>
        </span>
        <div class="dm-tarjeta-cuerpo dm-tarjeta-cuerpo--crece">
            <div class="dm-tarjeta-titulo">{{ filled($nombre) ? $nombre : 'Nombre del producto' }}</div>
            <div class="dm-celda-sub">
                <span class="dm-mono">{{ filled($sku) ? $sku : 'SKU' }}</span>{{ filled($categoria) ? ' · '.$categoria : '' }}
            </div>
        </div>
        <div class="dm-vista-previa-precio">
            <div class="dm-mono dm-tarjeta-titulo">Bs {{ number_format($precio, 2, ',', '.') }}</div>
            <div class="dm-celda-sub">por {{ $unidad }}</div>
        </div>
    </div>
</section>
