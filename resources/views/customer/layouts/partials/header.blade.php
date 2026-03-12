<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="robots" content="noindex, nofollow">

    <title>@stack('page-title') - {{ config('app.name') }}</title>

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('images/favicons/apple-icon-57x57.png') }}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{ asset('images/favicons/apple-icon-60x60.png') }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('images/favicons/apple-icon-72x72.png') }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('images/favicons/apple-icon-76x76.png') }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('images/favicons/apple-icon-114x114.png') }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('images/favicons/apple-icon-120x120.png') }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('images/favicons/apple-icon-144x144.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('images/favicons/apple-icon-152x152.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicons/apple-icon-180x180.png') }}">
    <link rel="icon" type="image/png" sizes="192x192"
          href="{{ asset('images/favicons/android-icon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('images/favicons/favicon-96x96.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicons/favicon-16x16.png') }}">
    <meta name="msapplication-TileColor" content="#061d21">
    <meta name="msapplication-TileImage" content="{{ asset('images/favicons/ms-icon-144x144.png') }}">
    <meta name="theme-color" content="#061d21">

    <!-- Stylesheets -->
    @stack('page-styles')

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>if(localStorage.getItem('cc-theme')==='light' && typeof Chart!=='undefined')Chart.defaults.color='#374151';</script>
    @stack('page-scripts')

    @vite(['resources/sass/app.scss', 'resources/sass/customer.scss', 'resources/js/app.js', 'resources/js/customer.js'])

    <link rel="manifest" href="{{ asset('manifest.json') }}">

    @yield('styles_in_head')

    <!-- Light mode CSS (external file) -->
    <link rel="stylesheet" href="{{ asset('css/light-mode.css') }}">
    <script>if(localStorage.getItem('cc-theme')==='light')document.documentElement.classList.add('light-mode');</script>
</head>

<body>
<script>if(localStorage.getItem('cc-theme')==='light')document.body.classList.add('light-mode');</script>

@include('sweetalert::alert')
<div class="app-main-container">
    @include('customer.layouts.partials.sidebar')
    <main class="dashboardMain">
        <header class="d-sm-block d-md-block d-lg-none d-xl-none">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-12">
                        <div class="headWrap">
                            <button type="button" class="sidebarMenuToggler">
                                <i class="fas fa-bars"></i>
                            </button>
                            <a href="{{ route('dashboard') }}" class="brand">
                                <img src="{{ asset('images/logo/clear-cash-logo.svg') }}" alt="" class="img-fluid logo-light">
                                <img src="{{ asset('images/logo/clear-cash-logo-dark.svg') }}" alt="" class="img-fluid logo-dark">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
