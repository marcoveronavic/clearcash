@php
    $showHint = $showHint ?? false;
    $fromSetup = $fromSetup ?? false;
@endphp

<a href="{{ route('powens.connect', $fromSetup ? ['from_setup' => 1] : []) }}" class="twoToneBlueGreenBtn cta-btn" id="connectBankBtn" style="width:100%;text-align:center;display:block;">
    Collega la tua banca
</a>
@if ($showHint)
    <small class="text-muted m-0 d-none d-md-inline">Collega il conto via Open Banking.</small>
@endif
