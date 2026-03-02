@extends('layouts.customer')

@section('styles_in_head')
    <link rel="stylesheet" href="{{ asset('build/assets/account-setup.css') }}">
@endsection

@section('content')
    @php
        $connectedAccounts = \App\Models\BankAccount::where('user_id', auth()->id())
            ->orderBy('account_name')
            ->get();
    @endphp

    <style>
        header, aside.sidebar { display:none; }
        main.dashboardMain { padding-top:2rem; width:100%; }
        main.dashboardMain.full { padding-top:2rem; }

        /* ---------- CTA row ---------- */
        .actions-row{
            display:flex;
            justify-content:center;
            align-items:center;
            gap:16px;
            flex-wrap:wrap;
        }
        .actions-row > *{ margin:0 !important; }
        .actions-row :where(a,button).twoToneBlueGreenBtn,
        .actions-row .cta-btn,
        .actions-row #toggleManual{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:14px 20px;
            border-radius:12px;
            font-weight:800;
            letter-spacing:.2px;
            min-width:260px;
            height:56px;
            text-decoration:none;
        }
        .actions-row #toggleManual{
            background:linear-gradient(90deg,#58f0a8,#43caff);
            color:#052026;
            border:0;
            box-shadow:0 0 0 1px rgba(0,0,0,.12) inset;
        }
        .actions-row #toggleManual:hover{
            filter:brightness(1.03);
            transform:translateY(-1px);
        }
        @media (max-width:576px){
            .actions-row :where(a,button).twoToneBlueGreenBtn,
            .actions-row #toggleManual{
                width:100%;
                min-width:0;
                height:54px;
            }
        }

        /* ---------- Connected accounts ---------- */
        .sep-title { margin:18px 0 8px; color: rgba(88,240,168,0.85); font-weight:800; letter-spacing:.2px; }
        .connected-accounts .bankItem {
            padding:14px 16px;
            border-radius:10px;
            margin-bottom:10px;
            border:1px solid rgba(67,202,255,0.14);
            background:rgba(67,202,255,0.04);
        }
        .connected-accounts .meta { color: rgba(255,255,255,0.82); font-size:.9rem; }
        .connected-accounts strong { color: rgba(255,255,255,0.95); }
        .connected-accounts .balance { font-weight:700; color: rgba(255,255,255,0.90); }
        p.text-muted { color: rgba(255,255,255,0.70) !important; }

        /* ✅ Add another bank account link (under) */
        .addAnotherInlineWrap{
            text-align:center;
            margin-top: 18px;
        }
        .addAnotherInlineLink{
            display:inline-flex;
            align-items:center;
            gap:8px;
            font-weight:800;
            color:#43caff;
            text-decoration:none;
        }
        .addAnotherInlineLink:hover{
            color:#58f0a8;
            text-decoration:none;
        }

        /* ✅ mini actions */
        .manualCardActions{
            margin-top:10px;
            display:flex;
            justify-content:flex-end;
            gap:10px;
        }
        .editBtnMini{
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.92);
            border:1px solid rgba(67,202,255,0.18);
            padding:8px 14px;
            border-radius:12px;
            cursor:pointer;
            font-weight:800;
        }
        .editBtnMini:hover{
            border-color: rgba(88,240,168,0.28);
            background: rgba(88,240,168,0.06);
            transform: translateY(-1px);
        }
        .removeBtnMini{
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.92);
            border:1px solid rgba(217,83,79,0.30);
            padding:8px 14px;
            border-radius:12px;
            cursor:pointer;
            font-weight:800;
        }
        .removeBtnMini:hover{
            background: rgba(217,83,79,0.10);
            border-color: rgba(217,83,79,0.50);
            transform: translateY(-1px);
        }

        /* ======================================================
           MANUAL: Edit form items
           ====================================================== */
        .bankDetailsInputMainWrap .bankItem{
            border:1px solid rgba(67,202,255,0.16);
            background: rgba(15, 34, 34, 0.45);
            border-radius:16px;
            padding:16px 16px;
            margin-bottom:14px;
            box-shadow: 0 0 0 1px rgba(0,0,0,0.08) inset;
        }
        .creditNote { color:#d9534f; font-size:.9em; margin-top:6px; display:none; }

        .saveBankBtn{
            background:#28a745;
            color:#fff;
            border:none;
            padding:10px 16px;
            cursor:pointer;
            border-radius:14px;
            font-weight:800;
        }
        .saveBankBtn:disabled{ opacity:.75; cursor:not-allowed; }

        .cancelBankBtn{
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.92);
            border:1px solid rgba(217,83,79,0.30);
            padding:10px 16px;
            border-radius:14px;
            cursor:pointer;
            font-weight:800;
        }
        .cancelBankBtn:hover{
            background: rgba(217,83,79,0.10);
            border-color: rgba(217,83,79,0.50);
            transform: translateY(-1px);
        }

        /* ✅ Salary checkbox row */
        .salaryRowLabel{
            display:flex;
            align-items:center;
            gap:10px;
            cursor:pointer;
            user-select:none;
        }
        .salaryRowLabel input[type="checkbox"]{
            width:18px;
            height:18px;
            accent-color:#58f0a8;
        }
        .salaryHint{
            color: rgba(255,255,255,0.70);
            font-size:.9em;
            margin-top:6px;
        }
        .salaryBadge{
            display:inline-flex;
            align-items:center;
            gap:6px;
            font-weight:800;
            font-size:.78rem;
            padding:4px 10px;
            border-radius:999px;
            background: rgba(88,240,168,0.16);
            border:1px solid rgba(88,240,168,0.24);
            color: rgba(255,255,255,0.92);
            margin-left:10px;
        }

        /* ✅ SweetAlert2: make text readable on dark background */
        .swal2-popup {
            background: rgba(15, 34, 34, 0.98) !important;
            border: 1px solid rgba(67, 202, 255, 0.18) !important;
            box-shadow: 0 18px 40px rgba(0,0,0,0.45) !important;
        }
        .swal2-title {
            color: rgba(255,255,255,0.96) !important;
        }
        .swal2-html-container,
        .swal2-content,
        .swal2-text {
            color: rgba(255,255,255,0.85) !important;
        }
        .swal2-icon.swal2-warning {
            border-color: rgba(88, 240, 168, 0.70) !important;
            color: rgba(88, 240, 168, 0.95) !important;
        }
        .swal2-actions .swal2-confirm,
        .swal2-actions .swal2-cancel {
            font-weight: 900 !important;
        }
        .swal2-actions .swal2-confirm {
            color: #052026 !important;
            background: linear-gradient(90deg,#58f0a8,#43caff) !important;
            border: 0 !important;
        }
        .swal2-actions .swal2-cancel {
            background: rgba(255,255,255,0.12) !important;
            border: 1px solid rgba(255,255,255,0.18) !important;
            color: rgba(255,255,255,0.92) !important;
        }
    </style>

    <section class="setupStepsWrapper">
        <div class="container">

            {{-- Stepper --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item">Create your budget</div>
                            <div class="sep"></div>
                            <div class="item active">Add bank accounts</div>
                            <div class="sep"></div>
                            <div class="item">Add your investments and pensions</div>
                            <div class="sep"></div>
                            <div class="item">Done</div>
                        </div>
                        <div class="boxes">
                            <div class="box active"></div>
                            <div class="box active"></div>
                            <div class="box active"></div>
                            <div class="box active"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Header + buttons --}}
            <div class="row">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1>Add bank accounts</h1>

                    <div class="actions-row mb-3">
                        @include('customer.pages.bank-accounts._plaid_link')

                        {{-- Only show the CTA if no accounts yet --}}
                        @if($connectedAccounts->isEmpty())
                            <a id="toggleManual" href="#" class="twoToneBlueGreenBtn cta-btn">
                                + Add bank account manually
                            </a>
                        @endif
                    </div>

                    <p>Add each of your bank accounts so you can easily keep track of all your spending and current balances.</p>
                </div>
            </div>

            {{-- Connected accounts section --}}
            <div id="connectedAccountsSection" style="{{ $connectedAccounts->count() ? '' : 'display:none' }}">
                <div class="row mt-2">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                        <div class="sep-title">Connected accounts</div>

                        <div class="connected-accounts" id="connectedAccountsList">
                            @foreach($connectedAccounts as $acc)
                                <div class="bankItem">
                                    <div class="row align-items-center">
                                        <div class="col-8">
                                            <div>
                                                <strong>{{ $acc->account_name }}</strong>
                                                @if(!empty($acc->is_salary_account))
                                                    <span class="salaryBadge">Salary account</span>
                                                @endif
                                            </div>
                                            <div class="meta">
                                                {{ str_replace('_', ' ', $acc->account_type) }}
                                                @if($acc->mask) • ••••{{ $acc->mask }} @endif
                                                @if($acc->institution_name) • {{ $acc->institution_name }} via Plaid @endif
                                            </div>
                                        </div>
                                        <div class="col-4" style="text-align:right">
                                            <div class="balance">£{{ number_format((float)$acc->starting_balance, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <p class="text-muted" style="font-size:.95rem">
                            You can continue, or add more accounts via the buttons above.
                        </p>

                        <div class="addAnotherInlineWrap" id="addAnotherInlineWrap" style="{{ $connectedAccounts->count() ? '' : 'display:none' }}">
                            <a href="#" id="addAnotherTrigger" class="addAnotherInlineLink">
                                <i class="fas fa-plus-circle"></i> Add another bank account
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Manual form (popup area) --}}
            <div class="row mt-md-4 mt-0">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">

                    <form id="manualForm"
                          action="{{ route('account-setup.step-five-store') }}"
                          method="post"
                          style="display:none;">
                        @csrf

                        {{-- ✅ This controls whether stepFiveStore redirects to step 6 --}}
                        <input type="hidden" name="go_next" id="goNextInput" value="0">

                        {{-- Hidden submissions (locked inputs live here) --}}
                        <div id="manualHiddenSubmissions" style="display:none;"></div>

                        {{-- Editable items (popup) --}}
                        <div class="bankDetailsInputMainWrap" id="bankItemsWrap"></div>

                        <div class="row align-items-center my-4">
                            <div class="col-6 d-flex justify-content-start">
                                <a class="setupStepsBackButton" href="{{ route('account-setup.step-four') }}">Back</a>
                            </div>
                            <div class="col-6 d-flex justify-content-end">
                                <button id="manualContinueBtn" type="button" class="twoToneBlueGreenBtn">Continue</button>
                            </div>
                        </div>
                    </form>

                    {{-- ✅ ALWAYS render Back + Continue --}}
                    <div id="quickContinueRow" class="mt-3">
                        <div class="row align-items-center my-4">
                            <div class="col-6 d-flex justify-content-start">
                                <a class="setupStepsBackButton" href="{{ route('account-setup.step-four') }}">Back</a>
                            </div>
                            <div class="col-6 d-flex justify-content-end">
                                <button id="quickContinueBtn" type="button" class="twoToneBlueGreenBtn">Continue</button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const manualForm = document.getElementById('manualForm');
            const goNextInput = document.getElementById('goNextInput');
            const manualContinueBtn = document.getElementById('manualContinueBtn');

            const toggleBtn  = document.getElementById('toggleManual');
            const addAnotherTrigger = document.getElementById('addAnotherTrigger');
            const addAnotherInlineWrap = document.getElementById('addAnotherInlineWrap');
            const quickRow  = document.getElementById('quickContinueRow');
            const quickBtn  = document.getElementById('quickContinueBtn');

            const wrap = document.getElementById('bankItemsWrap');
            const hiddenSubmissions = document.getElementById('manualHiddenSubmissions');

            const connectedSection = document.getElementById('connectedAccountsSection');
            const connectedList = document.getElementById('connectedAccountsList');

            function showSavedPopup(){
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved.',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Saved.');
                }
            }

            function showQuickContinue(){
                if (quickRow) quickRow.style.display = '';
            }
            function hideQuickContinue(){
                if (quickRow) quickRow.style.display = 'none';
            }

            showQuickContinue();

            if (!wrap || !hiddenSubmissions || !connectedSection || !connectedList || !manualForm) return;

            function submitAndGoNext(){
                if (goNextInput) goNextInput.value = "1";
                manualForm.submit();
            }

            if (quickBtn) {
                quickBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    submitAndGoNext();
                });
            }

            if (manualContinueBtn) {
                manualContinueBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    submitAndGoNext();
                });
            }

            function validate(bankItem){
                let ok = true;
                bankItem.querySelectorAll('input[required], select[required]').forEach(el => {
                    const v = String(el.value || '').trim();
                    if (!v) { ok = false; el.style.border = '1px solid red'; }
                    else { el.style.border = ''; }
                });
                if (!ok) alert('Please fill all required fields before saving.');
                return ok;
            }

            function getSalaryState(bankItem){
                const cb = bankItem.querySelector('input[name="is_salary_account[]"]');
                if (!cb) return false;

                if (cb.disabled) {
                    const hidden = bankItem.querySelector('input[type="hidden"][name="is_salary_account[]"]');
                    return hidden ? (String(hidden.value) === '1') : cb.checked;
                }

                return cb.checked;
            }

            function setSalaryState(bankItem, checked){
                const cb = bankItem.querySelector('input[name="is_salary_account[]"]');
                if (!cb) return;

                cb.checked = !!checked;

                if (cb.disabled) {
                    const hidden = bankItem.querySelector('input[type="hidden"][name="is_salary_account[]"]');
                    if (hidden) hidden.value = cb.checked ? '1' : '0';
                }
            }

            function refreshSalaryBadges(){
                connectedList.querySelectorAll('.salaryBadge').forEach(b => b.remove());

                const allHiddenItems = Array.from(hiddenSubmissions.querySelectorAll('.bankItem'));
                const salaryItem = allHiddenItems.find(it => getSalaryState(it) === true);

                if (!salaryItem) return;

                const salaryManualId = salaryItem.dataset.manualId;
                if (!salaryManualId) return;

                const card = connectedList.querySelector(`.bankItem[data-manual-id="${salaryManualId}"]`);
                if (!card) return;

                const titleDiv = card.querySelector('.col-8 > div');
                if (!titleDiv) return;

                const badge = document.createElement('span');
                badge.className = 'salaryBadge';
                badge.textContent = 'Salary account';
                titleDiv.appendChild(badge);
            }

            function confirmMoveSalary(){
                const text = 'You already selected a salary account. Do you want to move where your salary gets paid to this account?';

                if (window.Swal && typeof window.Swal.fire === 'function') {
                    return Swal.fire({
                        icon: 'warning',
                        title: 'Move salary account?',
                        text: text,
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        cancelButtonText: 'No',
                    }).then(r => !!r.isConfirmed);
                }

                return Promise.resolve(window.confirm(text));
            }

            async function handleSalaryToggle(changedBankItem){
                if (!getSalaryState(changedBankItem)) {
                    refreshSalaryBadges();
                    return;
                }

                const allItems = [
                    ...Array.from(hiddenSubmissions.querySelectorAll('.bankItem')),
                    ...Array.from(wrap.querySelectorAll('.bankItem'))
                ];

                const others = allItems.filter(it => it !== changedBankItem && getSalaryState(it) === true);

                if (others.length === 0) {
                    refreshSalaryBadges();
                    return;
                }

                const ok = await confirmMoveSalary();
                if (!ok) {
                    setSalaryState(changedBankItem, false);
                    refreshSalaryBadges();
                    return;
                }

                others.forEach(it => setSalaryState(it, false));
                refreshSalaryBadges();
            }

            function syncHiddenForItem(bankItem){
                const sel = bankItem.querySelector('select[name="bank_account_type[]"]');
                if (sel) {
                    let hiddenSel = bankItem.querySelector('input[type="hidden"][name="bank_account_type[]"]');
                    if (sel.disabled) {
                        if (!hiddenSel) {
                            hiddenSel = document.createElement('input');
                            hiddenSel.type = 'hidden';
                            hiddenSel.name = 'bank_account_type[]';
                            bankItem.appendChild(hiddenSel);
                        }
                        hiddenSel.value = sel.value;
                    } else {
                        if (hiddenSel) hiddenSel.remove();
                    }
                }

                const cb = bankItem.querySelector('input[name="is_salary_account[]"]');
                if (cb) {
                    let hiddenCb = bankItem.querySelector('input[type="hidden"][name="is_salary_account[]"]');
                    if (cb.disabled) {
                        if (!hiddenCb) {
                            hiddenCb = document.createElement('input');
                            hiddenCb.type = 'hidden';
                            hiddenCb.name = 'is_salary_account[]';
                            bankItem.appendChild(hiddenCb);
                        }
                        hiddenCb.value = cb.checked ? '1' : '0';
                    } else {
                        if (hiddenCb) hiddenCb.remove();
                    }
                }
            }

            function lockInputs(bankItem){
                bankItem.querySelectorAll('input[name="name_of_bank_account[]"], input[name="bank_account_starting_balance[]"]')
                    .forEach(i => i.setAttribute('readonly', true));

                const sel = bankItem.querySelector('select[name="bank_account_type[]"]');
                if (sel) sel.disabled = true;

                const cb = bankItem.querySelector('input[name="is_salary_account[]"]');
                if (cb) cb.disabled = true;

                syncHiddenForItem(bankItem);
            }

            function formatGBP(n){
                const num = Number(n);
                if (Number.isNaN(num)) return '£0.00';
                return '£' + num.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function openManual(){
                manualForm.style.display = '';
                hideQuickContinue();
                manualForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function closeManual(){
                manualForm.style.display = 'none';
                wrap.innerHTML = '';
                showQuickContinue();
            }

            function createEditableItem(existingValues = null){
                const bankItem = document.createElement('div');
                bankItem.className = 'bankItem';

                bankItem.innerHTML = `
                    <div class="row">
                        <div class="col-12">
                            <label>Name of bank</label>
                            <input type="text" name="name_of_bank_account[]" required>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <label>Account type</label>
                            <select name="bank_account_type[]" required>
                                <option value="" disabled selected>Select an option...</option>
                                <option value="current_account">Current Account</option>
                                <option value="savings_account">Savings Account</option>
                                <option value="isa_account">ISA Account</option>
                                <option value="investment_account">Investment Account</option>
                                <option value="credit_card">Credit Card</option>
                            </select>
                            <div class="creditNote">If you need to repay your card, insert a negative value.</div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <label>Starting balance</label>
                            <input type="number" name="bank_account_starting_balance[]" step="any" required>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <label class="salaryRowLabel">
                                <input type="checkbox" name="is_salary_account[]" value="1">
                                <span>This is the bank account where my salary will be paid into</span>
                            </label>
                            <div class="salaryHint">Only one account can be marked as salary at a time.</div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="saveBankBtn">Save</button>
                            <button type="button" class="cancelBankBtn">Cancel</button>
                        </div>
                    </div>
                `;

                const nameInput = bankItem.querySelector('input[name="name_of_bank_account[]"]');
                const typeSelect = bankItem.querySelector('select[name="bank_account_type[]"]');
                const balInput = bankItem.querySelector('input[name="bank_account_starting_balance[]"]');
                const salaryCb = bankItem.querySelector('input[name="is_salary_account[]"]');

                if (existingValues) {
                    if (nameInput) nameInput.value = existingValues.name || '';
                    if (typeSelect && existingValues.type) typeSelect.value = existingValues.type;
                    if (balInput) balInput.value = existingValues.balance || '';
                    if (salaryCb) salaryCb.checked = !!existingValues.isSalary;
                }

                if (salaryCb) {
                    salaryCb.addEventListener('change', async () => {
                        await handleSalaryToggle(bankItem);
                    });
                }

                const note = bankItem.querySelector('.creditNote');
                if (typeSelect && note) {
                    const updateNote = () => note.style.display = (typeSelect.value === 'credit_card') ? 'block' : 'none';
                    typeSelect.addEventListener('change', updateNote);
                    updateNote();
                }

                bankItem.querySelector('.saveBankBtn').addEventListener('click', async () => {
                    if (!validate(bankItem)) return;

                    const allHiddenItems = Array.from(hiddenSubmissions.querySelectorAll('.bankItem'));
                    if (getSalaryState(bankItem)) {
                        allHiddenItems.forEach(it => setSalaryState(it, false));
                    }

                    const name = (nameInput.value || '').trim();
                    const typeLabel = typeSelect.options[typeSelect.selectedIndex]?.text || typeSelect.value;
                    const bal = balInput.value;

                    lockInputs(bankItem);

                    const id = 'm_' + Date.now() + '_' + Math.floor(Math.random()*100000);
                    bankItem.dataset.manualId = id;

                    bankItem.style.display = 'none';
                    hiddenSubmissions.appendChild(bankItem);

                    connectedSection.style.display = '';
                    if (addAnotherInlineWrap) addAnotherInlineWrap.style.display = '';
                    if (toggleBtn) toggleBtn.style.display = 'none';

                    const card = document.createElement('div');
                    card.className = 'bankItem';
                    card.dataset.manualId = id;

                    card.innerHTML = `
                        <div class="row align-items-center">
                            <div class="col-8">
                                <div><strong></strong></div>
                                <div class="meta"></div>
                            </div>
                            <div class="col-4" style="text-align:right">
                                <div class="balance"></div>
                            </div>
                        </div>
                        <div class="manualCardActions">
                            <button type="button" class="editBtnMini">Edit</button>
                            <button type="button" class="removeBtnMini">Remove</button>
                        </div>
                    `;

                    card.querySelector('strong').textContent = name || '—';
                    card.querySelector('.meta').textContent = typeLabel || '—';
                    card.querySelector('.balance').textContent = formatGBP(bal);

                    connectedList.appendChild(card);

                    refreshSalaryBadges();

                    closeManual();
                    showSavedPopup();
                });

                bankItem.querySelector('.cancelBankBtn').addEventListener('click', () => {
                    closeManual();
                });

                return bankItem;
            }

            function openManualAndAddOne(){
                openManual();
                wrap.innerHTML = '';
                wrap.appendChild(createEditableItem());
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    openManualAndAddOne();
                });
            }

            if (addAnotherTrigger) {
                addAnotherTrigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    openManualAndAddOne();
                });
            }

            @if (session('success'))
            showSavedPopup();
            @endif
        });
    </script>
@endsection
