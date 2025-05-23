<!DOCTYPE html>

<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default"
    data-assets-path="{{ asset('assets/') }}" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>@yield('title') - {{ $general_settings['company_title'] ?? 'Taskify - Saas' }}</title>


    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="{{ asset($general_settings['favicon'] ?? 'storage/logos/default_favicon.png') }}" />
    @include('front-end.include-css')
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>


</head>

<body class = "">
    @include('front-end.navbar')
      @include('labels')
    <header>

    </header>
    <main class = "mt-4 ">

        @yield('content')

    </main>
    <footer>
        @include('front-end.footer')
    </footer>


    @include('front-end.include-js')

</body>


</html>
