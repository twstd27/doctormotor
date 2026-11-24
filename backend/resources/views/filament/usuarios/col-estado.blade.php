@php($usuario = $getRecord())
@if (! $usuario->activo)
    <span class="dm-estado dm-estado--entregado"><i aria-hidden="true"></i>Inactivo</span>
@elseif (! $usuario->password)
    <span class="dm-estado dm-estado--esperando_aprobacion"><i aria-hidden="true"></i>Invitación pendiente</span>
@else
    <span class="dm-estado dm-estado--en_reparacion"><i aria-hidden="true"></i>Activo</span>
@endif
