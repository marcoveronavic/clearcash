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
                                <button type="button" class="modalBtn" data-bs-toggle="modal"
                                    data-bs-target="#addTransaction">
                                    Add Transaction
                                </button>
                            </li>
                            <li>
                                <button type="button" class="modalBtn" data-bs-toggle="modal"
                                    data-bs-target="#fundTransfer">
                                    Fund Transfer
                                </button>
                            </li>
                            {{-- <li>
                                <button type="button" class="modalBtn" data-bs-toggle="modal"
                                    data-bs-target="#addRecurringPayment">
                                    Add Recurring payment
                                </button>
                            </li> --}}
                            <li>
                                <button type="button" class="modalBtn" data-bs-toggle="modal"
                                    data-bs-target="#addBankAccount">
                                    Add Bank Account
                                </button>
                            </li>
                        </ul>
                    </div>



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
                                                    <input type="text" class="form-control" name="name"
                                                        placeholder="Name" value="{{ old('name') }}" required>
                                                </div>
                                                <div class="col-md-5 ps-md-0 d-md-flex justify-content-md-end">
                                                    <div class="input-group">
                                                        <label>£</label>
                                                        <input type="number" min="0" step="any"
                                                            name="amount" placeholder="0.00" required
                                                            style="width: 80% !important;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2"
                                                    data-loading-text="Saving...">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>





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
                                                <label for="name">Name of Business/Person *</label>
                                                <input type="text" name="name" id="name">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="date">Date *</label>
                                                <input type="date" name="date" id="date">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="category">Category *</label>
                                                <select name="category" id="category">
                                                    <option value="" disabled selected>Select a category...
                                                    </option>
                                                    @foreach ($categories as $cat)
                                                        <option value="{{ $cat->id }}"
                                                            style="text-transform: capitalize !important;">
                                                            {{ str_replace('_', ' ', $cat->category_name) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="bank_account">Bank Account *</label>
                                                <select name="bank_account" id="">
                                                    <option value="" disabled selected>Select a bank account...
                                                    </option>
                                                    @foreach ($bankAccounts as $account)
                                                        <option value="{{ $account->id }}">
                                                            {{ str_replace('_', ' ', $account->account_name) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="amount">Amount *</label>
                                                <input type="number" name="amount" id="amount" step="any">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="">Transaction Type *</label>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio"
                                                        name="transaction_type" id="expense" value="expense"
                                                        required>
                                                    <label class="form-check-label" for="expense">Expense</label>
                                                </div>

                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio"
                                                        name="transaction_type" id="income" value="income"
                                                        required>
                                                    <label class="form-check-label" for="income">Income</label>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- <div class="row">
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <input type="checkbox" name="internal_transfer"
                                                        id="internal_transfer">
                                                    <label for="internal_transfer">Internal Transfer</label>
                                                </div>
                                            </div>
                                        </div> --}}
                                        <div class="row">
                                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2"
                                                    data-loading-text="Saving...">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fundTransfer" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <h1>Fund Transfer</h1>
                                    <form action="{{ route('transactions.global-fund-transfer') }}" method="post">
                                        @csrf
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="name">Name of Business/Person *</label>
                                                <input type="text" name="name" id="name">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="date">Date *</label>
                                                <input type="date" name="date" id="date">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="from_account">From*</label>
                                                <select name="from_account" id="">
                                                    <option value="" disabled selected>Select a bank account...
                                                    </option>
                                                    @foreach ($bankAccounts as $account)
                                                        <option value="{{ $account->id }}">
                                                            {{ str_replace('_', ' ', $account->account_name) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="to_account">To*</label>
                                                <select name="to_account" id="">
                                                    <option value="" disabled selected>Select a bank account...
                                                    </option>
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
                                                <label for="amount">Amount *</label>
                                                <input type="number" name="amount" id="amount" step="any">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                                <button type="submit"
                                                    class="twoToneBlueGreenBtn text-center py-2">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- <div class="modal fade" id="addRecurringPayment" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <h1>Add Recurring Payment</h1>
                                    <form action="{{ route('recurring-payment.add-recurring-payment') }}"
                                        method="post">
                                        @csrf
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="name">Name of business/person</label>
                                                <input type="text" name="name" id="name">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="date">Date</label>
                                                <input type="date" name="date" id="date">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="repeat">Repeat</label>
                                                <select name="repeat" id="repeat">
                                                    <option value="" selected disabled>Select an option...
                                                    </option>
                                                    <option value="weekly">Weekly</option>
                                                    <option value="fortnightly">Fortnightly</option>
                                                    <option value="monthly">Monthly</option>
                                                    <option value="semi_annually">Semi-annually</option>
                                                    <option value="annually">Annually</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="category">Category</label>
                                                <select name="category" id="category">
                                                    <option value="" selected disabled>Select an option...
                                                    </option>
                                                    @foreach ($categories as $cat)
                                                        <option value="{{ $cat->id }}">
                                                            {{ str_replace('_', ' ', $cat->category_name) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="bank_account">Bank Account</label>
                                                <select name="bank_account" id="bank_account">
                                                    <option value="" selected disabled>Select an option...
                                                    </option>
                                                    @foreach ($bankAccounts as $bank)
                                                        <option value="{{ $bank->id }}">{{ $bank->account_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="amount">Amount</label>
                                                <input type="number" name="amount" id="amount" step="any">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="">Transaction Type</label>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio"
                                                        name="transaction_type" id="expense" value="expense"
                                                        required>
                                                    <label class="form-check-label" for="expense">Expense</label>
                                                </div>

                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio"
                                                        name="transaction_type" id="income" value="income"
                                                        required>
                                                    <label class="form-check-label" for="income">Income</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <input type="checkbox" name="internal_transfer"
                                                        id="internal_transfer">
                                                    <label for="internal_transfer">Internal Transfer</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2"
                                                    data-loading-text="Saving...">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    <div class="modal fade" id="addBankAccount" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <h1>Add Bank Account</h1>
                                    <form action="{{ route('bank-accounts.global-add-bank-account') }}"
                                        method="post">
                                        @csrf
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="name_of_bank_account">Name of bank</label>
                                                <input type="text" name="name_of_bank_account"
                                                    id="name_of_bank_account">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="bank_account_type">Account type span</label>
                                                <select name="bank_account_type" id="bank_account_type">
                                                    <option value="" disabled selected>Select an option...
                                                    </option>
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
                                                <input type="number" name="bank_account_starting_balance"
                                                    id="bank_account_starting_balance" step="any">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                                <button type="submit" class="twoToneBlueGreenBtn text-center py-2"
                                                    data-loading-text="Saving...">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                </main>
                </div>

                <script>
                    document.querySelectorAll('.modal form').forEach(form => {
                        form.addEventListener('submit', function(e) {
                            const submitBtn = form.querySelector('button[type="submit"]');
                            if (submitBtn) {
                                // Set loading text
                                const loadingText = submitBtn.dataset.loadingText || 'Saving...';

                                // Add spinner + text
                                submitBtn.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ${loadingText}
                `;

                                // Disable the button to prevent duplicate submissions
                                submitBtn.disabled = true;
                                submitBtn.classList.add('opacity-50');
                            }
                        });
                    });
                </script>
                <script src="{{ asset('/sw.js') }}"></script>
                <script>
                    if ("serviceWorker" in navigator) {
                        // Register a service worker hosted at the root of the
                        // site using the default scope.
                        navigator.serviceWorker.register("/sw.js").then(
                            (registration) => {
                                console.log("Service worker registration succeeded:", registration);
                            },
                            (error) => {
                                console.error(`Service worker registration failed: ${error}`);
                            },
                        );
                    } else {
                        console.error("Service workers are not supported.");
                    }
                </script>
                </body>

                </html>
