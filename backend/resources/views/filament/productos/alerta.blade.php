@php
    use App\Filament\Resources\Compras\CompraResource;

    $partes = [];
    if ($bajos > 0) {
        $partes[] = $bajos.' '.($bajos === 1 ? 'producto está' : 'productos están').' por debajo del mínimo';
    }
    if ($sinStock > 0) {
        $partes[] = $sinStock.' '.($sinStock === 1 ? 'ya no tiene stock' : 'ya no tienen stock');
    }
    $frase = implode(' y ', $partes);
@endphp

@if ($frase !== '')
    <div class="dm-aviso dm-aviso--ambar dm-aviso--fila">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
        <span class="dm-aviso-texto">{{ $frase }}. Las órdenes que los necesiten podrían quedar esperando.</span>
        @if (CompraResource::canCreate())
            <a class="dm-aviso-accion" href="{{ CompraResource::getUrl('create') }}">Registrar compra →</a>
        @endif
    </div>
@endif
