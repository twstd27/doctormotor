@props(['estado'])

<span {{ $attributes->class(['dm-estado', 'dm-estado--'.$estado]) }}><i aria-hidden="true"></i>{{ \App\Support\EstadoOrden::etiqueta($estado) }}</span>
