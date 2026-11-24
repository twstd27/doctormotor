@php($orden = $getRecord())
<div class="dm-celda">
    <span class="dm-celda-titulo dm-celda-titulo--normal">{{ $orden->cliente?->nombre }}</span>
    <span class="dm-mono dm-celda-sub">{{ $orden->cliente?->telefono_whatsapp }}</span>
</div>
