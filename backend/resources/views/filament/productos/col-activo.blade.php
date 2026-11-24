@if ($getRecord()->activo)
    <span class="dm-estado dm-estado--en_reparacion"><i aria-hidden="true"></i>Activo</span>
@else
    <span class="dm-estado dm-estado--entregado"><i aria-hidden="true"></i>Inactivo</span>
@endif
