<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\SavingGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SavingGoalController extends Controller
{
    /**
     * Crea un nuovo obiettivo di risparmio.
     */
    public function store(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'target_amount'   => ['required', 'numeric', 'min:1'],
            'bank_account_id' => [
                'required', 'integer',
                Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'deadline'        => ['nullable', 'date', 'after:today'],
            'icon'            => ['nullable', 'string', 'max:50'],
            'color'           => ['nullable', 'string', 'max:20'],
        ]);

        SavingGoal::create([
            'user_id'         => $userId,
            'bank_account_id' => $validated['bank_account_id'],
            'name'            => $validated['name'],
            'target_amount'   => $validated['target_amount'],
            'deadline'        => $validated['deadline'] ?? null,
            'icon'            => $validated['icon'] ?? 'fa-bullseye',
            'color'           => $validated['color'] ?? '#2DD4BF',
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Obiettivo di risparmio creato!');
    }

    /**
     * Aggiorna un obiettivo esistente.
     */
    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        $goal   = SavingGoal::where('user_id', $userId)->findOrFail($id);

        $validated = $request->validate([
            'name'            => ['sometimes', 'string', 'max:255'],
            'target_amount'   => ['sometimes', 'numeric', 'min:1'],
            'bank_account_id' => [
                'sometimes', 'integer',
                Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'deadline'        => ['nullable', 'date'],
            'icon'            => ['nullable', 'string', 'max:50'],
            'color'           => ['nullable', 'string', 'max:20'],
            'status'          => ['sometimes', 'in:active,completed,cancelled'],
        ]);

        $goal->update($validated);

        return redirect()->route('dashboard')
            ->with('success', 'Obiettivo aggiornato!');
    }

    /**
     * Elimina un obiettivo.
     */
    public function destroy(string $id)
    {
        $userId = Auth::id();
        $goal   = SavingGoal::where('user_id', $userId)->findOrFail($id);

        $goal->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Obiettivo eliminato.');
    }
}
