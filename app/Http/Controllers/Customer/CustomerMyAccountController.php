<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\CustomerAccountDetails;
use App\Models\RecurringPayment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerMyAccountController extends Controller
{
    /**
     * My Account page.
     */
    public function index()
    {
        $details = Auth::user();

        return view('customer.pages.my-account.index', compact('details'));
    }

    /**
     * Update name + email.
     */
    public function mainDetailsStore(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'email'      => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        // fallback ai valori correnti se i campi sono omessi
        $first = $validated['first_name'] ?? $user->first_name;
        $last  = $validated['last_name']  ?? $user->last_name;
        $email = $validated['email']      ?? $user->email;

        $user->first_name = $first;
        $user->last_name  = $last;
        $user->full_name  = trim($first . ' ' . $last);

        if ($email !== $user->email) {
            $user->email = $email;
            // Se vuoi forzare la riverifica email, sblocca la riga sotto:
            // $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('my-account.index')
            ->with('success', 'Your account details have been updated.');
    }

    /**
     * Update password.
     */
    public function passwordUpdateStore(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'min:8'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->with('error', 'Your current password is incorrect');
        }

        if (Hash::check($validated['password'], $user->password)) {
            return back()->with('error', 'Your new password must be different from your current password.');
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->route('my-account.index')->with('success', 'Your password has been updated.');
    }

    public function store(Request $request) { /* not used */ }
    public function show(string $id) { /* not used */ }
    public function update(Request $request, string $id) { /* not used */ }

    /**
     * Delete account + all user data, then logout.
     */
    public function destroy(string $id, Request $request)
    {
        $customer = User::findOrFail($id);

        DB::beginTransaction();
        try {
            // Purge data (ordine non critico, ma coerente)
            Budget::where('user_id', $id)->delete();
            BudgetCategory::where('user_id', $id)->delete();
            RecurringPayment::where('user_id', $id)->delete();
            Transaction::where('user_id', $id)->delete();
            CustomerAccountDetails::where('customer_id', $id)->delete();

            // Bank accounts: delete (funziona sia con che senza SoftDeletes)
            BankAccount::where('user_id', $id)->delete();

            Auth::logout();

            $customer->delete();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Unable to delete your account: ' . $e->getMessage());
        }

        return redirect()->route('login')
            ->with('success', 'Your account has been deleted and your data removed.');
    }

    /**
     * Hard reset: azzera tutto ma lascia l'utente attivo per ripetere il setup.
     */
    public function resetAccount(Request $request)
    {
        $userId = Auth::id();

        DB::beginTransaction();
        try {
            Budget::where('user_id', $userId)->delete();
            BudgetCategory::where('user_id', $userId)->delete();
            RecurringPayment::where('user_id', $userId)->delete();
            Transaction::where('user_id', $userId)->delete();
            CustomerAccountDetails::where('customer_id', $userId)->delete();
            BankAccount::where('user_id', $userId)->delete();

            // Flag di setup
            $user = User::find($userId);
            if ($user) {
                $user->has_completed_setup = 0;
                $user->save();
            }

            // Pulisci eventuali dati di sessione legati al setup
            $request->session()->forget([
                'budgets',
                'recurring_payments',
                'transactions',
                'customer_account_details',
                'bank_accounts',
                'accSetup',
            ]);
            $request->session()->regenerate();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Reset failed: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success']);
        }

        // In UI classica rimanda allo step iniziale
        return redirect()->route('account-setup.step-one')
            ->with('success', 'Your account has been reset. Start the setup again.');
    }
}
