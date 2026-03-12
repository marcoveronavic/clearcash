{{--
    Sezione ricevuta — include dentro il modal di modifica transazione
    Uso: @include('customer.pages.transactions.partials.receipt-section', ['transaction' => $transaction])
--}}

<style>
    .receipt-section { margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,255,255,0.06); }
    .receipt-preview { position:relative; display:inline-block; border-radius:10px; overflow:hidden; border:1px solid #1D3838; }
    .receipt-preview img { max-width:100%; max-height:200px; display:block; border-radius:10px; }
    .receipt-preview .receipt-overlay {
        position:absolute; inset:0; background:rgba(0,0,0,0.5); display:flex;
        align-items:center; justify-content:center; gap:10px; opacity:0; transition:opacity .2s;
    }
    .receipt-preview:hover .receipt-overlay { opacity:1; }
    .receipt-overlay a, .receipt-overlay button {
        padding:6px 14px; border-radius:8px; font-size:12px; font-weight:600;
        text-decoration:none; cursor:pointer; border:none;
    }
    .receipt-overlay a { background:rgba(45,212,191,0.2); color:#2DD4BF; border:1px solid rgba(45,212,191,0.3); }
    .receipt-overlay button { background:rgba(248,113,113,0.2); color:#F87171; border:1px solid rgba(248,113,113,0.3); }
    .receipt-upload-area {
        border:2px dashed #1D3838; border-radius:12px; padding:20px; text-align:center;
        cursor:pointer; transition:all .2s; position:relative;
    }
    .receipt-upload-area:hover { border-color:#2DD4BF; background:rgba(45,212,191,0.03); }
    .receipt-upload-area input[type="file"] {
        position:absolute; inset:0; opacity:0; cursor:pointer;
    }
    .receipt-badge {
        display:inline-flex; align-items:center; gap:4px;
        padding:3px 8px; border-radius:6px; font-size:10px; font-weight:600;
    }
    .receipt-badge-yes { background:rgba(45,212,191,0.12); color:#2DD4BF; }
    .receipt-badge-no { background:rgba(90,144,144,0.1); color:#5A9090; }
    .receipt-uploading {
        display:none; align-items:center; gap:8px; margin-top:8px;
        color:#2DD4BF; font-size:13px;
    }
    .receipt-uploading.active { display:flex; }
    .receipt-error {
        display:none; margin-top:8px; color:#F87171; font-size:13px;
    }
    .receipt-error.active { display:block; }
</style>

<div class="receipt-section" id="receipt-section-{{ $transaction->id }}">
    <label style="color:#fff !important; font-size:14px; font-weight:600; margin-bottom:8px; display:block;">
        <i class="fa-solid fa-receipt" style="color:#2DD4BF; margin-right:6px;"></i> Ricevuta
    </label>

    @if ($transaction->receipt_path)
        {{-- Preview ricevuta esistente --}}
        <div class="receipt-preview">
            <img src="{{ asset('storage/' . $transaction->receipt_path) }}" alt="Ricevuta">
            <div class="receipt-overlay">
                <a href="{{ asset('storage/' . $transaction->receipt_path) }}" target="_blank">
                    <i class="fa-solid fa-expand"></i> Apri
                </a>
                <form action="{{ route('transactions.delete-receipt', $transaction->id) }}" method="POST" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Eliminare la ricevuta?')">
                        <i class="fa-solid fa-trash"></i> Elimina
                    </button>
                </form>
            </div>
        </div>

        {{-- Sostituisci ricevuta via JS --}}
        <label style="display:inline-block; margin-top:8px; padding:6px 14px; border-radius:8px; font-size:12px; font-weight:500;
                      background:#112828; color:#5A9090; border:1px solid #1D3838; cursor:pointer;">
            <i class="fa-solid fa-rotate me-1"></i> Sostituisci
            <input type="file" name="receipt" accept="image/*" style="display:none;"
                   class="receipt-file-input" data-transaction-id="{{ $transaction->id }}"
                   data-upload-url="{{ route('transactions.upload-receipt', $transaction->id) }}">
        </label>
    @else
        {{-- Upload nuova ricevuta via JS --}}
        <div class="receipt-upload-area">
            <input type="file" name="receipt" accept="image/*"
                   class="receipt-file-input" data-transaction-id="{{ $transaction->id }}"
                   data-upload-url="{{ route('transactions.upload-receipt', $transaction->id) }}">
            <i class="fa-solid fa-camera" style="font-size:22px; color:#2DD4BF; margin-bottom:6px;"></i>
            <p style="color:#5A9090; font-size:13px; margin:0;">
                Clicca o trascina per caricare la ricevuta
            </p>
            <span style="color:#3A6666; font-size:11px;">JPG, PNG, WebP — max 5MB</span>
        </div>
    @endif

    <div class="receipt-uploading" id="receipt-uploading-{{ $transaction->id }}">
        <span class="spinner-border spinner-border-sm" role="status"></span> Caricamento in corso...
    </div>
    <div class="receipt-error" id="receipt-error-{{ $transaction->id }}"></div>
</div>

<script>
    (function(){
        var section = document.getElementById('receipt-section-{{ $transaction->id }}');
        if (!section) return;
        var fileInput = section.querySelector('.receipt-file-input');
        if (!fileInput) return;

        fileInput.addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;

            var txId = this.dataset.transactionId;
            var url  = this.dataset.uploadUrl;
            var loadingEl = document.getElementById('receipt-uploading-' + txId);
            var errorEl   = document.getElementById('receipt-error-' + txId);

            errorEl.classList.remove('active');
            errorEl.textContent = '';
            loadingEl.classList.add('active');

            var formData = new FormData();
            formData.append('receipt', file, file.name);
            formData.append('_token', '{{ csrf_token() }}');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    window.location.reload();
                } else {
                    loadingEl.classList.remove('active');
                    errorEl.textContent = 'Errore nel caricamento: ' + xhr.status + ' - ' + xhr.responseText;
                    errorEl.classList.add('active');
                }
            };

            xhr.onerror = function() {
                loadingEl.classList.remove('active');
                errorEl.textContent = 'Errore di rete nel caricamento.';
                errorEl.classList.add('active');
            };

            xhr.send(formData);
        });
    })();
</script>
