{{--
    Vue sign-in screen (resources/js/components/Auth/LoginScreen.vue). It posts
    JSON to the login route; layout and live background come from Settings →
    Login Page via App\Support\LoginScreen.
--}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · {{ config('app.name') }}</title>
    <script>
        try {
            var stored = localStorage.getItem('login-theme');
            document.documentElement.dataset.theme = stored || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ https_asset('assets/vendors/font-awesome/font-awesome.min.css') }}">
    <link rel="icon" type="image/png" href="{{ https_asset('favicon.png') }}">
    @vite('resources/js/login-page.js')
</head>

<body>
    <div id="login-app" data-screen='@json($screen)'></div>
    <noscript>
        <p style="font-family: system-ui, sans-serif; text-align: center; padding: 40px 16px;">Please enable JavaScript to sign in.</p>
    </noscript>
</body>

</html>
