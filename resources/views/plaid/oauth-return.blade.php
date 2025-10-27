<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Plaid OAuth Return</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
<script>
    (async function () {
        try {
            const res = await fetch("{{ route('plaid.link-token') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({})
            });
            const data = await res.json();
            if (!data.link_token) throw new Error('No link_token');

            const handler = Plaid.create({
                token: data.link_token,
                receivedRedirectUri: window.location.href,
                onSuccess: async function(public_token, metadata) {
                    await fetch("{{ route('plaid.exchange') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ public_token, institution: metadata.institution || null })
                    });
                    window.location.href = "{{ route('account-setup.step-five') }}";
                },
                onExit: function() {
                    window.location.href = "{{ route('account-setup.step-five') }}";
                }
            });

            handler.open();
        } catch (e) {
            console.error(e);
            window.location.href = "{{ route('account-setup.step-five') }}";
        }
    })();
</script>
</body>
</html>
