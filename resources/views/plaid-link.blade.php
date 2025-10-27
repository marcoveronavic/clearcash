<div class="mb-3 d-flex align-items-center gap-2">
    <button type="button" id="plaidLinkBtn" class="twoToneBlueGreenBtn">
        Connect your bank
    </button>
    <small class="text-muted">Collega il conto via Plaid.</small>
</div>

<script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
<script>
    (function () {
        const btn = document.getElementById('plaidLinkBtn');
        if (!btn) return;

        btn.addEventListener('click', async () => {
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
                if (!data.link_token) {
                    alert('Cannot create Plaid link token'); return;
                }

                const handler = Plaid.create({
                    token: data.link_token,
                    onSuccess: async function(public_token, metadata) {
                        await fetch("{{ route('plaid.exchange') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                public_token: public_token,
                                institution: metadata.institution || null
                            })
                        });
                        window.location.reload();
                    },
                    onExit: function(err, metadata) {
                        if (err) console.error('Plaid exit', err, metadata);
                    }
                });
                handler.open();
            } catch (e) {
                console.error(e);
                alert('Could not start Plaid Link.');
            }
        });
    })();
</script>
