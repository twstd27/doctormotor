@if ($getRecord()->user_id)
    <span class="dm-estado dm-estado--en_reparacion"><i aria-hidden="true"></i>Activo</span>
@else
    <span class="dm-estado dm-estado--recepcionado"><i aria-hidden="true"></i>Sin cuenta</span>
@endif
