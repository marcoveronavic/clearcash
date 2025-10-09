<aside class="sidebar open">
    <div class="container">
        <div class="row">
            <div class="col-12 px-0">
                <a class="sidebarBrand" href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/logo/clear-cash-logo.svg') }}" alt="" class="img-fluid">
                </a>
                @include('admin.layouts.navs.sidebarNav')
                <div class="userWrapper">
                    <div class="dropup">
                        <button type="button" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ asset('images/icons/ph_user.png') }}" alt="" class="img-fluid userIcon"> {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                        </button>
                        <ul class="dropdown-menu">
                            {{--<li>
                                <a class="dropdown-item" href="">My Account</a>
                            </li>--}}
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
