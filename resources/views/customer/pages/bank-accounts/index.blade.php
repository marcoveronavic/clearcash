{{-- resources/views/customer/pages/bank-accounts/index.blade.php --}}
@extends('layouts.customer')

@section('styles_in_head')
    <style>
        header, .customerHeader, .contentTopBar, .page-header, .navbar {
            background: #0f2222 !important;
            border: 0 !important;
            box-shadow: none !important;
        }
        main.dashboardMain h1 { margin-top: 0 !important; }

        /* ✅ footer buttons layout (keep existing styles) */
        .ccModalFooterActions{
            width:100%;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
        }
        .ccModalFooterActions form{ margin:0; }
        .ccCancelBtn{
            padding:10px 18px;
            border-radius:12px;
            font-weight:800;
            height:44px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }
    </style>
@endsection

@section('content')
    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1>Bank Accounts</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="addBanner">
        <div class="container">
            <div class="row">
                <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                    <button type="button"
                            class="twoToneBlueGreenBtn"
                            data-bs-toggle="modal"
                            data-bs-target="#addBankAccountModal">
                        Add Bank Account
                    </button>

                    @include('customer.pages.bank-accounts._plaid_link')
                </div>
            </div>
        </div>
    </section>

    <section class="bankAccountsMainList">
        <div class="container">
            <div class="row">
                <div class="col-12">

                    @if ($bankAccounts->isNotEmpty())
                        @foreach ($bankAccounts as $account)
                            <div class="bankItem">
                                <button class="modalBtn" type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#acc_{{ $account->id }}">
                                    <div class="row align-items-center">
                                        <div class="col-8">
                                            <h4 class="mb-1">{{ $account->account_name }}</h4>
                                            <h6 class="m-0">{{ str_replace('_', ' ', $account->account_type) }}</h6>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="balance">
                                                £{{ number_format($account->current_balance ?? $account->starting_balance, 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </button>

                                {{-- Modal Edit --}}
                                <div class="modal fade" id="acc_{{ $account->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>

                                            <div class="modal-body">
                                                <h1 class="mb-3">Edit {{ $account->account_name }} Bank Account</h1>

                                                <form action="{{ route('bank-accounts.update', $account->id) }}" method="post" class="mb-4">
                                                    @csrf
                                                    @method('put')

                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <label for="name_of_bank_account_{{ $account->id }}">Name of bank</label>
                                                            <input type="text"
                                                                   name="name_of_bank_account"
                                                                   id="name_of_bank_account_{{ $account->id }}"
                                                                   value="{{ old('name_of_bank_account', $account->account_name) }}">
                                                        </div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <label for="bank_account_type_{{ $account->id }}">Account type</label>
                                                            <select name="bank_account_type" id="bank_account_type_{{ $account->id }}">
                                                                <option value="current_account"    @selected($account->account_type == 'current_account')>Current Account</option>
                                                                <option value="savings_account"    @selected($account->account_type == 'savings_account')>Savings Account</option>
                                                                <option value="isa_account"        @selected($account->account_type == 'isa_account')>ISA Account</option>
                                                                <option value="investment_account" @selected($account->account_type == 'investment_account')>Investment Account</option>
                                                                <option value="pension"            @selected($account->account_type == 'pension')>Pension</option>
                                                                <option value="investment"         @selected($account->account_type == 'investment')>Investments</option>
                                                                <option value="credit_card"        @selected($account->account_type == 'credit_card')>Credit Card</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-4">
                                                        <div class="col-12">
                                                            {{-- ✅ label change --}}
                                                            <label for="bank_account_starting_balance_{{ $account->id }}">Balance</label>
                                                            <input type="number"
                                                                   step="any"
                                                                   name="bank_account_starting_balance"
                                                                   id="bank_account_starting_balance_{{ $account->id }}"
                                                                   value="{{ old('bank_account_starting_balance', $account->starting_balance) }}">
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                                            <button type="submit" class="twoToneBlueGreenBtn text-center py-2">
                                                                Update
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>

                                                {{-- Recent Transactions passate dal controller --}}
                                                @php $recent = $account->recentTransactions ?? collect(); @endphp

                                                <div class="transactionList">
                                                    <h4 class="mb-3 fw-semibold text-white">Recent Transactions</h4>
                                                    <ul class="list-group">

                                                        {{-- ✅ Starting balance sempre come prima riga --}}
                                                        <li class="list-group-item d-flex justify-content-between align-items-center my-1"
                                                            style="background-color:#d1f9ff0d;border:none;">
                                                            <div class="d-flex flex-column">
                                                                <span class="fs-5 fw-semibold text-white">Starting balance</span>
                                                                <small class="text-white">
                                                                    {{ $account->created_at ? \Carbon\Carbon::parse($account->created_at)->format('d M, Y') : '' }}
                                                                </small>
                                                            </div>
                                                            <span class="badge bg-secondary fs-6">
                                                                £{{ number_format((float) $account->starting_balance, 2) }}
                                                            </span>
                                                        </li>

                                                        @if ($recent->isNotEmpty())
                                                            @foreach ($recent as $transaction)
                                                                <li class="list-group-item d-flex justify-content-between align-items-center my-1"
                                                                    style="background-color:#d1f9ff0d;border:none;">
                                                                    <div class="d-flex flex-column">
                                                                        <span class="fs-5 fw-semibold text-white">
                                                                            {{ $transaction->name ?? $transaction->description ?? 'No Name' }}
                                                                        </span>
                                                                        <small class="text-white">
                                                                            {{ \Carbon\Carbon::parse($transaction->date)->format('d M, Y') }}
                                                                        </small>
                                                                    </div>
                                                                    @php
                                                                        $isIncome = $transaction->transaction_type === 'income';
                                                                        $amount   = number_format((float) $transaction->amount, 2);
                                                                    @endphp
                                                                    <span class="badge {{ $isIncome ? 'bg-success' : 'bg-danger' }} fs-6">
                                                                        {{ $isIncome ? '£+'.$amount : '£'.$amount }}
                                                                    </span>
                                                                </li>
                                                            @endforeach
                                                        @else
                                                            <li class="list-group-item d-flex justify-content-between align-items-center"
                                                                style="background-color:#d1f9ff0d;border:none;">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-white">No Transaction recorded yet for this Bank</span>
                                                                </div>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>

                                            {{-- ✅ footer: Delete left, Cancel right (palette) --}}
                                            <div class="modal-footer">
                                                <div class="ccModalFooterActions">
                                                    <form id="deleteBankAccountForm-{{ $account->id }}"
                                                          action="{{ route('bank-accounts.destroy', $account->id) }}"
                                                          method="post">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="button"
                                                                class="dangerBtn confirmDeleteBankAccountBtn"
                                                                data-form-id="deleteBankAccountForm-{{ $account->id }}">
                                                            Delete
                                                        </button>
                                                    </form>

                                                    <button type="button"
                                                            class="twoToneBlueGreenBtn ccCancelBtn"
                                                            data-bs-dismiss="modal">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="noAccountNotice text-center">
                            <i class="fa-solid fa-building-columns"></i>
                            <h2>No bank accounts added yet</h2>
                            <p>Add each of your bank accounts so you can easily keep track of all your spending and current balances.</p>

                            <div class="d-flex flex-wrap justify-content-center gap-3">
                                <button type="button"
                                        class="twoToneBlueGreenBtn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addBankAccountModal">
                                    Add Bank Account
                                </button>

                                @include('customer.pages.bank-accounts._plaid_link')
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </section>

    {{-- Modal: Add Bank Account --}}
    <div class="modal fade" id="addBankAccountModal" tabindex="-1" aria-labelledby="addBankAccountModal"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <h1>Add Bank Account</h1>

                    <form action="{{ route('bank-accounts.store') }}" method="post">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="name_of_bank_account">Name of bank</label>
                                <input type="text" name="name_of_bank_account" id="name_of_bank_account" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="bank_account_type">Account type</label>
                                <select name="bank_account_type" id="bank_account_type" required
                                        onchange="toggleNote(this.value)">
                                    <option value="" disabled selected>Select an option...</option>
                                    <option value="current_account">Current Account</option>
                                    <option value="savings_account">Savings Account</option>
                                    <option value="isa_account">ISA Account</option>
                                    <option value="investment_account">Investment Account</option>
                                    <option value="pension">Pension</option>
                                    <option value="investment">Investments</option>
                                    <option value="credit_card">Credit Card</option>
                                </select>
                                <small class="text-white creditNote" id="creditNote" style="display:none;">
                                    If you need to repay your credit card, insert a negative value.
                                </small>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <label for="bank_account_starting_balance">Starting balance</label>
                                <input type="number" step="any" name="bank_account_starting_balance"
                                       id="bank_account_starting_balance" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="twoToneBlueGreenBtn">Add Bank Account</button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        function toggleNote(value) {
            const note = document.getElementById("creditNote");
            if (note) note.style.display = (value === "credit_card") ? "block" : "none";
        }

        /**
         * FIX: collega il pulsante flottante “+” (in basso a destra) all'apertura del modal Add Bank Account.
         * Il FAB è nel layout e spesso non ha data-bs-target, quindi non fa nulla.
         */
        (function wireFloatingPlusToAddBankAccountModal() {
            function hasBootstrapModal() {
                return window.bootstrap && window.bootstrap.Modal;
            }

            function openAddBankModal() {
                const modalEl = document.getElementById('addBankAccountModal');
                if (!modalEl) return false;
                if (!hasBootstrapModal()) return false;
                window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                return true;
            }

            function findFloatingFab() {
                // Cerchiamo un elemento "cliccabile" fisso vicino all'angolo bottom-right
                const candidates = Array.from(document.querySelectorAll('button, a, [role="button"], div'));
                let best = null;
                let bestScore = Infinity;

                for (const el of candidates) {
                    const cs = window.getComputedStyle(el);
                    if (cs.position !== 'fixed') continue;

                    const rect = el.getBoundingClientRect();
                    const distRight  = window.innerWidth  - rect.right;
                    const distBottom = window.innerHeight - rect.bottom;

                    // deve stare vicino al corner
                    if (distRight < -5 || distBottom < -5) continue;
                    if (distRight > 140 || distBottom > 140) continue;

                    // deve assomigliare a un FAB (dimensione ragionevole)
                    if (rect.width < 30 || rect.height < 30) continue;
                    if (rect.width > 120 || rect.height > 120) continue;

                    // icona plus/x oppure aria-label "add"
                    const hasIcon =
                        el.querySelector('.fa-plus, .fa-circle-plus, .fa-xmark, .fa-times, .bi-plus') ||
                        /add|plus/i.test((el.getAttribute('aria-label') || '') + ' ' + (el.getAttribute('title') || ''));

                    if (!hasIcon) continue;

                    const score = distRight + distBottom;
                    if (score < bestScore) {
                        bestScore = score;
                        best = el;
                    }
                }

                return best;
            }

            document.addEventListener('DOMContentLoaded', function () {
                const fab = findFloatingFab();
                if (!fab) return;

                // Evita di attaccare più volte
                if (fab.dataset.ccWired === '1') return;
                fab.dataset.ccWired = '1';

                fab.addEventListener('click', function (e) {
                    // Se su questa pagina c'è il modal, lo apriamo.
                    const opened = openAddBankModal();
                    if (opened) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }, true);
            });
        })();

        // ✅ Confirm prima di eliminare bank account (testo richiesto)
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.confirmDeleteBankAccountBtn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();

                    const formId = btn.getAttribute('data-form-id');
                    const form = formId ? document.getElementById(formId) : null;
                    if (!form) return;

                    const message = 'Are you sure you want to cancel your bank account?';

                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            icon: 'warning',
                            title: 'Are you sure?',
                            text: message,
                            showCancelButton: true,
                            confirmButtonText: 'Yes',
                            cancelButtonText: 'No',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    } else {
                        if (window.confirm(message)) {
                            form.submit();
                        }
                    }
                });
            });
        });
    </script>
@endsection
