@if (Auth::user()->has_completed_setup == true)

    @php
        $categories   = $categories   ?? \App\Models\Budget::where('user_id', auth()->id())->get();
        $bankAccounts = $bankAccounts ?? \App\Models\BankAccount::where('user_id', auth()->id())->orderBy('account_name')->get();
        $budgetCategories = \App\Models\BudgetCategory::where('user_id', auth()->id())->orderBy('name')->get();
    @endphp

    <div class="floatingQuickAddDropUp dropup">
        <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-plus"></i>
        </button>
        <ul class="dropdown-menu">
            <li>
                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#addBudget">
                    Aggiungi categoria budget
                </button>
            </li>
            <li>
                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#addTransaction">
                    Aggiungi transazione
                </button>
            </li>
            <li>
                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#fundTransfer">
                    Trasferimento fondi
                </button>
            </li>
            <li>
                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#addBankAccount">
                    Aggiungi conto bancario
                </button>
            </li>
        </ul>
    </div>

    {{-- Aggiungi Budget --}}
    <div class="modal fade" id="addBudget" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h1>Aggiungi voce di budget</h1>

                    <form action="{{ route('budget.global-add-budget') }}" method="post" id="addBudgetForm">
                        @csrf
                        <input type="hidden" name="name" id="budgetCategoryName" value="">

                        <div class="mb-3">
                            <label class="mb-2" style="color:#EAFBFF;">Categoria</label>

                            <div class="catDropdown" id="catDropdown">
                                <div class="catDropdownTrigger" id="catDropdownTrigger">
                                    <span class="catDropdownPlaceholder" id="catDropdownLabel">Seleziona una categoria...</span>
                                    <i class="fa-solid fa-chevron-down catDropdownArrow"></i>
                                </div>
                                <div class="catDropdownMenu" id="catDropdownMenu">
                                    <div class="catDropdownSearch">
                                        <input type="text" id="catSearchInput" placeholder="Cerca categoria..." autocomplete="off">
                                    </div>
                                    <div class="catDropdownList" id="catDropdownList">
                                        @foreach($budgetCategories as $cat)
                                            <div class="catDropdownItem" data-value="{{ $cat->name }}" data-icon="{{ $cat->icon ?? 'fa-solid fa-tag' }}">
                                                <i class="{{ $cat->icon ?? 'fa-solid fa-tag' }} catItemIcon"></i>
                                                <span>{{ ucfirst(str_replace('_', ' ', $cat->name)) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="catDropdownCustom" id="catDropdownCustomBtn">
                                        <i class="fa-solid fa-plus catItemIcon" style="color:#44E0AC;"></i>
                                        <span>Crea categoria personalizzata</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3" id="customCategoryRow" style="display:none;">
                            <label class="mb-2" style="color:#EAFBFF;">Nome categoria personalizzata</label>
                            <div style="display:flex;gap:8px;">
                                <input type="text" class="form-control" id="customCategoryInput"
                                       placeholder="Es. palestra, Netflix..."
                                       style="background:#1D373B;color:#fff;border:1px solid rgba(255,255,255,0.14);border-radius:10px;padding:12px;flex:1;">
                                <button type="button" id="customCategoryBack" class="btn btn-sm"
                                        style="background:rgba(255,255,255,0.06);color:#fff;border:1px solid rgba(255,255,255,0.12);border-radius:10px;padding:8px 14px;">
                                    <i class="fa-solid fa-arrow-left"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="mb-2" style="color:#EAFBFF;">Importo budget</label>
                            <div class="input-group">
                                <label class="input-group-text"
                                       style="background:#1D373B;color:#44E0AC;border:1px solid rgba(255,255,255,0.14);">€</label>
                                <input type="number" min="0" step="any"
                                       name="amount" placeholder="0.00" required
                                       class="form-control"
                                       style="background:#1D373B;color:#fff;border:1px solid rgba(255,255,255,0.14);">
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox"
                                   name="include_internal_transfers"
                                   id="include_internal_transfers" value="1">
                            <label class="form-check-label fw-semibold"
                                   for="include_internal_transfers">
                                Conta i <strong>trasferimenti interni</strong> come spesa per questa categoria (es. contributi pensione)
                            </label>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2" data-loading-text="Salvataggio...">
                                    Salva
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        #addBudget .form-check-label{ color: #EAFBFF !important; }
        #addBudget .form-check-input{ border-color: #44E0AC !important; background-color: transparent; }
        #addBudget .form-check-input:checked{ background-color: #44E0AC !important; border-color: #44E0AC !important; }
        #addBudget .form-check-input:focus{ box-shadow: 0 0 0 .2rem rgba(68,224,172,.25) !important; }

        .catDropdown{ position: relative; }
        .catDropdownTrigger{
            display: flex; align-items: center; justify-content: space-between;
            background: #1D373B; color: #fff; border: 1px solid rgba(255,255,255,0.14);
            border-radius: 10px; padding: 12px 14px; cursor: pointer; transition: all 0.2s;
        }
        .catDropdownTrigger:hover{ border-color: rgba(68,224,172,0.4); }
        .catDropdownTrigger.open{ border-color: rgba(68,224,172,0.5); border-radius: 10px 10px 0 0; }
        .catDropdownPlaceholder{ opacity: 0.6; }
        .catDropdownArrow{ transition: transform 0.2s; font-size: 0.8rem; opacity: 0.5; }
        .catDropdownTrigger.open .catDropdownArrow{ transform: rotate(180deg); }

        .catDropdownSelected{ display: flex; align-items: center; gap: 10px; }
        .catDropdownSelected i{ color: #44E0AC; width: 20px; text-align: center; }

        .catDropdownMenu{
            display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 1050;
            background: #1D373B; border: 1px solid rgba(68,224,172,0.3); border-top: none;
            border-radius: 0 0 10px 10px; max-height: 280px; overflow: hidden;
            box-shadow: 0 12px 32px rgba(0,0,0,0.4);
        }
        .catDropdownMenu.show{ display: block; }

        .catDropdownSearch{ padding: 8px 10px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .catDropdownSearch input{
            width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; padding: 8px 12px; color: #fff; font-size: 0.9rem; outline: none;
        }
        .catDropdownSearch input::placeholder{ color: rgba(255,255,255,0.35); }
        .catDropdownSearch input:focus{ border-color: rgba(68,224,172,0.4); }

        .catDropdownList{ max-height: 200px; overflow-y: auto; padding: 4px 0; }
        .catDropdownList::-webkit-scrollbar{ width: 6px; }
        .catDropdownList::-webkit-scrollbar-track{ background: transparent; }
        .catDropdownList::-webkit-scrollbar-thumb{ background: rgba(255,255,255,0.12); border-radius: 3px; }

        .catDropdownItem{
            display: flex; align-items: center; gap: 12px; padding: 10px 14px;
            cursor: pointer; transition: all 0.15s; color: rgba(255,255,255,0.85);
        }
        .catDropdownItem:hover{ background: rgba(68,224,172,0.08); color: #fff; }
        .catDropdownItem.hidden{ display: none; }
        .catItemIcon{ color: #44E0AC; width: 20px; text-align: center; font-size: 0.95rem; }

        .catDropdownCustom{
            display: flex; align-items: center; gap: 12px; padding: 12px 14px;
            cursor: pointer; transition: all 0.15s; color: rgba(255,255,255,0.7);
            border-top: 1px solid rgba(255,255,255,0.06); font-weight: 700;
        }
        .catDropdownCustom:hover{ background: rgba(68,224,172,0.06); color: #44E0AC; }
    </style>

    {{-- Aggiungi Transazione --}}
    <div class="modal fade" id="addTransaction" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h1>Aggiungi transazione</h1>
                    <form action="{{ route('transactions.global-add-transaction') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <label for="gt_name">Nome esercizio/persona *</label>
                                <input type="text" name="name" id="gt_name" value="{{ old('name') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="gt_date">Data *</label>
                                <input type="date" name="date" id="gt_date" value="{{ old('date') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="gt_category">Categoria *</label>
                                <select name="category" id="gt_category">
                                    <option value="" disabled selected>Seleziona una categoria...</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" style="text-transform: capitalize !important;">
                                            {{ str_replace('_', ' ', $cat->category_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="gt_bank_account_id">Conto bancario *</label>
                                <select name="bank_account_id" id="gt_bank_account_id">
                                    <option value="" disabled selected>Seleziona un conto...</option>
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ str_replace('_', ' ', $account->account_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row" id="gt_transfer_row" style="display:none;">
                            <div class="col-12">
                                <label for="gt_transfer_to">Trasferisci a (opzionale)</label>
                                <select name="transfer_to_account" id="gt_transfer_to" disabled>
                                    <option value="" selected>-- scegli destinazione --</option>
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ str_replace('_', ' ', $account->account_name) }}</option>
                                    @endforeach
                                </select>
                                <small class="internal-note d-block mt-1">
                                    Se scegli una destinazione, verranno create due transazioni (uscita da → entrata su).
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="gt_amount">Importo *</label>
                                <input type="number" name="amount" id="gt_amount" step="any" value="{{ old('amount') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label>Tipo di transazione *</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="gt_expense" value="expense" required checked>
                                    <label class="form-check-label" for="gt_expense">Uscita</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="gt_income" value="income" required>
                                    <label class="form-check-label" for="gt_income">Entrata</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="input-group">
                                    <input type="checkbox" name="internal_transfer" id="gt_internal_transfer" value="1">
                                    <label for="gt_internal_transfer">Trasferimento interno</label>
                                </div>
                                <small class="internal-note d-block mt-1">
                                    Se la categoria selezionata è impostata per "contare i trasferimenti interni", verrà conteggiata come spesa a budget.
                                </small>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2" data-loading-text="Salvataggio...">Salva</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        #addTransaction .internal-note{ color:#EAFBFF !important; opacity:.95 }
        #addTransaction .form-check-label{ color:#EAFBFF !important; }
    </style>

    {{-- Trasferimento Fondi --}}
    <div class="modal fade" id="fundTransfer" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Chiudi"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <h1>Trasferimento fondi</h1>
                    <form action="{{ route('transactions.global-fund-transfer') }}" method="post">
                        @csrf

                        <div class="row">
                            <div class="col-12">
                                <label for="ft_category">Categoria (opzionale)</label>
                                <select name="category" id="ft_category">
                                    <option value="" selected>-- nessuna --</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">
                                            {{ str_replace('_', ' ', $cat->category_name) }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">
                                    L'<strong>uscita</strong> (Da) sarà associata a questa categoria.
                                    Se quella categoria conta i trasferimenti interni, verrà inclusa nel budget.
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="ft_name">Nome esercizio/persona *</label>
                                <input type="text" name="name" id="ft_name" value="{{ old('name') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="ft_date">Data *</label>
                                <input type="date" name="date" id="ft_date" value="{{ old('date') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="from_account">Da *</label>
                                <select name="from_account" id="from_account">
                                    <option value="" disabled selected>Seleziona un conto...</option>
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ str_replace('_', ' ', $account->account_name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="to_account">A *</label>
                                <select name="to_account" id="to_account">
                                    <option value="" disabled selected>Seleziona un conto...</option>
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ str_replace('_', ' ', $account->account_name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="ft_amount">Importo *</label>
                                <input type="number" name="amount" id="ft_amount" step="any" value="{{ old('amount') }}">
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2">Salva</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Aggiungi Conto Bancario --}}
    <div class="modal fade" id="addBankAccount" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Chiudi"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <h1>Aggiungi conto bancario</h1>
                    <form action="{{ route('bank-accounts.global-add-bank-account') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <label for="name_of_bank_account">Nome della banca</label>
                                <input type="text" name="name_of_bank_account" id="name_of_bank_account" value="{{ old('name_of_bank_account') }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="bank_account_type">Tipo di conto</label>
                                <select name="bank_account_type" id="bank_account_type">
                                    <option value="" disabled selected>Seleziona un'opzione...</option>
                                    <option value="current_account">Conto corrente</option>
                                    <option value="savings_account">Conto risparmio</option>
                                    <option value="isa_account">Conto ISA</option>
                                    <option value="investment_account">Conto investimenti</option>
                                    <option value="pension">Pensione</option>
                                    <option value="investment">Investimenti</option>
                                    <option value="credit_card">Carta di credito</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="bank_account_starting_balance">Saldo iniziale</label>
                                <input type="number" name="bank_account_starting_balance" id="bank_account_starting_balance" step="any" value="{{ old('bank_account_starting_balance') }}">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2" data-loading-text="Salvataggio...">Salva</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

<style>
    /* Fix dropdown bianchi nei modal */
    .modal select,
    .modal select option {
        background-color: #1D373B !important;
        color: #ffffff !important;
        border: 1px solid rgba(255,255,255,0.14) !important;
    }
    .modal select:focus {
        outline: none !important;
        box-shadow: 0 0 0 0.2rem rgba(68,224,172,0.18) !important;
        border-color: rgba(68,224,172,0.5) !important;
    }
</style>


<script>
    // Spinner e blocco doppio submit
    document.querySelectorAll('.modal form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const loadingText = submitBtn.dataset.loadingText || 'Salvataggio...';
                submitBtn.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ${loadingText}
                `;
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50');
            }
        });
    });

    // Toggle "Transfer To"
    document.addEventListener('DOMContentLoaded', function(){
        const it  = document.getElementById('gt_internal_transfer');
        const row = document.getElementById('gt_transfer_row');
        const sel = document.getElementById('gt_transfer_to');
        if (!it || !row || !sel) return;
        const sync = () => {
            const on = it.checked;
            row.style.display = on ? '' : 'none';
            sel.disabled = !on;
            if (!on) sel.value = '';
        };
        it.addEventListener('change', sync);
        sync();
    });

    // Custom budget category dropdown con icone
    document.addEventListener('DOMContentLoaded', function() {
        const trigger = document.getElementById('catDropdownTrigger');
        const menu = document.getElementById('catDropdownMenu');
        const list = document.getElementById('catDropdownList');
        const searchInput = document.getElementById('catSearchInput');
        const label = document.getElementById('catDropdownLabel');
        const hiddenName = document.getElementById('budgetCategoryName');
        const customRow = document.getElementById('customCategoryRow');
        const customInput = document.getElementById('customCategoryInput');
        const customBtn = document.getElementById('catDropdownCustomBtn');
        const customBack = document.getElementById('customCategoryBack');
        const dropdown = document.getElementById('catDropdown');

        if (!trigger || !menu || !list || !hiddenName) return;

        let isOpen = false;
        let isCustomMode = false;

        function openMenu() {
            isOpen = true;
            menu.classList.add('show');
            trigger.classList.add('open');
            if (searchInput) { searchInput.value = ''; filterItems(''); searchInput.focus(); }
        }
        function closeMenu() {
            isOpen = false;
            menu.classList.remove('show');
            trigger.classList.remove('open');
        }

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            if (isCustomMode) return;
            isOpen ? closeMenu() : openMenu();
        });

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) closeMenu();
        });

        function filterItems(query) {
            const items = list.querySelectorAll('.catDropdownItem');
            const q = query.toLowerCase().trim();
            items.forEach(item => {
                const name = item.querySelector('span').textContent.toLowerCase();
                item.classList.toggle('hidden', q !== '' && !name.includes(q));
            });
        }
        if (searchInput) {
            searchInput.addEventListener('input', function() { filterItems(this.value); });
            searchInput.addEventListener('click', function(e) { e.stopPropagation(); });
        }

        list.addEventListener('click', function(e) {
            const item = e.target.closest('.catDropdownItem');
            if (!item) return;

            const value = item.dataset.value;
            const icon = item.dataset.icon;
            const text = item.querySelector('span').textContent;

            hiddenName.value = value;
            label.innerHTML = '<span class="catDropdownSelected"><i class="' + icon + '"></i> ' + text + '</span>';
            label.classList.remove('catDropdownPlaceholder');

            isCustomMode = false;
            customRow.style.display = 'none';
            customInput.value = '';
            customInput.required = false;

            closeMenu();
        });

        if (customBtn) {
            customBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                isCustomMode = true;
                closeMenu();
                customRow.style.display = '';
                customInput.required = true;
                customInput.focus();
                trigger.style.display = 'none';
                hiddenName.value = '';
                label.innerHTML = 'Seleziona una categoria...';
                label.classList.add('catDropdownPlaceholder');
            });
        }

        if (customBack) {
            customBack.addEventListener('click', function() {
                isCustomMode = false;
                customRow.style.display = 'none';
                customInput.value = '';
                customInput.required = false;
                trigger.style.display = '';
                hiddenName.value = '';
            });
        }

        if (customInput) {
            customInput.addEventListener('input', function() {
                hiddenName.value = this.value;
            });
        }

        const form = document.getElementById('addBudgetForm');
        if (form) {
            form.addEventListener('submit', function() {
                if (isCustomMode) {
                    hiddenName.value = customInput.value;
                }
            });
        }
    });
</script>

<script src="{{ asset('/sw.js') }}"></script>
<script>
    if ("serviceWorker" in navigator) {
        navigator.serviceWorker.register("/sw.js").then(
            (registration) => { console.log("Service worker registration succeeded:", registration); },
            (error) => { console.error(`Service worker registration failed: ${error}`); },
        );
    } else {
        console.error("Service workers are not supported.");
    }
</script>
