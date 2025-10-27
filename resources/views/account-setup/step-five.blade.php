@extends('layouts.customer')

@section('styles_in_head')
    <link rel="stylesheet" href="{{ asset('build/assets/account-setup.css') }}">
@endsection

@section('content')
    @php
        // Carico eventuali conti già collegati (via Plaid o inseriti prima)
        $connectedAccounts = \App\Models\BankAccount::where('user_id', auth()->id())
            ->orderBy('account_name')
            ->get();
    @endphp

    <style>
        header, aside.sidebar { display: none; }
        main.dashboardMain { padding-top: 2rem; width: 100%; }
        main.dashboardMain.full { padding-top: 2rem; }

        .bankItem.completed {
            border: 1px solid #28a74569 !important;
            background: #28a7450a !important;
        }

        .saveBankBtn, .editBankBtn {
            background: #28a745; color: #fff; border: none; padding: 6px 12px;
            margin-right: 10px; cursor: pointer; border-radius: 4px;
        }
        .saveBankBtn:disabled { opacity: .7; cursor: not-allowed; }

        .creditNote { color: #d9534f; font-size: .9em; margin-top: 4px; display: none; }

        .connected-accounts .bankItem {
            padding: 14px 16px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #2a3b3b;
            background: rgba(40,167,69,0.06);
        }
        .connected-accounts .meta { color: #8aa; font-size: .9rem; }
        .connected-accounts .balance { font-weight: 600; }
        .sep-title { margin: 18px 0 8px; color: #9fb; font-weight: 600; letter-spacing: .2px; }
    </style>

    <section class="setupStepsWrapper">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item ">Create your budget</div>
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

            {{-- Header + bottone Plaid --}}
            <div class="row">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1>Add bank accounts</h1>

                    {{-- Bottone Plaid --}}
                    @include('customer.pages.bank-accounts._plaid_link')

                    <p>Add each of your bank accounts so you can easily keep track of all your spending and current balances.</p>
                </div>
            </div>

            {{-- Connected accounts (se presenti) --}}
            @if($connectedAccounts->count())
                <div class="row mt-2">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                        <div class="sep-title">Connected accounts</div>

                        <div class="connected-accounts">
                            @foreach($connectedAccounts as $acc)
                                <div class="bankItem completed">
                                    <div class="row align-items-center">
                                        <div class="col-8">
                                            <div><strong>{{ $acc->account_name }}</strong></div>
                                            <div class="meta">
                                                {{ str_replace('_', ' ', $acc->account_type) }}
                                                @if($acc->mask) • ••••{{ $acc->mask }} @endif
                                                @if($acc->institution_name) • {{ $acc->institution_name }} via Plaid @endif
                                            </div>
                                        </div>
                                        <div class="col-4" style="text-align: right">
                                            <div class="balance">
                                                £{{ number_format((float)$acc->starting_balance, 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <p class="text-muted" style="font-size:.95rem">
                            You can continue, or add more accounts via the button above.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Form manuale: mostrato solo se NON ci sono conti collegati --}}
            @if($connectedAccounts->isEmpty())
                <div class="row mt-md-4 mt-0">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                        <form action="{{ route('account-setup.step-five-store') }}" method="post">
                            @csrf
                            <div class="bankDetailsInputMainWrap">
                                <div class="bankItem">
                                    <div class="row">
                                        <div class="col-12">
                                            <label>Name of bank</label>
                                            <input type="text" name="name_of_bank_account[]" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <label>Account type</label>
                                            <select name="bank_account_type[]" required onchange="toggleNote(this.value)">
                                                <option value="" disabled selected>Select an option...</option>
                                                <option value="current_account">Current Account</option>
                                                <option value="savings_account">Savings Account</option>
                                                <option value="isa_account">ISA Account</option>
                                                <option value="investment_account">Investment Account</option>
                                                <option value="credit_card">Credit Card</option>
                                            </select>
                                            <div class="creditNote" id="creditNote">If you need to repay your card, insert a negative value.</div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <label>Starting balance</label>
                                            <input type="number" name="bank_account_starting_balance[]" step="any" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-end">
                                            <button type="button" class="saveBankBtn">Save</button>
                                            <button type="button" class="editBankBtn" style="display:none;">Edit</button>
                                            <button type="button" class="removeBankBtn">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row my-4">
                                <div class="col-12 text-center">
                                    <button type="button" class="addAnotherBankBtn">
                                        <i class="fas fa-plus-circle"></i> Add another bank account
                                    </button>
                                </div>
                            </div>

                            <div class="row align-items-center my-4">
                                <div class="col-6 d-flex justify-content-start">
                                    <a class="setupStepsBackButton" href="{{ route('account-setup.step-four') }}">Back</a>
                                </div>
                                <div class="col-6 d-flex justify-content-end">
                                    <button type="submit" class="twoToneBlueGreenBtn">Continue</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                {{-- Se ho già account connessi, invio il form vuoto per proseguire lo step --}}
                <div class="row align-items-center my-4">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                        <form action="{{ route('account-setup.step-five-store') }}" method="post">
                            @csrf
                            <input type="hidden" name="skip_manual" value="1">
                            <div class="d-flex justify-content-between">
                                <a class="setupStepsBackButton" href="{{ route('account-setup.step-four') }}">Back</a>
                                <button type="submit" class="twoToneBlueGreenBtn">Continue</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- JS del form manuale (rimane attivo solo quando il form è visibile) --}}
    <script>
        function toggleNote(value) {
            const note = document.getElementById("creditNote");
            if (note) note.style.display = (value === "credit_card") ? "block" : "none";
        }

        document.addEventListener("DOMContentLoaded", function() {
            const bankDetailsWrapper = document.querySelector(".bankDetailsInputMainWrap");
            const addAnotherBankBtn = document.querySelector(".addAnotherBankBtn");
            if (!bankDetailsWrapper || !addAnotherBankBtn) return;

            function checkIfComplete(bankItem) {
                let allFilled = true;
                bankItem.querySelectorAll("input[required], select[required]").forEach(field => {
                    if (!field.value.trim()) allFilled = false;
                });
                if (allFilled) bankItem.classList.add("completed");
                else bankItem.classList.remove("completed");
            }

            function attachEvents(bankItem) {
                bankItem.querySelectorAll("input, select").forEach(field => {
                    field.addEventListener("input", () => checkIfComplete(bankItem));
                });

                const accountTypeSelect = bankItem.querySelector("select[name='bank_account_type[]']");
                if (accountTypeSelect) {
                    accountTypeSelect.addEventListener("change", function() { toggleNote(this.value); });
                }

                const saveBtn = bankItem.querySelector(".saveBankBtn");
                const editBtn = bankItem.querySelector(".editBankBtn");

                if (saveBtn) saveBtn.addEventListener("click", function() {
                    let valid = true;
                    bankItem.querySelectorAll("input[required], select[required]").forEach(field => {
                        if (!field.value.trim()) {
                            valid = false; field.style.border = "1px solid red";
                        } else field.style.border = "";
                    });

                    if (!valid) { alert("Please fill all required fields before saving."); return; }

                    bankItem.querySelectorAll("input, select").forEach(field => {
                        if (field.tagName === "SELECT") {
                            field.setAttribute("disabled", true);
                            let hidden = bankItem.querySelector(`input[type="hidden"][name="${field.name}"]`);
                            if (!hidden) {
                                hidden = document.createElement("input");
                                hidden.type = "hidden"; hidden.name = field.name; bankItem.appendChild(hidden);
                            }
                            hidden.value = field.value;
                        } else {
                            field.setAttribute("readonly", true);
                        }
                    });

                    bankItem.classList.add("completed");
                    saveBtn.textContent = "Saved"; saveBtn.disabled = true;
                    if (editBtn) editBtn.style.display = "inline-block";
                });

                if (editBtn) editBtn.addEventListener("click", function() {
                    bankItem.querySelectorAll("input, select").forEach(field => {
                        field.removeAttribute("readonly"); field.removeAttribute("disabled");
                    });
                    bankItem.querySelectorAll('input[type="hidden"]').forEach(h => h.remove());
                    const saveBtn = bankItem.querySelector(".saveBankBtn");
                    if (saveBtn) { saveBtn.textContent = "Save"; saveBtn.disabled = false; }
                    editBtn.style.display = "none";
                });
            }

            // init blocco di partenza
            const first = bankDetailsWrapper.querySelector(".bankItem");
            if (first) attachEvents(first);

            // aggiunta dinamica
            addAnotherBankBtn.addEventListener("click", function() {
                const firstBankItem = bankDetailsWrapper.querySelector(".bankItem");
                const newBankItem = firstBankItem.cloneNode(true);

                newBankItem.classList.remove("completed");
                newBankItem.querySelectorAll("input, select").forEach(field => {
                    field.removeAttribute("readonly"); field.removeAttribute("disabled");
                    field.style.border = ""; if (field.tagName === "SELECT") field.selectedIndex = 0; else field.value = "";
                });

                newBankItem.querySelector(".saveBankBtn").textContent = "Save";
                newBankItem.querySelector(".saveBankBtn").disabled = false;
                newBankItem.querySelector(".editBankBtn").style.display = "none";
                const note = newBankItem.querySelector(".creditNote"); if (note) note.style.display = "none";

                bankDetailsWrapper.appendChild(newBankItem);
                attachEvents(newBankItem);
            });

            bankDetailsWrapper.addEventListener("click", function(event) {
                if (event.target.classList.contains("removeBankBtn")) {
                    const bankItem = event.target.closest(".bankItem");
                    if (bankDetailsWrapper.querySelectorAll(".bankItem").length > 1) bankItem.remove();
                }
            });
        });
    </script>
@endsection
