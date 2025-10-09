@extends('layouts.admin')

@section('content')
    <section class="adminDashboardMainWrapper">
        <div class="container">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="adminDashItem small">
                        <h4>{{ $activeCustomerCount }}</h4>
                        <h6>Active Customers</h6>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="adminDashItem small">
                        <h4>{{ $totalAmountofBankAccountsLink }}</h4>
                        <h6>Total Bank Accounts</h6>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="adminDashItem medium">
                        <h4>User Details</h4>
                        <div class="table-responsive table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email Address</th>
                                        <th>Number of Bank Accounts</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customerList as $customer)
                                        <tr>
                                            <td>{{ $customer->full_name }}</td>
                                            <td>{{ $customer->email }}</td>
                                            <td>{{ $customer->bank_account_count }}</td>
                                            <td>{{ date('d/m/Y', strtotime($customer->created_at)) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
