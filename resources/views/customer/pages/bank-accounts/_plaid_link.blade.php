@php
    $showHint = $showHint ?? false;
    $fromSetup = $fromSetup ?? false;
@endphp

<a href="{{ route('powens.connect', $fromSetup ? ['from_setup' => 1] : []) }}" class="twoToneBlueGreenBtn cta-btn" id="connectBankBtn" style="width:100%;text-align:center;display:block;overflow:visible;">
    {{ __('messages.connect_bank_btn') }}
</a>
@if ($showHint)
    <small class="text-muted m-0 d-none d-md-inline">{{ __('messages.connect_bank_hint') }}</small>
@endif
