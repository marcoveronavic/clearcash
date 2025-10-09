@extends('layouts.customer')
@section('content')
    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1>
                        My Account
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
                                    <label for="first_name">First Name</label>
                                    <input type="text" name="first_name" id="first_name" value="{{ $details->first_name }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" value="{{ $details->last_name }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" id="email" value="{{ $details->email }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <label for="">Password</label>
                                    <button type="button" class="changePasswordBtn" data-bs-toggle="modal" data-bs-target="#changePasswordModel">
                                        Change Password
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="twoToneBlueGreenBtn">Save</button>
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
                                <h3>Delete Your Account</h3>
                                <p>
                                    This action is irreversible, once you have confirmed your intention to delete your account ALL of your data will be removed.  Please proceed with caution.
                                </p>
                            </div>
                            <div class="col-md-3 d-md-flex justify-content-md-end">
                                <form action="{{ route('my-account.delete-account', $details->id) }}" method="post" id="deleteAccountForm">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-lg btn-outline-danger" id="deleteAccountBtn" type="button">Delete</button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h1>Change password</h1>
                    <form action="{{ route('my-account.password-update-store', $details->id) }}" method="post">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-12">
                                <label for="">Current Password</label>
                                <input type="password" name="current_password" id="current_password">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="">New password</label>
                                <input type="password" name="password" id="password">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="">Confirm new password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="twoToneBlueGreenBtn">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.getElementById('deleteAccountBtn').addEventListener('click', function (event) {
            Swal.fire({
                title: "Are you sure?",
                text: "Once deleted, all your data will be permanently removed!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteAccountForm').submit();
                }
            });
        });
    </script>
@endsection
