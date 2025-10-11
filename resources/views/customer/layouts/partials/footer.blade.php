@if (Auth::user()->has_completed_setup == true)
    <div class="floatingQuickAddDropUp dropup">
        <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-plus"></i>
        </button>
        <ul class="dropdown-menu">
            <li>
                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#addBudget">
                    Add Budget Category
                </button>
            </li>
            <li>
                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#addTransaction">
                    Add Transaction
                </button>
            </li>
            <li>
                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#fundTransfer">
                    Fund Transfer
                </button>
            </li>
            {{-- <li>
                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#addRecurringPayment">
                    Add Recurring payment
                </button>
            </li> --}}
            <li>
                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#addBankAccount">
                    Add Bank Account
                </button>
            </li>
        </ul>
    </div>

    {{-- Add Budget --}}
    <div class="modal fade" id="addBudget" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h1>Add Budget Item</h1>
                    <form action="{{ route('budget.global-add-budget') }}" method="post">
                        @csrf
                        <div class="addBudgetItem">
                            <div class="row px-0">
                                <div class="col-md-7 pe-md-0">
                                    <input type="text" class="form-control" name="name" placeholder="Name" value="{{ old('name') }}" required>
                                </div>
                                <div class="col-md-5 ps-md-0 d-md-flex justify-content-md-end">
                                    <div class="input-group">
                                        <label>£</label>
                                        <input type="number" min="0" step="any" name="amount" placeholder="0.00" required style="width: 80% !important;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2" data-loading-text="Saving...">Save</button>
                            </div>
                        </div>
                    </form>
                </div> <!-- /.modal-body -->
            </div>
        </div>
    </div>

    {{-- Add Transaction (global quick add) --}}
    <div class="modal fade" id="addTransaction" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h1>Add Transaction</h1>
                    <form action="{{ route('transactions.global-add-transaction') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <label for="gt_name">Name of Business/Person *</label>
                                <input type="text" name="name" id="gt_name" value="{{ old('name') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="gt_date">Date *</label>
                                <input type="date" name="date" id="gt_date" value="{{ old('date') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="gt_category">Category *</label>
                                <select name="category" id="gt_category">
                                    <option value="" disabled selected>Select a category...</option>
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
                                <label for="gt_bank_account_id">Bank Account *</label>
                                <select name="bank_account_id" id="gt_bank_account_id">
                                    <option value="" disabled selected>Select a bank account...</option>
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ str_replace('_', ' ', $account->account_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="gt_amount">Amount *</label>
                                <input type="number" name="amount" id="gt_amount" step="any" value="{{ old('amount') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label>Transaction Type *</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="gt_expense" value="expense" required>
                                    <label class="form-check-label" for="gt_expense">Expense</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="gt_income" value="income" required>
                                    <label class="form-check-label" for="gt_income">Income</label>
                                </div>
                            </div>
                        </div>

                        {{-- <div class="row">
                            <div class="col-12">
                                <div class="input-group">
                                    <input type="checkbox" name="internal_transfer" id="gt_internal_transfer">
                                    <label for="gt_internal_transfer">Internal Transfer</label>
                                </div>
                            </div>
                        </div> --}}

                        <div class="row mt-2">
                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2" data-loading-text="Saving...">Save</button>
                            </div>
                        </div>
                    </form>
                </div> <!-- /.modal-body -->
            </div>
        </div>
    </div>

    {{-- Fund Transfer --}}
    <div class="modal fade" id="fundTransfer" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h1>Fund Transfer</h1>
                    <form action="{{ route('transactions.global-fund-transfer') }}" method="post">
                        @csrf

                        <div class="row">
                            <div class="col-12">
                                <label for="ft_name">Name of Business/Person *</label>
                                <input type="text" name="name" id="ft_name" value="{{ old('name') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="ft_date">Date *</label>
                                <input type="date" name="date" id="ft_date" value="{{ old('date') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="from_account">From*</label>
                                <select name="from_account" id="from_account">
                                    <option value="" disabled selected>Select a bank account...</option>
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ str_replace('_', ' ', $account->account_name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="to_account">To*</label>
                                <select name="to_account" id="to_account">
                                    <option value="" disabled selected>Select a bank account...</option>
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ str_replace('_', ' ', $account->account_name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="ft_amount">Amount *</label>
                                <input type="number" name="amount" id="ft_amount" step="any" value="{{ old('amount') }}">
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2">Save</button>
                            </div>
                        </div>
                    </form>
                </div> <!-- /.modal-body -->
            </div>
        </div>
    </div>

    {{-- Add Bank Account --}}
    <div class="modal fade" id="addBankAccount" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h1>Add Bank Account</h1>
                    <form action="{{ route('bank-accounts.global-add-bank-account') }}" method="post">
                        @csrf

                        <div class="row">
                            <div class="col-12">
                                <label for="name_of_bank_account">Name of bank</label>
                                <input type="text" name="name_of_bank_account" id="name_of_bank_account" value="{{ old('name_of_bank_account') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="bank_account_type">Account type</label>
                                <select name="bank_account_type" id="bank_account_type">
                                    <option value="" disabled selected>Select an option...</option>
                                    <option value="current_account">Current Account</option>
                                    <option value="savings_account">Savings Account</option>
                                    <option value="isa_account">ISA Account</option>
                                    <option value="investment_account">Investment Account</option>
                                    <option value="pension">Pension</option>
                                    <option value="investment">Investments</option>
                                    <option value="credit_card">Credit Card</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="bank_account_starting_balance">Starting balance</label>
                                <input type="number" name="bank_account_starting_balance" id="bank_account_starting_balance" step="any" value="{{ old('bank_account_starting_balance') }}">
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2" data-loading-text="Saving...">Save</button>
                            </div>
                        </div>
                    </form>
                </div> <!-- /.modal-body -->
            </div>
        </div>
    </div>
@endif

<script>
    document.querySelectorAll('.modal form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const loadingText = submitBtn.dataset.loadingText || 'Saving...';
                submitBtn.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ${loadingText}
                `;
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50');
            }
        });
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
