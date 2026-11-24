@php
    $livewire ??= null;
@endphp

{{-- Layout vacío para el login: la página dibuja sus dos mitades de borde a borde. --}}
<x-filament-panels::layout.base :livewire="$livewire">
    {{ $slot }}
</x-filament-panels::layout.base>
