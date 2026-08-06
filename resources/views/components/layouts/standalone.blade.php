{{--
    Standalone full-window shell — no sidebar, header, navigation or footer.

    For screens that are an application in their own right (the barcode template
    designer, for instance) where the surrounding admin chrome only steals space.
    The theme stack is kept intact so --bs-primary, the colour scheme and dark
    mode still follow the user's settings.

        <x-layouts.standalone title="Barcode Template Designer">
            ...
        </x-layouts.standalone>
--}}
@props(['title' => null])
<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-scheme="" data-nav-skin="standard">

<head>
    @include('layouts.theme-init')

    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Astra') }}</title>
    @stack('head')
    <link rel="stylesheet" href="{{ https_asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ https_asset('assets/vendors/font-awesome/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ https_asset('assets/css/nifty.min.css') }}">
    <link rel="stylesheet" href="{{ https_asset('css/theme-helper.css') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ https_asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" href="{{ https_asset('favicon.png') }}">
    <style>
        html,
        body {
            height: 100%;
        }

        body.standalone-body {
            margin: 0;
            background: var(--bs-body-bg);
        }
    </style>
    @stack('styles')
</head>

<body class="standalone-body">
    {{ $slot }}

    <script src="{{ https_asset('js/theme-applier.js') }}"></script>
    @stack('scripts')
</body>

</html>
