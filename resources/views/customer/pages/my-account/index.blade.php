@extends('layouts.customer')
@section('content')
    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1>
                        {{ __('messages.my_account') }}
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <section class="myAccountBox">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="inner">
                        <form action="{{ route('my-account.main-details-store', $details->id) }}" method="post">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-12">
                                    <label for="first_name">{{ __('messages.first_name') }}</label>
                                    <input type="text" name="first_name" id="first_name" value="{{ $details->first_name }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <label for="last_name">{{ __('messages.last_name') }}</label>
                                    <input type="text" name="last_name" id="last_name" value="{{ $details->last_name }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <label for="email">{{ __('messages.email') }}</label>
                                    <input type="email" name="email" id="email" value="{{ $details->email }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <label for="">{{ __('messages.password') }}</label>
                                    <button type="button" class="changePasswordBtn" data-bs-toggle="modal" data-bs-target="#changePasswordModel">
                                        {{ __('messages.change_password') }}
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="twoToneBlueGreenBtn">{{ __('messages.save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="accountDeletionBanner">
                        <div class="row align-items-center">
                            <div class="col-md-9">
                                <h3>{{ __('messages.delete_your_account') }}</h3>
                                <p>
                                    {{ __('messages.delete_account_warning') }}
                                </p>
                            </div>
                            <div class="col-md-3 d-md-flex justify-content-md-end">
                                <form action="{{ route('my-account.delete-account', $details->id) }}" method="post" id="deleteAccountForm">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-lg btn-outline-danger" id="deleteAccountBtn" type="button">{{ __('messages.delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="changePasswordModel" tabindex="-1" aria-labelledby="changePasswordModel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h1>{{ __('messages.change_password') }}</h1>
                    <form action="{{ route('my-account.password-update-store', $details->id) }}" method="post">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-12">
                                <label for="">{{ __('messages.current_password') }}</label>
                                <input type="password" name="current_password" id="current_password">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="">{{ __('messages.new_password') }}</label>
                                <input type="password" name="password" id="password">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="">{{ __('messages.confirm_new_password') }}</label>
                                <input type="password" name="password_confirmation" id="password_confirmation">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="twoToneBlueGreenBtn">{{ __('messages.save') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        var swalTitle = @json(__('messages.are_you_sure'));
        var swalText = @json(__('messages.delete_account_swal_text'));
        var swalConfirm = @json(__('messages.yes_delete'));
        var swalCancelText = @json(__('messages.cancel'));

        document.getElementById('deleteAccountBtn').addEventListener('click', function (event) {
            Swal.fire({
                title: swalTitle,
                text: swalText,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: swalConfirm,
                cancelButtonText: swalCancelText
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteAccountForm').submit();
                }
            });
        });
    </script>
@endsection
