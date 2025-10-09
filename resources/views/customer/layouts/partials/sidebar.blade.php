<aside class="sidebar open">
    <div class="container">
        <div class="row">
            <div class="col-12 px-0">
                <a class="sidebarBrand" href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/logo/clear-cash-logo.svg') }}" alt="" class="img-fluid">
                </a>
                @include('customer.layouts.navs.sidebarNav')
                <div class="userWrapper">
                    <div class="dropup">
                        <button type="button" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ asset('images/icons/ph_user.png') }}" alt="" class="img-fluid userIcon"> {{ Auth::user()->first_name }}
                            {{-- {{ Auth::user()->last_name }} --}}
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route("my-account.index") }}">My Account</a>
                            </li>
                            <li>
                                <a class="dropdown-item" style="cursor: pointer" onclick="resetAccount()" >Reset Account</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>



    @if (config('sweetalert.animation.enable'))
        <link rel="stylesheet" href="{{ config('sweetalert.animatecss') }}">
    @endif

    @if (config('sweetalert.theme') != 'default')
        <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-{{ config('sweetalert.theme') }}" rel="stylesheet">
    @endif

    @if (config('sweetalert.neverLoadJS') === false)
        <script src="{{ $cdn ?? asset('vendor/sweetalert/sweetalert.all.js') }}"></script>
    @endif


        <script>
            function resetAccount(){

                 if ($(window).width() <= 1080) {
            $('button.sidebarMenuToggler').find('i').toggleClass('fa-bars fa-times')
            $('.sidebar').toggleClass('open', 1000);
            }



                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Are you sure you want to reset your account? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, reset it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("{{ route('reset-account') }}", {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                "Accept": "application/json",
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({})
                        })
                        .then(response => response.json())
                        .then(data => {
                            Swal.fire('Reset!', 'Your account has been reset.', 'success').then(() => {
                                window.location.href = "{{ route('account-setup.step-one') }}";
                            });

                        })
                        .catch(() => {
                            Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                        });
                    }
                });
            }
        </script>




