<x-guest-layout>
    {{-- ======================================================
         LUMINOUS GATEWAY — Sign in
         Global auth environment is applied by layouts/guest.
         ====================================================== --}}
    <x-luminous-card
        icon="lock"
        title="Welcome back"
        subtitle="Sign in to your <strong>{{ config('app.name', 'Size Run') }}</strong> workspace."
    >
        <form method="POST" action="{{ route('login') }}" id="loginForm" autocomplete="on" novalidate>
            @csrf

            {{-- Email --}}
            <div class="lux-field" data-field="email">
                <label class="lux-label" for="email">
                    <span class="lux-label-text">Email Address</span>
                    <span class="lux-required">Required</span>
                </label>
                <div class="lux-input-wrap">
                    <span class="lux-input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </span>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', env('DEFAULT_USERNAME')) }}"
                        placeholder="name@example.com"
                        required
                        autofocus
                        autocomplete="username"
                        class="lux-input"
                    />
                    <span class="lux-focus-line" aria-hidden="true"></span>
                </div>
                @error('email')
                    <p class="lux-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="lux-field" data-field="password">
                <label class="lux-label" for="password">
                    <span class="lux-label-text">Password</span>
                    <span class="lux-required">Required</span>
                </label>
                <div class="lux-input-wrap">
                    <span class="lux-input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        value="{{ env('DEFAULT_PASSWORD') }}"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                        class="lux-input lux-input--has-toggle"
                    />
                    <button class="lux-reveal" type="button" id="togglePassword" aria-label="Toggle password visibility">
                        <svg class="eye eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg class="eye eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                    <span class="lux-focus-line" aria-hidden="true"></span>
                </div>
                @error('password')
                    <p class="lux-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Options row --}}
            <div class="lux-row">
                <label class="lux-check">
                    <input type="checkbox" name="remember" id="remember_me">
                    <span class="lux-check-box">
                        <svg viewBox="0 0 16 16" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 8.5 6.5 12 13 4.5"/>
                        </svg>
                    </span>
                    <span class="lux-check-label">Keep me signed in</span>
                </label>
                @if (Route::has('password.request') && false)
                    <a class="lux-link" href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>

            {{-- Submit (magnetic + shimmer) --}}
            <button type="submit" class="lux-submit" id="submitBtn">
                <span class="lux-submit-bg"></span>
                <span class="lux-submit-ripple" id="submitRipple"></span>
                <span class="lux-submit-content">
                    <span class="lux-submit-label">Sign in securely</span>
                    <svg class="lux-submit-arrow" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </span>
                <span class="lux-submit-spinner" aria-hidden="true"></span>
            </button>

            {{-- Register link --}}
            @if (Route::has('register') && false)
                <p class="lux-aux-row">
                    <span class="lux-aux-text">New to {{ config('app.name', 'Size Run') }}?</span>
                    <a class="lux-aux-link lux-aux-link--forward" href="{{ route('register') }}">
                        Create an account
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </p>
            @endif
        </form>
    </x-luminous-card>

    {{-- Session auto-refresh banner --}}
    <div id="sessionBanner" class="session-refresh-banner" style="display:none;" hidden>
        <div class="session-refresh-inner">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            <span>Session expiring &mdash; refreshing in <strong id="loginCountdown">5</strong>s</span>
        </div>
    </div>

    {{-- Login-only interactions --}}
    <script>
        /* ============================================================
           PASSWORD REVEAL
           ============================================================ */
        (function () {
            var btn = document.getElementById('togglePassword');
            var input = document.getElementById('password');
            if (!btn || !input) return;
            btn.addEventListener('click', function () {
                var isPw = input.type === 'password';
                input.type = isPw ? 'text' : 'password';
                btn.classList.toggle('active', isPw);
            });
        })();

        /* ============================================================
           Session auto-refresh
           ============================================================ */
        (function () {
            var SESSION_LIFETIME_MS = {{ (int) config('session.lifetime', 120) }} * 60 * 1000;
            var WARN_BEFORE_MS = 30 * 1000;
            var refreshTimeout = SESSION_LIFETIME_MS - WARN_BEFORE_MS;

            setInterval(function () {
                fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' })
                    .then(function () {
                        var cookie = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
                        if (cookie) {
                            var token = decodeURIComponent(cookie[1]);
                            var tokenInput = document.querySelector('input[name="_token"]');
                            if (tokenInput) tokenInput.value = token;
                        }
                    })
                    .catch(function () {});
            }, 15 * 60 * 1000);

            setTimeout(function () {
                var banner = document.getElementById('sessionBanner');
                var countdownEl = document.getElementById('loginCountdown');
                if (!banner || !countdownEl) return;
                banner.style.display = 'block';
                var seconds = Math.floor(WARN_BEFORE_MS / 1000);
                countdownEl.textContent = seconds;
                var tick = setInterval(function () {
                    seconds--;
                    countdownEl.textContent = seconds;
                    if (seconds <= 0) { clearInterval(tick); window.location.reload(); }
                }, 1000);
            }, refreshTimeout);
        })();
    </script>
</x-guest-layout>
