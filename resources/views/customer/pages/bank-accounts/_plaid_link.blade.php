@php
    // ID univoco per il bottone (evita collisioni se includi il partial più volte nella stessa pagina)
    $btnId    = $btnId    ?? ('plaidLinkBtn_' . uniqid());
    // Se vuoi mostrare anche il testo di aiuto a destra del bottone: passa ['showHint' => true] all'include
    $showHint = $showHint ?? false;
@endphp

<div class="d-inline-flex align-items-center gap-2 m-0 p-0">
    <button type="button" id="{{ $btnId }}" class="twoToneBlueGreenBtn">
        Connect your bank
    </button>
    @if ($showHint)
        <small class="text-muted m-0 d-none d-md-inline">Collega il conto via Plaid.</small>
    @endif
</div>

<script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
<script>
    (function () {
        async function openLink(receivedRedirectUri) {
            try {
                const res = await fetch("{{ route('plaid.link-token') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({})
                });
                const data = await res.json();
                if (!data.link_token) { alert('Cannot create Plaid link token'); return; }

                const cfg = { token: data.link_token };
                if (receivedRedirectUri) cfg.receivedRedirectUri = receivedRedirectUri;

                const handler = Plaid.create({
                    ...cfg,
                    onSuccess: async function (public_token, metadata) {
                        await fetch("{{ route('plaid.exchange') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                public_token: public_token,
                                institution: (metadata && metadata.institution) ? metadata.institution : null
                            })
                        });
                        window.location.reload();
                    },
                    onExit: function (err, metadata) {
                        if (err) console.error('Plaid exit', err, metadata);
                    }
                });

                handler.open();
            } catch (e) {
                console.error(e);
                alert('Could not start Plaid Link.');
            }
        }

        // Click sul bottone
        var btn = document.getElementById('{{ $btnId }}');
        if (btn) btn.addEventListener('click', function () { openLink(null); });

        // Auto‑resume dopo redirect OAuth (UK/EU)
        if (new URLSearchParams(window.location.search).has('oauth_state_id')) {
            openLink(window.location.href);
        }
    })();
</script>
