<header class="sticky-top">
    <div class="topbar d-none d-sm-none d-md-none d-lg-block d-xl-block">
        <div class="d-flex align-items-center w-100 px-4">
            <div class="col-xl-3 d-flex align-items-center">
                <button class="sidebarMenuToggler">
                    <i class="fas fa-times"></i>
                </button>
                <a href="" class="topbar-brand">
                    {{ config('app.name') }}
                </a>
            </div>
            <div class="col-xl-6">
                <form action="" method="post" class="searchFormMain">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="" id="" class="form-control">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            <div class="col-xl-3 d-lg-flex justify-content-lg-end">
                <ul class="list-inline topbarEndList">
                    <li class="list-inline-item dropdown">
                        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-plus"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">

                        </ul>
                    </li>
                    <li class="list-inline-item notifications">
                        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                        </a>
                    </li>
                    <li class="list-inline-item dropdown userDropdown">
                        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            @if(Auth::user()->avatar)
                                <img class="img-fluid userAvatar" src="{{ asset(Auth::user()->avatar) }}" alt="{{ Auth::user()->first_name }} {{ Auth::user()->last_name }} avatar">
                            @else
                                <img class="img-fluid userAvatar" src="{{ asset('images/male-avatar.jpg') }}" alt="{{ Auth::user()->first_name }} {{ Auth::user()->last_name }} avatar">
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="">My Account</a>
                            </li>
                            <hr>
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
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="topbar d-sm-block d-md-block d-lg-none d-xl-none">
        <div class="col-12 d-flex align-items-center justify-content-between gap-4">
            <a href="" class="topbar-brand">
                {{ config('app.name') }}
            </a>

            <ul class="list-inline topbarEndList">
                <li class="list-inline-item dropdown">
                    <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-plus"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">

                    </ul>
                </li>
                <li class="list-inline-item notifications">
                    <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                    </a>
                </li>
                <li class="list-inline-item dropdown userDropdown">
                    <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        @if(Auth::user()->avatar)
                            <img class="img-fluid userAvatar" src="{{ asset(Auth::user()->avatar) }}" alt="{{ Auth::user()->first_name }} {{ Auth::user()->last_name }} avatar">
                        @else
                            <img class="img-fluid userAvatar" src="{{ asset('images/male-avatar.jpg') }}" alt="{{ Auth::user()->first_name }} {{ Auth::user()->last_name }} avatar">
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a href="">My Account</a>
                        </li>
                        <hr>
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
                </li>
            </ul>
            <button class="sidebarMenuToggler">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</header>
