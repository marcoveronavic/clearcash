<ul class="sidebar-nav">
    <li>
        <a href="{{ route('dashboard') }}" class="{{ Route::is('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-column"></i> Dashboard
        </a>
    </li>
    <li>
        <a href="{{ route('budget.index') }}"><i class="fa-solid fa-calculator"></i> Budget</a>
    </li>
    <li>
        <a href="{{ route('bank-accounts.index') }}"><i class="fa-solid fa-building-columns"></i> Bank Accounts</a>
    </li>
    <li>
        <a href="{{ route('transactions.index') }}"><i class="fa-solid fa-arrow-right-arrow-left"></i> Transactions</a>
    </li>
    <li>
        {{-- <a href="{{ route('recurring-payments.index') }}"><i class="fa-solid fa-arrows-rotate"></i> Recurring Payments</a> --}}
    </li>
</ul>
