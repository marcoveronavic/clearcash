@extends('layouts.customer')
@section('styles_in_head')
    <link rel="stylesheet" href="{{ asset('build/assets/account-setup.css') }}">

    <script>
    (function(){
        var btn = document.createElement('button');
        btn.innerHTML = document.body.classList.contains('light-mode') ? '?? Dark' : '?? Light';
        btn.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:999999;padding:10px 18px;border-radius:20px;border:1px solid #d1d5db;background:#fff;color:#111;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
        document.body.appendChild(btn);
        btn.addEventListener('click', function(){
            if (document.body.classList.contains('light-mode')) {
                document.body.classList.remove('light-mode');
                localStorage.setItem('cc-theme','dark');
            } else {
                document.body.classList.add('light-mode');
                localStorage.setItem('cc-theme','light');
            }
            window.location.reload();
        });
    })();
    </script>
@endsection
@section('content')
    <style>
        header, aside.sidebar { display: none; }
        main.dashboardMain { padding-top: 2rem; width: 100%; }
        main.dashboardMain.full { padding-top: 2rem; }

        .bankItem.completed { border: 1px solid #28a74569 !important; background: #28a7450a !important; }
        .saveBankBtn, .editBankBtn { background: #28a745; color: #fff; border: none; padding: 6px 12px; margin-right: 10px; cursor: pointer; border-radius: 4px; }
        .saveBankBtn:disabled { opacity: 0.7; cursor: not-allowed; }
        .removeBankBtn, .addAnotherBankBtn { background: #dc3545; color: #fff; border: none; padding: 6px 12px; cursor: pointer; border-radius: 4px; }
        .addAnotherBankBtn { background: #007bff; }

        .connectedAccountsTitle{ color:#2ef0b3; font-weight:700; margin: 20px 0 12px 0; }
        .connectedAccountCard{
            border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:14px 16px;
            display:flex; align-items:center; justify-content:space-between;
            background: rgba(0,0,0,0.08); margin-bottom:12px;
        }
        .connectedAccountCard .name{ font-size:18px; font-weight:700; color:#fff; line-height:1.1; }
        .connectedAccountCard .type{ opacity:.7; color:#fff; margin-top:4px; text-transform: lowercase; }
        .connectedAccountCard .balance{ font-size:18px; font-weight:700; color:#fff; }
        .connectedAccountsHint{ opacity:.6; color:#fff; text-align:center; margin-top:8px; }

        .manualCtaWrap{ text-align:center; margin: 26px 0 6px 0; }
        .manualCtaBtn{
            display:inline-flex; align-items:center; justify-content:center;
            padding:14px 22px; border-radius:12px; font-weight:800; letter-spacing:.2px;
            min-width:320px; height:56px; text-decoration:none;
            background:linear-gradient(90deg,#58f0a8,#43caff); color:#052026;
            border:0; box-shadow:0 0 0 1px rgba(0,0,0,.12) inset; cursor:pointer;
        }
        .manualCtaBtn:hover{ filter:brightness(1.03); transform:translateY(-1px); }
        @media (max-width:576px){ .manualCtaBtn{ width:100%; min-width:0; height:54px; } }
    </style>

    @php
        $hasInvestmentsOrPensions = (($investmentAccounts ?? collect())->isNotEmpty());
    @endphp

    <section class="setupStepsWrapper">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item">Crea il tuo budget</div><div class="sep"></div>
                            <div class="item">Aggiungi conti bancari</div><div class="sep"></div>
                            <div class="item active">Investimenti e pensioni</div><div class="sep"></div>
                            <div class="item">Fatto</div>
                        </div>
                        <div class="boxes">
                            <div class="box active"></div><div class="box active"></div><div class="box active"></div>
                            <div class="box active"></div><div class="box active"></div><div class="box"></div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <section class="errorsBanner"><div class="container"><ul>
                            @foreach ($errors->all() as $error) <li style="color:white">{{ $error }}</li> @endforeach
                        </ul></div></section>
            @endif

            @if (session('success'))
                <section class="errorsBanner"><div class="container"><ul><li style="color:white">{{ session('success') }}</li></ul></div></section>
            @endif

            @if (session('error'))
                <section class="errorsBanner"><div class="container"><ul><li style="color:white">{{ session('error') }}</li></ul></div></section>
            @endif

            <div class="row mt-4">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1>Aggiungi investimenti e pensioni</h1>
                    <p>Inserisci i saldi attuali dei tuoi investimenti e fondi pensione.</p>
                </div>
            </div>

            @if(($investmentAccounts ?? collect())->isNotEmpty())
                <div class="row">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                        <div class="connectedAccountsTitle">Conti collegati</div>
                        @foreach($investmentAccounts as $acc)
                            <div class="connectedAccountCard">
                                <div class="left">
                                    <div class="name">{{ $acc->account_name }}</div>
                                    <div class="type">{{ $acc->account_type === 'pension' ? 'pensione' : 'investimento' }}</div>
                                </div>
                                <div class="right">
                                    <div class="balance">€{{ number_format((float)$acc->starting_balance, 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                        <div class="connectedAccountsHint">Puoi continuare o aggiungere altri conti con il modulo qui sotto.</div>
                    </div>
                </div>
            @endif

            @if(!$hasInvestmentsOrPensions)
                <div class="row">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                        <div class="manualCtaWrap">
                            <button type="button" id="toggleManualInvest" class="manualCtaBtn">
                                <i class="fas fa-plus-circle" style="margin-right:10px;"></i>
                                Aggiungi investimento o pensione manualmente
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <form id="investmentsForm" action="{{ route('account-setup.step-six-investments-store') }}" method="post">
                        @csrf
                        <input type="hidden" name="intent" id="intentField" value="save">

                        <div id="bankDetailsWrapper" class="bankDetailsInputMainWrap" style="display:none;">
                            <div class="bankItem">
                                <div class="row"><div class="col-12"><label>Nome del conto</label><input type="text" name="name_of_pension_investment_account[]" required></div></div>
                                <div class="row"><div class="col-12"><label>Tipo di conto</label>
                                        <select name="pension_investment_type[]" required>
                                            <option value="" disabled selected>Seleziona un'opzione...</option>
                                            <option value="pension">Pensione</option>
                                            <option value="investment">Investimento</option>
                                        </select>
                                    </div></div>
                                <div class="row"><div class="col-12"><label>Saldo iniziale</label><input type="number" name="pension_investment_account_starting_balance[]" step="any" required></div></div>
                                <div class="row"><div class="col-12 d-flex justify-content-end">
                                        <button type="button" class="saveBankBtn">Salva</button>
                                        <button type="button" class="editBankBtn" style="display:none;">Modifica</button>
                                        <button type="button" class="removeBankBtn">Rimuovi</button>
                                    </div></div>
                            </div>
                        </div>

                        <div class="row my-4" id="addAnotherRow" style="{{ $hasInvestmentsOrPensions ? '' : 'display:none' }}">
                            <div class="col-12 text-center">
                                <button type="button" class="addAnotherBankBtn">
                                    <i class="fas fa-plus-circle"></i> Aggiungi un altro conto
                                </button>
                            </div>
                        </div>

                        <div class="row align-items-center my-4">
                            <div class="col-6 d-flex justify-content-start">
                                <a class="setupStepsBackButton" href="{{ route('account-setup.step-five') }}">Indietro</a>
                            </div>
                            <div class="col-6 d-flex justify-content-end gap-4">
                                @if(!$hasInvestmentsOrPensions)
                                    <button type="submit" class="twoToneBlueGreenBtn" formnovalidate
                                            onclick="document.getElementById('intentField').value='skip'">
                                        Salta e continua
                                    </button>
                                @endif
                                <button type="submit" class="twoToneBlueGreenBtn" formnovalidate
                                        onclick="document.getElementById('intentField').value='continue'">
                                    Continua
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const addAnotherBankBtn = document.querySelector(".addAnotherBankBtn");
            const bankDetailsWrapper = document.getElementById("bankDetailsWrapper");
            const intentField = document.getElementById("intentField");
            const form = document.getElementById("investmentsForm");
            const toggleManualInvest = document.getElementById("toggleManualInvest");

            function checkIfComplete(bankItem) {
                let allFilled = true;
                bankItem.querySelectorAll("input, select").forEach(field => { if (!field.value.trim()) allFilled = false; });
                if (allFilled) bankItem.classList.add("completed"); else bankItem.classList.remove("completed");
            }

            function attachEvents(bankItem) {
                bankItem.querySelectorAll("input, select").forEach(field => { field.addEventListener("input", () => checkIfComplete(bankItem)); });
                const saveBtn = bankItem.querySelector(".saveBankBtn");
                const editBtn = bankItem.querySelector(".editBankBtn");

                saveBtn.addEventListener("click", function() {
                    let valid = true;
                    bankItem.querySelectorAll("input[required], select[required]").forEach(field => {
                        if (!field.value.trim()) { valid = false; field.style.border = "1px solid red"; } else { field.style.border = ""; }
                    });
                    if (!valid) { alert("Compila tutti i campi obbligatori prima di salvare."); return; }

                    bankItem.querySelectorAll("input, select").forEach(field => {
                        if (field.tagName === "SELECT") {
                            let existingHidden = bankItem.querySelector(`input[type="hidden"][name="${field.name}"]`);
                            if (!existingHidden) { const hidden = document.createElement("input"); hidden.type = "hidden"; hidden.name = field.name; hidden.value = field.value; bankItem.appendChild(hidden); } else { existingHidden.value = field.value; }
                            field.setAttribute("disabled", true);
                        } else { field.setAttribute("readonly", true); }
                    });

                    bankItem.classList.add("completed");
                    saveBtn.textContent = "Salvato";
                    saveBtn.disabled = true;
                    editBtn.style.display = "inline-block";
                    intentField.value = "save";
                    form.submit();
                });

                editBtn.addEventListener("click", function() {
                    bankItem.querySelectorAll("input, select").forEach(field => { field.removeAttribute("readonly"); field.removeAttribute("disabled"); });
                    bankItem.querySelectorAll('input[type="hidden"]').forEach(h => h.remove());
                    saveBtn.textContent = "Salva";
                    saveBtn.disabled = false;
                    editBtn.style.display = "none";
                });
            }

            attachEvents(bankDetailsWrapper.querySelector(".bankItem"));

            if (toggleManualInvest) {
                toggleManualInvest.addEventListener("click", function() {
                    bankDetailsWrapper.style.display = "block";
                    bankDetailsWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }

            if (addAnotherBankBtn) {
                addAnotherBankBtn.addEventListener("click", function() {
                    if (bankDetailsWrapper.style.display === "none") { bankDetailsWrapper.style.display = "block"; return; }
                    const firstBankItem = bankDetailsWrapper.querySelector(".bankItem");
                    const newBankItem = firstBankItem.cloneNode(true);
                    newBankItem.classList.remove("completed");
                    newBankItem.querySelectorAll("input, select").forEach(field => { field.removeAttribute("readonly"); field.removeAttribute("disabled"); field.style.border = ""; if (field.tagName === "SELECT") field.selectedIndex = 0; else field.value = ""; });
                    newBankItem.querySelector(".saveBankBtn").textContent = "Salva";
                    newBankItem.querySelector(".saveBankBtn").disabled = false;
                    newBankItem.querySelector(".editBankBtn").style.display = "none";
                    newBankItem.querySelectorAll('input[type="hidden"]').forEach(h => h.remove());
                    bankDetailsWrapper.appendChild(newBankItem);
                    attachEvents(newBankItem);
                });
            }

            bankDetailsWrapper.addEventListener("click", function(event) {
                if (event.target.classList.contains("removeBankBtn")) {
                    const bankItem = event.target.closest(".bankItem");
                    if (bankDetailsWrapper.querySelectorAll(".bankItem").length > 1) bankItem.remove();
                }
            });
        });
    </script>
        <script>
        (function(){
            var btn = document.createElement('button');
            var ico = document.createElement('i'); ico.className = document.body.classList.contains('light-mode') ? 'fa-solid fa-moon' : 'fa-solid fa-sun'; ico.style.marginRight = '6px'; ico.style.color = document.body.classList.contains('light-mode') ? '#fbbf24' : '#f59e0b'; btn.appendChild(ico); btn.appendChild(document.createTextNode(document.body.classList.contains('light-mode') ? ' Dark' : ' Light'));
            btn.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:999999;padding:10px 18px;border-radius:20px;border:1px solid #d1d5db;background:#fff;color:#111;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
            document.body.appendChild(btn);
            btn.addEventListener('click', function(){
                if (document.body.classList.contains('light-mode')) {
                    document.body.classList.remove('light-mode');
                    localStorage.setItem('cc-theme','dark');
                } else {
                    document.body.classList.add('light-mode');
                    localStorage.setItem('cc-theme','light');
                }
                window.location.reload();
            });
        })();
    </script>
@endsection


