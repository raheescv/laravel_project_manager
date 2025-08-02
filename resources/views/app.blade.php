<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ https_asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ https_asset('assets/vendors/font-awesome/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ https_asset('assets/css/nifty.min.css') }}">
    <link rel="stylesheet" href="{{ https_asset('assets/css/demo-purpose/demo-icons.min.css') }}">
    <link rel="stylesheet" href="{{ https_asset('assets/premium/icon-sets/line-icons/premium-line-icons.css') }}">
    <link rel="stylesheet" href="{{ https_asset('assets/css/demo-purpose/demo-settings.min.css') }}">
    <link rel="stylesheet" href="{{ https_asset('css/theme-helper.css') }}"><!-- Theme persistence helper -->
    <link rel="stylesheet" href="{{ https_asset('assets/vendors/tom-select/tom-select.min.css') }}">
    <link rel="stylesheet" href="{{ https_asset('assets/vendors/toaster/toastr.min.css') }}">
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia

    <!-- Theme Applier - Must be before other scripts to ensure it runs first -->
    <script src="{{ https_asset('js/theme-applier.js') }}"></script>
    <script src="{{ https_asset('assets/vendors/popperjs/popper.min.js') }}"></script>
    <script src="{{ https_asset('assets/vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ https_asset('assets/js/nifty.js') }}"></script>
    {{-- <script src="{{ https_asset('assets/js/demo-purpose-only.js') }}"></script> --}}
    <script src="{{ https_asset('assets/vendors/popperjs/popper.min.js') }}"></script>
    <script src="{{ https_asset('assets/vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ https_asset('assets/js/nifty.js') }}"></script>
    {{-- <script src="{{ https_asset('assets/js/demo-purpose-only.js') }}"></script> --}}
</body>

</html>
