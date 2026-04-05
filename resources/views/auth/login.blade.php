<x-guest-layout>
    <div class="fade-up">
        <div class="text-center mb-4">
            <div class="avatar-icon mb-3 fade-up fade-up-delay-1">
                <i class="demo-pli-gear"></i>
            </div>
            <h2 class="welcome-title mb-2 fade-up fade-up-delay-2">Welcome Back</h2>
            <p class="welcome-subtitle fade-up fade-up-delay-2">Sign in to your workspace</p>
        </div>

        <div class="card login-card border-0 fade-up fade-up-delay-3">
            <div class="card-body p-4 p-lg-5">
                <x-auth-session-status class="mb-3" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="responsive-form">
                    @csrf

                    {{-- Email Field --}}
                    <div class="mb-4">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>Email Address</span>
                            <span class="badge-required">Required</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="demo-pli-mail"></i>
                            </span>
                            <x-text-input
                                id="email"
                                class="form-control"
                                type="email"
                                name="email"
                                :value="old('email', env('DEFAULT_USERNAME'))"
                                placeholder="name@example.com"
                                required
                                autofocus
                                autocomplete="username"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    {{-- Password Field --}}
                    <div class="mb-4">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>Password</span>
                            <span class="badge-required">Required</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="demo-pli-lock-2"></i>
                            </span>
                            <x-text-input
                                id="password"
                                class="form-control"
                                type="password"
                                name="password"
                                value="{{ env('DEFAULT_PASSWORD') }}"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                            />
                            <button class="password-toggle-btn" type="button" id="togglePassword" aria-label="Toggle password visibility">
                                <svg class="eye-icon eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-icon eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    {{-- Remember Me --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                            <label class="form-check-label" for="remember_me">Remember me</label>
                        </div>
                    </div>

                    <div class="auth-divider">
                        <span>secure login</span>
                    </div>

                    {{-- Submit Button --}}
                    <div class="d-grid">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="demo-pli-unlock fs-5 me-2"></i>
                            <span>Sign In</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-4 auth-footer fade-up fade-up-delay-3">
            <p class="mb-0">
                <i class="demo-pli-security-check me-1"></i>
                Secured by {{ config('app.name', 'Laravel') }}
            </p>
        </div>

        {{-- Session auto-refresh banner --}}
        <div id="sessionBanner" class="session-refresh-banner" style="display:none;">
            <div class="session-refresh-inner">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <span>Session expiring — refreshing in <strong id="loginCountdown">5</strong>s</span>
            </div>
        </div>
    </div>

    <style>
        .session-refresh-banner {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            z-index: 200;
            opacity: 0;
            animation: bannerSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .session-refresh-inner {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 12px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            box-shadow: 0 4px 20px var(--card-shadow);
            white-space: nowrap;
        }

        .session-refresh-inner svg {
            color: #f59e0b;
            flex-shrink: 0;
        }

        .session-refresh-inner strong {
            color: #f59e0b;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
        }

        @keyframes bannerSlideUp {
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
    </style>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.classList.toggle('active', isPassword);
        });

        // Auto-refresh page before CSRF token expires (session lifetime from Laravel config)
        (function () {
            var SESSION_LIFETIME_MS = {{ (int) config('session.lifetime', 120) }} * 60 * 1000;
            var WARN_BEFORE_MS = 30 * 1000; // show banner 30s before expiry
            var refreshTimeout = SESSION_LIFETIME_MS - WARN_BEFORE_MS;

            // Also silently refresh CSRF token every 15 min as a safety net
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

            // Show banner and countdown before session expires
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
                    if (seconds <= 0) {
                        clearInterval(tick);
                        window.location.reload();
                    }
                }, 1000);
            }, refreshTimeout);
        })();
    </script>
</x-guest-layout>
