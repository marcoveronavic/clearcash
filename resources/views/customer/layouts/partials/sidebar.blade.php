<aside class="sidebar open">
    <div class="container">
        <div class="row">
            <div class="col-12 px-0">
                <a class="sidebarBrand" href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/logo/clear-cash-logo.svg') }}" alt="" class="img-fluid logo-light">
                    <img src="{{ asset('images/logo/clear-cash-logo-dark.svg') }}" alt="" class="img-fluid logo-dark">
                </a>
                @include('customer.layouts.navs.sidebarNav')

                {{-- Theme toggle --}}
                <div class="theme-toggle-wrap">
                    <span class="theme-icon">
                        <i class="fa-solid fa-moon theme-icon-moon"></i>
                        <i class="fa-solid fa-sun theme-icon-sun"></i>
                    </span>
                    <label class="theme-toggle">
                        <input type="checkbox" id="themeToggle">
                        <span class="slider"></span>
                    </label>
                    <span class="theme-toggle-label" id="themeLabel">Light</span>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var t = document.getElementById('themeToggle');
                        var l = document.getElementById('themeLabel');
                        var isLight = localStorage.getItem('cc-theme') === 'light';
                        t.checked = isLight;
                        l.textContent = isLight ? 'Dark' : 'Light';
                        if (isLight && typeof Chart !== 'undefined') Chart.defaults.color = '#374151';
                        t.addEventListener('change', function() {
                            if (this.checked) {
                                document.body.classList.add('light-mode');
                                localStorage.setItem('cc-theme', 'light');
                                l.textContent = 'Dark';
                                if (typeof Chart !== 'undefined') Chart.defaults.color = '#374151';
                            } else {
                                document.body.classList.remove('light-mode');
                                localStorage.setItem('cc-theme', 'dark');
                                l.textContent = 'Light';
                                window.location.reload();
                            }
                        });
                    });
                </script>

                {{-- Language Switcher --}}
                @php
                    $locales = [
                        'it' => ['name' => 'Italiano',   'flag' => '🇮🇹'],
                        'en' => ['name' => 'English',     'flag' => '🇬🇧'],
                        'es' => ['name' => 'Español',     'flag' => '🇪🇸'],
                        'fr' => ['name' => 'Français',    'flag' => '🇫🇷'],
                        'pt' => ['name' => 'Português',   'flag' => '🇧🇷'],
                        'de' => ['name' => 'Deutsch',     'flag' => '🇩🇪'],
                    ];
                    $currentLocale = app()->getLocale();
                @endphp
                <div class="language-switcher-wrap">
                    <div class="dropup">
                        <button type="button" class="language-switcher-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="lang-flag">{{ $locales[$currentLocale]['flag'] ?? '🌐' }}</span>
                            <span class="lang-name">{{ $locales[$currentLocale]['name'] ?? 'Language' }}</span>
                        </button>
                        <ul class="dropdown-menu language-dropdown">
                            @foreach($locales as $code => $lang)
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 {{ $code === $currentLocale ? 'active' : '' }}"
                                       href="{{ route('language.switch', $code) }}">
                                        <span class="lang-flag">{{ $lang['flag'] }}</span>
                                        <span>{{ $lang['name'] }}</span>
                                        @if($code === $currentLocale)
                                            <i class="fas fa-check ms-auto"></i>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="userWrapper">
                    <div class="dropup">
                        <button type="button" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ asset('images/icons/ph_user.png') }}" alt="" class="img-fluid userIcon"> {{ Auth::user()->first_name }}
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route("my-account.index") }}">{{ __('messages.my_account') }}</a>
                            </li>
                            <li>
                                <a class="dropdown-item" style="cursor: pointer" onclick="resetAccount()">{{ __('messages.reset_account') }}</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    {{ __('messages.logout') }}
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
            title: @json(__('messages.are_you_sure')),
            text: @json(__('messages.reset_budget_confirm')),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: @json(__('messages.yes_reset'))
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
                        Swal.fire(@json(__('messages.yes_reset')), @json(__('messages.success')), 'success').then(() => {
                            window.location.href = "{{ route('account-setup.step-one') }}";
                        });
                    })
                    .catch(() => {
                        Swal.fire(@json(__('messages.error')), @json(__('messages.error')), 'error');
                    });
            }
        });
    }
</script>

<style>
    .language-switcher-wrap {
        padding: 10px 20px;
    }
    .language-switcher-btn {
        background: transparent;
        border: 1px solid rgba(45, 212, 191, 0.3);
        border-radius: 8px;
        color: #94a3b8;
        padding: 8px 14px;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .language-switcher-btn:hover,
    .language-switcher-btn:focus {
        border-color: #2DD4BF;
        color: #2DD4BF;
        background: rgba(45, 212, 191, 0.06);
    }
    .language-switcher-btn .lang-flag {
        font-size: 1.2rem;
    }
    .language-switcher-btn::after {
        margin-left: auto;
    }
    .language-dropdown {
        background-color: #0F2D2D !important;
        border: 1px solid rgba(45, 212, 191, 0.25) !important;
        min-width: 100%;
        padding: 4px 0;
    }
    .language-dropdown .dropdown-item {
        color: #94a3b8;
        padding: 8px 14px;
        font-size: 0.85rem;
    }
    .language-dropdown .dropdown-item:hover {
        background-color: rgba(45, 212, 191, 0.1) !important;
        color: #e2e8f0;
    }
    .language-dropdown .dropdown-item.active {
        background-color: rgba(45, 212, 191, 0.15) !important;
        color: #2DD4BF;
    }
    .language-dropdown .dropdown-item .fa-check {
        color: #2DD4BF;
        font-size: 0.7rem;
    }
    .language-dropdown .lang-flag {
        font-size: 1.1rem;
    }

    /* Light mode support */
    .light-mode .language-switcher-btn {
        border-color: rgba(13, 32, 32, 0.2);
        color: #374151;
    }
    .light-mode .language-switcher-btn:hover,
    .light-mode .language-switcher-btn:focus {
        border-color: #0D2020;
        color: #0D2020;
        background: rgba(13, 32, 32, 0.05);
    }
    .light-mode .language-dropdown {
        background-color: #ffffff !important;
        border-color: rgba(13, 32, 32, 0.15) !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .light-mode .language-dropdown .dropdown-item {
        color: #374151;
    }
    .light-mode .language-dropdown .dropdown-item:hover {
        background-color: rgba(13, 32, 32, 0.06) !important;
        color: #0D2020;
    }
    .light-mode .language-dropdown .dropdown-item.active {
        background-color: rgba(13, 32, 32, 0.1) !important;
        color: #0D2020;
    }
    .light-mode .language-dropdown .dropdown-item .fa-check {
        color: #0D2020;
    }
</style>
