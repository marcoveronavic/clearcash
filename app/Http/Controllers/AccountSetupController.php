<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\CustomerAccountDetails;
use App\Models\DefaultBudgetCategories;
use App\Models\RecurringPayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AccountSetupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $accSetup = $request->session()->get('accSetup');

        $request->session()->forget('accSetup.period_selection');


        return view('account-setup.index', compact('accSetup'));
    }

    public function indexStore(Request $request)
    {

        $validated = $request->validate([
            'period_selection' => 'required', // Ensure it's required for debugging
        ]);

        // Check if the session has 'accSetup' data
        if (!$request->session()->has('accSetup')) {
            $accSetup = []; // Initialize an empty array instead of model
        } else {
            $accSetup = $request->session()->get('accSetup', []);
        }

        // Process the selected period
        if ($validated['period_selection'] == 'first_day') {
            $accSetup = array_merge($accSetup, [
                'period_selection' => $validated['period_selection'],
                'date' => 1,
                // 'date' => 1,
            ]);

            // Save the updated session data
            $request->session()->put('accSetup', $accSetup);

            return to_route('account-setup.step-three');
        } elseif ($validated['period_selection'] == 'last_working') {
            $lastDayOfMonth = Carbon::now()->endOfMonth(); // Get last day of the month

            // Adjust if the last day falls on the weekend
            if ($lastDayOfMonth->isSaturday()) {
                $lastDayOfMonth->subDay(); // Move to Friday
            } elseif ($lastDayOfMonth->isSunday()) {
                $lastDayOfMonth->subDays(2); // Move to Friday
            }

            $lastWorkingDay = $lastDayOfMonth->day;


            $accSetup = array_merge($accSetup, [
                'period_selection' => $validated['period_selection'],
                'date' => $lastWorkingDay,
            ]);

            // dd($accSetup["date"]);

            // Save the updated session data
            $request->session()->put('accSetup', $accSetup);

            return to_route('account-setup.step-three');
        } else {
            // For other period selections, just merge the validated data
            $accSetup = array_merge($accSetup, $validated);
            $request->session()->put('accSetup', $accSetup);

            return to_route('account-setup.step-two');
        }
    }


    public function stepTwoShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup');

        return view('account-setup.step-two', compact('accSetup'));
    }
    public function stepTwoStore(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|integer|between:1,31',
        ]);

        $selectedDate = $request->input('date');

        //Check if existing session
        if (empty($request->session()->get('accSetup'))) {
            $accSetup = new CustomerAccountDetails();
        } else {
            $accSetup = $request->session()->get('accSetup', []);
        }
        $accSetup = array_merge($accSetup, $validated);
        $request->session()->put('accSetup', $accSetup);

        // Redirect to the next step
        return to_route('account-setup.step-three');
    }

    public function stepThreeShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup');

        $defaultBudgetCategories = DefaultBudgetCategories::get();

        // dd($accSetup);

        return view('account-setup.step-three', compact('accSetup', 'defaultBudgetCategories'));
    }

    public function stepThreeStore(Request $request)
    {
        $validated = $request->validate([
            'salary_date' => ['required'],
            'salary_amount' => ['required'],
            '*_amount' => ['nullable', 'numeric'],
            'other_name.*' => ['nullable', 'string'],
            'other_amounts.*' => ['nullable', 'numeric'],
        ]);






        //Check if existing session
        if (empty($request->session()->get('accSetup'))) {
            $accSetup = new CustomerAccountDetails();
        } else {
            $accSetup = $request->session()->get('accSetup', []);
        }
        $accSetup = array_merge($accSetup, $validated);


        $request->session()->put('accSetup', $accSetup);
        // dd($accSetup['period_selection']);

        // Redirect to next step (bank accounts)
        return to_route('account-setup.step-four');
    }

    public function stepFourShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup');

        // Calculate total expenses
        $totalExpenses = collect($accSetup)
            ->filter(function ($value, $key) {
                return preg_match('/^expense_.*_amount$/', $key);
            })
            ->sum();

        // Sum all values properly
        $totalAmount = $totalExpenses
            + ($accSetup['savings_pension_amount'] ?? 0)
            + ($accSetup['savings_investments_amount'] ?? 0) // Fixed key name to match the array
            + array_sum($accSetup['other_amounts'] ?? []); // Sum the array values safely


        // dd($accSetup, $totalAmount);

        return view('account-setup.step-four', compact('accSetup', 'totalAmount'));
    }


    public function stepFourStore(Request $request)
    {
        if (empty($request->session()->get('accSetup'))) {
            return to_route('account-setup.step-one');
        } else {
            $accSetup = $request->session()->get('accSetup');
        }
        return to_route('account-setup.step-five');
    }

    public function stepFiveShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup');

        return view('account-setup.step-five', compact('accSetup'));
    }
    public function stepFiveStore(Request $request)
    {

        $validated = $request->validate([
            'name_of_bank_account.*' => 'required|string',
            'bank_account_type.*' => 'required|string',
            'bank_account_starting_balance.*' => 'required|numeric',
            'other_name.*' => 'nullable|string',
            'other_amounts.*' => 'nullable|numeric|min:0',
        ]);

        // Ensure session exists and is an array
        if (!$request->session()->has('accSetup')) {
            return to_route('account-setup.step-one');
        }

        $accSetup = $request->session()->get('accSetup', []);

        if (!is_array($accSetup)) {
            $accSetup = [];
        }

        $accSetup['bank_accounts'] = [];

        // Store multiple bank accounts in session
        foreach ($validated['name_of_bank_account'] as $index => $bankName) {
            $accSetup['bank_accounts'][] = [
                'name_of_bank_account' => $bankName,
                'bank_account_type' => $validated['bank_account_type'][$index],
                'bank_account_starting_balance' => $validated['bank_account_starting_balance'][$index],
            ];
        }

        $request->session()->put('accSetup', $accSetup);

        // dd($accSetup);

        // Redirect to new investments/pensions step
        return to_route('account-setup.step-six-investments');
    }

    // New: Show investments/pensions step
    public function stepSixInvestmentsShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup');
        return view('account-setup.step-six-investments', compact('accSetup'));
    }

    // New: Store investments/pensions and finish setup
    public function stepSixInvestmentsStore(Request $request)
    {
        // $validated = $request->validate([
        //     'name_of_pension_investment_account' => ['nullable', 'string'],
        //     'pension_investment_type' => ['nullable', 'string'],
        //     'savings_pension_amount' => ['nullable', 'numeric', 'min:0'],
        // ]);

        // dd($request->all());


        $accSetup = $request->session()->get('accSetup', []);
        // $accSetup = array_merge($accSetup, $validated);
        $request->session()->put('accSetup', $accSetup);

        $user = Auth::user();

        // Save investments and pensions as bank accounts if provided
        // if (!empty($accSetup['savings_pension_amount'])) {
        //     BankAccount::create([
        //         'account_name' => 'Pension',
        //         'account_type' => 'pension',
        //         'starting_balance' => $accSetup['savings_pension_amount'],
        //         'user_id' => $user->id,
        //     ]);
        // }
        // if (!empty($accSetup['savings_investments_amount'])) {
        //     BankAccount::create([
        //         'account_name' => 'Main Investment Account',
        //         'account_type' => 'investment',
        //         'starting_balance' => $accSetup['savings_investments_amount'],
        //         'user_id' => $user->id,
        //     ]);
        // }

        if (!empty($request->name_of_pension_investment_account)) {
            foreach ($request->name_of_pension_investment_account as $key => $pi_account) {
                if (
                    !empty($pi_account) &&
                    !empty($request->pension_investment_type[$key]) &&
                    !empty($request->pension_investment_account_starting_balance[$key])
                ) {
                    BankAccount::create([
                        'account_name' => $pi_account,
                        'account_type' => $request->pension_investment_type[$key],
                        'starting_balance' => $request->pension_investment_account_starting_balance[$key],
                        'user_id' => $user->id,
                    ]);
                }
            }
        }


        // Extract existing expenses from session
        $expenses = [];
        foreach ($accSetup as $key => $value) {
            if (strpos($key, 'expense_') === 0 && str_ends_with($key, '_amount')) {
                $category = str_replace(['expense_', '_amount'], '', $key);
                $expenses[$category] = $value;
            }
        }

        // Add new expenses from form input
        if (!empty($accSetup['other_name'])) {
            foreach ($accSetup['other_name'] as $index => $category) {
                if (!empty($category) && isset($accSetup['other_amounts'][$index])) {
                    $expenses[$category] = $accSetup['other_amounts'][$index];
                }
            }
        }

        if ($accSetup['period_selection'] == 'first_day') {
            $budget_start_date = Carbon::now()->startOfMonth()->toDateString();
            $budgetExpiryDate = Carbon::now()->endOfMonth()->toDateString();
        } elseif ($accSetup['period_selection'] == 'last_working') {
            $budget_start_date = Carbon::now()->startOfMonth()->toDateString();
            $budgetExpiryDate = Carbon::now()->endOfMonth()->toDateString();
        } else {
            // $accSetup['date'] contains the day (e.g., 5), so get this month's date with that day
            $budget_start_date = Carbon::create(
                Carbon::now()->year,
                Carbon::now()->month,
                $accSetup['date'],
                0,
                0,
                0,
                config('app.timezone')
            )->toDateString();

            // dd($budget_start_date);
            $nextMonth = Carbon::now()->addMonth();
            $budgetExpiryDate = Carbon::create($nextMonth->year, $nextMonth->month, $accSetup['date'], 0, 0, 0, config('app.timezone'))->toDateString();
        }

        // Get budget expiry date
        // $budgetExpiryDay = $accSetup['date'] ?? 1;
        // $budgetExpiryDate = Carbon::create($nextMonth->year, $nextMonth->month, $budgetExpiryDay, 0, 0, 0, config('app.timezone'))->toDateString();

        // dd($accSetup);
        // Store all expenses (existing + new) in BudgetCategory and Budget

        foreach ($expenses as $category => $amount) {
            try {
                $budgetCategory = BudgetCategory::firstOrCreate([
                    'user_id' => $user->id,
                    'name' => $category,
                ]);

                Budget::create([
                    'category_id' => $budgetCategory->id,
                    'category_name' => $budgetCategory->name,
                    'amount' => $amount,
                    'user_id' => $user->id,
                    // 'budget_start_date' => Carbon::today(),
                    'budget_start_date' => $budget_start_date,
                    'budget_end_date' => $budgetExpiryDate,
                ]);
            } catch (\Exception $e) {
                Log::error('Error inserting Budget/BudgetCategory:', ['message' => $e->getMessage()]);
            }
        }

        if (!$accSetup['date']) {
            $accSetup['date'] = Carbon::today()->toDateString();
        }

        // Store customer account details
        CustomerAccountDetails::create([
            'customer_id' => $user->id,
            'period_selection' => $accSetup['period_selection'] ?? null,
            'renewal_date' => $accSetup['date'] ?? null,
        ]);

        // Store multiple bank accounts in the database
        foreach ($accSetup['bank_accounts'] as $bank) {
            BankAccount::create([
                'account_name' => $bank['name_of_bank_account'],
                'account_type' => $bank['bank_account_type'],
                'starting_balance' => $bank['bank_account_starting_balance'],
                'user_id' => $user->id,
            ]);
        }

        $bankAccount = BankAccount::where('user_id', Auth::user()->id)->first();

        $salaryBudgetCat = Budget::create([
            'category_name' => 'salary',
            'amount' => $accSetup['salary_amount'],
            'user_id' => $user->id,
            'budget_start_date' => $budget_start_date,
            'budget_end_date' => $budgetExpiryDate,
        ]);


        RecurringPayment::create([
            'name' => 'Salary',
            'repeat' => 'monthly',
            'start_date' => $accSetup['salary_date'] ?? null,
            'amount' => $accSetup['salary_amount'] ?? null,
            'bank_account_id' => $bankAccount->id,
            'transaction_type' => 'income',
            'user_id' => Auth::user()->id
        ]);

        Transaction::create([
            'name' => 'Salary',
            'date' => $accSetup['salary_date'] ?? null,
            'category_name' => 'salary',
            'bank_account_id' => $bankAccount->id,
            'amount' => $accSetup['salary_amount'] ?? null,
            'transaction_type' => 'income',
            'user_id' => Auth::user()->id
        ]);

        $bankAccountCurrentBalance = $bankAccount->starting_balance;

        $bankAccount->update([
            'starting_balance' => $bankAccountCurrentBalance + $accSetup['salary_amount'],
        ]);

        $uncategorisedBudgetCat = Budget::create([
            'category_name' => 'uncategorised',
            'amount' => '0',
            'user_id' => $user->id,
            'budget_start_date' => Carbon::today(),
            'budget_end_date' => $budgetExpiryDate,
        ]);

        // Mark user setup as completed
        User::where('id', $user->id)->update([
            'has_completed_setup' => true,
        ]);

        // Forget the session
        $request->session()->forget('accSetup');

        // Redirect to final done step
        return to_route('account-setup.step-seven');
    }

    // New: Show final done step (was step six)
    public function stepSevenShow(Request $request)
    {
        return view('account-setup.step-seven');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
