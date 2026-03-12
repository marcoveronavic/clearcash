<ul class="sidebar-nav">
    <li>
        <a href="{{ route('dashboard') }}" class="{{ Route::is('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-column"></i> {{ __('messages.dashboard') }}
        </a>
    </li>
    <li>
        <a href="{{ route('budget.index') }}"><i class="fa-solid fa-calculator"></i> {{ __('messages.budget') }}</a>
    </li>
    <li>
        <a href="{{ route('bank-accounts.index') }}"><i class="fa-solid fa-building-columns"></i> {{ __('messages.bank_accounts') }}</a>
    </li>
    <li>
        <a href="{{ route('transactions.index') }}"><i class="fa-solid fa-arrow-right-arrow-left"></i> {{ __('messages.transactions') }}</a>
    </li>
    <li>
        {{-- <a href="{{ route('recurring-payments.index') }}"><i class="fa-solid fa-arrows-rotate"></i> {{ __('messages.recurring_payments') }}</a> --}}
    </li>
</ul>
