<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(){

        $activeCustomerCount = User::role('customer')->count();
        $totalAmountofBankAccountsLink = BankAccount::count();

        $customerList = User::role('customer')
            ->orderBy('created_at', 'desc')
            ->withCount('bankAccount')
            ->get();


        return view('admin.pages.dashboard', compact('activeCustomerCount', 'customerList', 'totalAmountofBankAccountsLink'));
    }
}
