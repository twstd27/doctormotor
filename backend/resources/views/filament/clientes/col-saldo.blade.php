@php($saldo = $getRecord()->saldoPendiente())
@if ($saldo > 0.009)
    <span class="dm-mono dm-saldo dm-saldo--debe">Bs {{ number_format($saldo, 2, ',', '.') }}</span>
@elseif ($saldo < -0.009)
    <span class="dm-mono dm-saldo dm-saldo--favor" title="El cliente pagó de más">− Bs {{ number_format(abs($saldo), 2, ',', '.') }}</span>
@else
    <span class="dm-mono dm-saldo">Bs 0,00</span>
@endif
