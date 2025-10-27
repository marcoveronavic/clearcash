<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Plaid Demo • ClearCash (Sandbox)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Plaid Link SDK -->
    <script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
    <style>
        :root { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; }
        body { margin: 24px; }
        .wrap { max-width: 980px; margin: 0 auto; }
        .row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin: 12px 0 18px; }
        button { padding: 10px 14px; border-radius: 10px; border: 1px solid #e5e7eb; cursor: pointer; font-weight: 600; }
        button.primary { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }
        button.ghost { background: #f3f4f6; }
        pre, code { background: #0b1020; color: #d0e8ff; padding: 12px; border-radius: 8px; overflow: auto; }
        table { border-collapse: collapse; width: 100%; margin-top: 12px; }
        th, td { text-align: left; border-bottom: 1px solid #eee; padding: 8px; }
        .muted { color: #6b7280; font-size: .95rem; }
        .ok { color: #059669; }
        .err { color: #b91c1c; }
        .badge { display:inline-block; background:#e5e7eb; padding:.15rem .5rem; border-radius:.375rem; font-size:.8rem; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Plaid Link — Demo (Sandbox)</h1>
    <p class="muted">
        Flusso completo: <code>link_token</code> → Plaid Link → <code>/api/plaid/exchange</code> → <code>accounts</code> → <code>transactions/sync-store</code>.
    </p>

    <div class="row">
        <button id="btn-link" class="primary">🔗 Collega banca (Plaid Link)</button>
        <button id="btn-refresh" class="ghost" title="Rigenera link_token">♻️ Rigenera link_token</button>
        <span id="status" class="badge">idle</span>
    </div>

    <div class="row">
        <div><strong>bank_connection_id:</strong> <span id="connId">—</span></div>
        <div><strong>item_id:</strong> <span id="itemId">—</span></div>
    </div>

    <h3>Accounts</h3>
    <div id="accountsWrap" class="muted">—</div>

    <h3>Sync</h3>
    <div id="syncWrap" class="muted">—</div>

    <h3>Log</h3>
    <pre id="log" style="min-height: 140px;"></pre>
</div>

<script>
    const $ = s => document.querySelector(s);
    const log = (msg, obj) => {
        const line = `[${new Date().toISOString()}] ${msg}`;
        $('#log').textContent += line + (obj ? ' ' + JSON.stringify(obj, null, 2) : '') + "\n";
        $('#log').scrollTop = $('#log').scrollHeight;
        console.log(msg, obj || '');
    };
    const setStatus = (txt, cls='') => { const el=$('#status'); el.textContent = txt; el.className = 'badge ' + cls; };

    let handler = null;            // Plaid Link handler
    let bankConnectionId = null;   // salvato dopo exchange
    let lastItemId = null;

    function renderAccounts(list) {
        if (!list || !list.length) { $('#accountsWrap').innerHTML = '<div class="muted">— nessun account —</div>'; return; }
        let html = '<table><thead><tr><th>Nome</th><th>Mask</th><th>Tipo</th><th>Subtipo</th><th>Saldo disp.</th><th>Saldo corr.</th><th>Valuta</th></tr></thead><tbody>';
        for (const a of list) {
            const bal = a.balances || {};
            html += `<tr>
        <td>${a.name ?? ''}</td>
        <td>${a.mask ?? ''}</td>
        <td>${a.type ?? ''}</td>
        <td>${a.subtype ?? ''}</td>
        <td>${bal.available ?? ''}</td>
        <td>${bal.current ?? ''}</td>
        <td>${bal.iso_currency_code ?? ''}</td>
      </tr>`;
        }
        html += '</tbody></table>';
        $('#accountsWrap').innerHTML = html;
    }

    function renderSync(res) {
        if (!res || typeof res !== 'object') { $('#syncWrap').innerHTML = '<div class="muted">—</div>'; return; }
        const c = res.counters || {};
        $('#syncWrap').innerHTML = `
      <div>next_cursor: <code>${res.next_cursor || ''}</code></div>
      <div>added: <b>${c.added ?? 0}</b>, modified: <b>${c.modified ?? 0}</b>, removed: <b>${c.removed ?? 0}</b>, loops: <b>${c.loops ?? '-'}</b></div>
      <div class="muted">bank_connection_id: ${res.bank_connection_id ?? '-'} | request_id: ${res.request_id ?? '-'}</div>
    `;
    }

    async function createLinkToken() {
        setStatus('creo link_token…');
        const res = await fetch('/api/plaid/link-token', {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'Accept':'application/json' },
            body: JSON.stringify({ products:['transactions'], country_codes:['US','GB'], language:'en' })
        });
        const json = await res.json();
        log('link-token', json);
        if (!res.ok || !json.link_token) throw new Error('link-token failed');
        setStatus('link_token ok ✅', 'ok');
        return json.link_token;
    }

    async function initLink() {
        const linkToken = await createLinkToken();

        handler = Plaid.create({
            token: linkToken,
            onSuccess: async (public_token, metadata) => {
                log('onSuccess', { public_token, metadata });
                setStatus('exchange…');
                const payload = {
                    public_token,
                    institution_id: metadata?.institution?.institution_id || null,
                    institution_name: metadata?.institution?.name || null
                };
                const resp = await fetch('/api/plaid/exchange', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'Accept':'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await resp.json();
                log('exchange', data);
                if (!resp.ok || data?.saved !== true) { setStatus('exchange error', 'err'); return; }

                bankConnectionId = data.bank_connection_id; lastItemId = data.item_id;
                $('#connId').textContent = bankConnectionId;
                $('#itemId').textContent = lastItemId;
                setStatus('connessione salvata ✅', 'ok');

                // Accounts
                setStatus('accounts…');
                const accRes = await fetch(`/api/plaid/accounts/${bankConnectionId}`, { headers: {'Accept':'application/json'} });
                const accJson = await accRes.json();
                renderAccounts(accJson.accounts);
                log('accounts', accJson);

                // Sync & store
                setStatus('sync-store…');
                const sRes = await fetch('/api/plaid/transactions/sync-store', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'Accept':'application/json' },
                    body: JSON.stringify({ bank_connection_id: bankConnectionId, count: 100 })
                });
                const sJson = await sRes.json();
                renderSync(sJson);
                log('sync-store', sJson);
                setStatus('ready ✅', 'ok');
            },
            onExit: (err, metadata) => {
                log('onExit', { err, metadata });
                setStatus(err ? 'exit con errore' : 'exit');
            },
            onEvent: (name, metadata) => {
                log('event ' + name, metadata);
            },
            // Necessario per i flussi Open Banking (redirect OAuth UK/EU)
            receivedRedirectUri: window.location.href.includes('/plaid/oauth-return') ? window.location.href : undefined,
        });
    }

    $('#btn-link').addEventListener('click', async () => {
        try {
            $('#btn-link').disabled = true;
            if (!handler) await initLink();
            handler.open();
        } catch (e) {
            log('init error', e); setStatus('errore init', 'err');
        } finally {
            $('#btn-link').disabled = false;
        }
    });

    $('#btn-refresh').addEventListener('click', async () => {
        try {
            setStatus('rigenero link_token…');
            handler = null;
            await initLink();
            setStatus('link_token rigenerato ✅', 'ok');
        } catch (e) {
            log('refresh error', e); setStatus('errore rigenerazione', 'err');
        }
    });

    // Se torni dal redirect OAuth, inizializza in automatico e riapri Link
    if (window.location.pathname === '/plaid/oauth-return') {
        (async () => {
            try { await initLink(); handler.open(); }
            catch(e){ log('oauth-return init error', e); setStatus('errore post-redirect', 'err'); }
        })();
    }
</script>
</body>
</html>

