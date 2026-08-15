<!DOCTYPE html>
{{--
    Public appointment page shell — no app chrome, no auth, no sidebar.

    The "Estate" design puts the company's brandmark inside the page's own dark
    hero rather than in a masthead card, so this shell deliberately carries very
    little: the warm ivory ground the hero sits on, the closing fine print, and
    the Bootstrap tokens the .apxp system derives its accent from (they normally
    come from the app layout's theme, which a logged-out customer never loads).

    Dark mode follows the viewer's OS preference through data-bs-theme, which is
    what .apxp keys its dark ramp on.
--}}
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ tenant_cache('company_name', '') ?: config('app.name') }} — Book your appointment</title>

    {{-- Same vendored files the app layout uses. Bootstrap lives under
         assets/css/, NOT assets/vendors/bootstrap/ — the latter has no CSS. --}}
    <link rel="icon" type="image/png" href="{{ https_asset('favicon.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ https_asset('apple-touch-icon.png') }}">

    <link rel="stylesheet" href="{{ https_asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ https_asset('assets/vendors/font-awesome/font-awesome.min.css') }}">

    <style>
        :root{
            --bs-primary:#1D4ED8;
            --bs-primary-rgb:29,78,216;
            --bs-success:#0F9D58; --bs-success-rgb:15,157,88;
            --bs-danger:#DC2626;  --bs-danger-rgb:220,38,38;
            --bs-warning:#B45309; --bs-warning-rgb:180,83,9;

            /* Estate ground — warm ivory, not the blue-grey the admin uses. */
            --pub-bg:#f3efe9;
            --pub-ink:#191512;
            --pub-ink-2:#5b5248;
            --pub-ink-3:#94897c;
            --pub-line:#e7dfd4;
            --pub-serif:Georgia,'Times New Roman','Noto Serif',serif;
        }
        [data-bs-theme="dark"]{
            --pub-bg:#131110;
            --pub-ink:#f2ece4;
            --pub-ink-2:#c3b8ab;
            --pub-ink-3:#8c8175;
            --pub-line:#332d27;
        }

        *{ box-sizing:border-box; }
        html, body{ min-height:100%; }
        body{
            margin:0;
            color:var(--pub-ink);
            background:var(--pub-bg);
            font-family:'Segoe UI',system-ui,-apple-system,Arial,sans-serif;
            -webkit-font-smoothing:antialiased;
        }

        .pub-page{ min-height:100vh; display:flex; flex-direction:column; }
        .pub-main{ flex:1 0 auto; }

        /* ── closing fine print ───────────────────────────────────── */
        .pub-foot{
            width:100%; max-width:1080px; margin:0 auto;
            padding:30px clamp(18px,4vw,40px) 48px;
            display:flex; gap:20px; flex-wrap:wrap; align-items:center;
            font-size:11.5px; color:var(--pub-ink-3); letter-spacing:.01em;
        }
        .pub-foot .rule{
            width:100%; height:1px; background:var(--pub-line); margin-bottom:20px;
        }
        .pub-foot a{ color:var(--pub-ink-2); text-decoration:none; }
        .pub-foot a:hover{ color:var(--bs-primary); }
        .pub-foot .sep{ margin-inline-start:auto; }
        @media (max-width:575.98px){
            .pub-foot .sep{ margin-inline-start:0; }
        }
    </style>
</head>
<body>
    @php
        // Company Profile is the source of truth; fall back to the app's own
        // branding, which is also where the logo fallback comes from.
        $companyName = tenant_cache('company_name', '') ?: config('app.name');
        $companyPhone = tenant_cache('mobile', '');
        $companyEmail = tenant_cache('email', '');
    @endphp

    <div class="pub-page">
        <main class="pub-main">
            @yield('content')
        </main>

        <footer class="pub-foot">
            <span class="rule"></span>
            <span>&copy; {{ date('Y') }} {{ $companyName }}</span>
            @if (filled($companyPhone))
                <a href="tel:{{ $companyPhone }}"><i class="fa fa-phone"></i> {{ $companyPhone }}</a>
            @endif
            @if (filled($companyEmail))
                <a href="mailto:{{ $companyEmail }}"><i class="fa fa-envelope-o"></i> {{ $companyEmail }}</a>
            @endif
            <span class="sep"><i class="fa fa-lock"></i> This link is personal to you — please don't share it.</span>
        </footer>
    </div>

    <script>
        // Honour the viewer's OS theme. .apxp keys its dark ramp on
        // data-bs-theme, so stamping the attribute is all that is needed.
        (function () {
            var mq = window.matchMedia('(prefers-color-scheme: dark)');
            var apply = function (dark) {
                document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
            };
            apply(mq.matches);
            mq.addEventListener ? mq.addEventListener('change', function (e) { apply(e.matches); })
                                : mq.addListener(function (e) { apply(e.matches); });
        })();
    </script>

    @stack('scripts')
</body>
</html>
