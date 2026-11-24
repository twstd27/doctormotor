@php($notificacion = $getRecord())
<div class="dm-celda">
    <span class="dm-celda-titulo dm-celda-titulo--normal">{{ $notificacion->nombrePlantilla() }}</span>
    <span class="dm-mono dm-celda-sub">{{ $notificacion->plantilla }}</span>
    @if ($notificacion->estado === 'fallido' && $notificacion->errorLegible())
        <span class="dm-error-envio">{{ $notificacion->errorLegible() }}</span>
    @endif
</div>
