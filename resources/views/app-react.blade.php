<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light" data-scheme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/font-awesome/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/nifty.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/demo-purpose/demo-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/premium/icon-sets/line-icons/premium-line-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/demo-purpose/demo-settings.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-helper.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/tom-select/tom-select.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/toaster/toastr.min.css') }}">

    @viteReactRefresh
    @vite('resources/js/react/app.jsx')
    @inertiaHead
</head>
<body class="out-quart">
    @inertia

    <!-- JS Libraries -->
    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/theme-applier.js') }}"></script>
    <script src="{{ asset('assets/vendors/popperjs/popper.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/nifty.js') }}"></script>
    <script src="{{ asset('assets/vendors/toaster/toastr.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/tom-select/tom-select.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/sweetalert/sweetalert2.js') }}"></script>

    <!-- Initialize Theme, TomSelect, Toastr -->
    <script>
        window.addEventListener('load', function() {
            if (typeof applyStoredThemeSettings === 'function') applyStoredThemeSettings();
        });

        $(document).ready(function() {
            $(".tomSelect").each(function () {
                new TomSelect(this, {
                    plugins: ["remove_button"],
                    sortField: { field: "text", direction: "asc" }
                });
            });

            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: "5000"
            };
        });
    </script>
</body>
</html>
