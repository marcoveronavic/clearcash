<?php

namespace App\Http\Controllers;

use App\Services\PlaidService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaidController extends Controller
{
    public function __construct(private PlaidService $plaid)
    {
        $this->middleware(['auth', 'verified']);
    }

    public function createLinkToken(Request $request)
    {
        $token = $this->plaid->createLinkToken(Auth::id(), config('app.name', 'ClearCash'));
        return response()->json(['link_token' => $token]);
    }

    public function exchangePublicToken(Request $request)
    {
        $data = $request->validate([
            'public_token' => ['required', 'string'],
            'institution'  => ['nullable', 'array'],
        ]);

        Log::info('PLAID exchange called');

        // 1) Exchange
        $exchange = $this->plaid->exchangePublicToken($data['public_token']);
        if (empty($exchange['access_token'])) {
            Log::error('Plaid exchange failed', ['resp' => $exchange]);
            return response()->json(['ok' => false, 'error' => 'exchange_failed'], 400);
        }

        $accessToken = $exchange['access_token'];
        $itemId      = $exchange['item_id'] ?? null;

        // 2) Accounts
        $accounts = $this->plaid->getAccounts($accessToken);

        // 3) Save/Update
        foreach ($accounts as $acc) {
            $plaidAccountId = $acc['account_id'] ?? null;
            $name           = $acc['name'] ?? ($acc['official_name'] ?? ($data['institution']['name'] ?? 'Bank'));
            $typeParts      = array_filter([ $acc['type'] ?? null, $acc['subtype'] ?? null ]);
            $typeCombined   = implode('_', $typeParts);
            $mask           = $acc['mask'] ?? null;
            $current        = $acc['balances']['current'] ?? 0;

            DB::table('bank_accounts')->updateOrInsert(
                [
                    'user_id'          => Auth::id(),
                    'plaid_account_id' => $plaidAccountId,
                ],
                [
                    'account_name'       => $name,
                    'account_type'       => $typeCombined,
                    'starting_balance'   => (float) $current,
                    'plaid_item_id'      => $itemId,
                    'plaid_access_token' => encrypt($accessToken),
                    'institution_name'   => $data['institution']['name'] ?? null,
                    'mask'               => $mask,
                    'updated_at'         => now(),
                    'created_at'         => now(),
                ]
            );
        }

        return response()->json(['ok' => true]);
    }
}
