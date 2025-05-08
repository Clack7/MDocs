<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/emmet.js') }}" defer></script>
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0">
            <router-link class="navbar-brand col-md-3 col-lg-2 mr-0" to="/">{{ config('app.name', 'Laravel') }}</router-link>
            <search-component></search-component>
            <ul class="navbar-nav px-3">
                <li class="nav-item text-nowrap">
                    <router-link class="nav-link" to="/create">Create</router-link>
                </li>
            </ul>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <sidebar-component></sidebar-component>

                <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-0">
                    @yield('content')
                </main>
            </div>
        </div>

        <div class="loader-overlay" :class="{ 'loader-overlay-show': $root.$data.loader }">
            <div class="d-flex justify-content-center align-items-center h-100">
                <div class="spinner-border"  style="width: 4rem; height: 4rem;">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.UtilConfig = {
            spaRoutes: {!! json_encode(config('app.spa.routes')) !!},
            vim: { enabled: {{ config('mdocs.vim.enabled') ? 'true' : 'false' }} }
        };
        window.MDocs = {
            name: '{{ config('app.name', 'Laravel') }}',
            char_regex: '{{ config('mdocs.char_regex') }}',
            type: '{{ config('mdocs.type') }}',
        };
    </script>
</body>
</html>
