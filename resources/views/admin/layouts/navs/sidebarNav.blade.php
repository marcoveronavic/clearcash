<ul class="sidebar-nav">
    <li>
        <a href="{{ route('admin.dashboard') }}" class="{{ Route::is('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
    </li>
    {{--<li class="dropdown">
        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Billing</a>
        <ul class="dropdown-menu">
            <li><a href="">Expenses</a></li>
            <li><a href="">Transactions</a></li>
            <li class="dropdown dropdown-submenu">
                <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Invoices</a>
                <ul class="dropdown-menu submenu-dropdown-menu">
                    <li><a href="">All Invoices</a></li>
                    <li><a href="">Create Invoice</a></li>
                    <hr>
                    <li><a href="">Paid</a></li>
                    <li><a href="">Unpaid</a></li>
                    <li><a href="">Draft</a></li>
                    <li><a href="">Cancelled</a></li>
                    <li><a href="">Refunded</a></li>
                    <li><a href="">Cancellations</a></li>
                    <li><a href="">Payment Pending</a></li>
                </ul>
            </li>
            <li class="dropdown dropdown-submenu">
                <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Billable Items</a>
                <ul class="dropdown-menu"></ul>
            </li>
            <li><a href="">Quotes</a></li>
            <li><a href="">Estimates</a></li>
        </ul>
    </li>--}}
    {{--<li class="dropdown">
        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Staff</a>
        <ul class="dropdown-menu">
            <li>
                <a href="">All Staff</a>
            </li>
            <li>
                <a href="">Add Staff Member</a>
            </li>
        </ul>
    </li>--}}
    {{--<hr>
    <li>
        <a href="{{ route('admin.activity-log.index') }}">Activity Log</a>
    </li>--}}
    {{--<li class="dropdown">
        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            Settings
        </a>
        <ul class="dropdown-menu">
            <li>
                <a href="">General Settings</a>
            </li>
            <li>
                <a href="">Branding</a>
            </li>
            <li>
                <a href="">Email Settings</a>
            </li>
            <li>
                <a href="">Roles</a>
            </li>
            <li>
                <a href="">Permissions</a>
            </li>
        </ul>
    </li>--}}
</ul>
