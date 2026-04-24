<x-guest-layout>
    {{-- ======================================================
         LUMINOUS GATEWAY — Create your account
         ====================================================== --}}
    <x-luminous-card
        icon="user"
        title="Create your account"
        subtitle="Join <strong>{{ config('app.name', 'Size Run') }}</strong> &mdash; your secure workspace for managing projects and teams."
    >
        <x-slot name="trust">
            <span class="lux-trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                Protected
            </span>
            <span class="lux-trust-sep"></span>
            <span class="lux-trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Encrypted
            </span>
            <span class="lux-trust-sep"></span>
            <span class="lux-trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                Free to start
            </span>
        </x-slot>

        <form method="POST" action="{{ route('register') }}" id="registerForm" autocomplete="on" novalidate>
            @csrf

            {{-- Full name --}}
            <div class="lux-field" data-field="name">
                <label class="lux-label" for="name">
                    <span class="lux-label-text">{{ __('Full Name') }}</span>
                    <span class="lux-required">{{ __('Required') }}</span>
                </label>
                <div class="lux-input-wrap">
                    <span class="lux-input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        placeholder="Jane Doe"
                        required
                        autofocus
                        autocomplete="name"
                        class="lux-input"
                    />
                    <span class="lux-focus-line" aria-hidden="true"></span>
                </div>
                @error('name')
                    <p class="lux-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div class="lux-field" data-field="email">
                <label class="lux-label" for="email">
                    <span class="lux-label-text">{{ __('Email Address') }}</span>
                    <span class="lux-required">{{ __('Required') }}</span>
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
                        value="{{ old('email') }}"
                        placeholder="name@example.com"
                        required
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
                    <span class="lux-label-text">{{ __('Password') }}</span>
                    <span class="lux-required">{{ __('Required') }}</span>
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
                        placeholder="Create a strong password"
                        required
                        autocomplete="new-password"
                        class="lux-input lux-input--has-toggle"
                    />
                    <button class="lux-reveal" type="button" data-toggle="password" aria-label="{{ __('Toggle password visibility') }}">
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
                <div class="lux-strength" id="pwStrength" aria-hidden="true">
                    <span class="lux-strength-bar"><span class="lux-strength-fill" id="pwStrengthFill"></span></span>
                    <span class="lux-strength-label" id="pwStrengthLabel">{{ __('Too short') }}</span>
                </div>
                @error('password')
                    <p class="lux-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="lux-field" data-field="password_confirmation">
                <label class="lux-label" for="password_confirmation">
                    <span class="lux-label-text">{{ __('Confirm Password') }}</span>
                    <span class="lux-required">{{ __('Required') }}</span>
                </label>
                <div class="lux-input-wrap">
                    <span class="lux-input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                    </span>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        placeholder="{{ __('Repeat your password') }}"
                        required
                        autocomplete="new-password"
                        class="lux-input lux-input--has-toggle"
                    />
                    <button class="lux-reveal" type="button" data-toggle="password_confirmation" aria-label="{{ __('Toggle password visibility') }}">
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
                <p class="lux-match" id="pwMatch" aria-live="polite"></p>
                @error('password_confirmation')
                    <p class="lux-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <button type="submit" class="lux-submit" id="submitBtn">
                <span class="lux-submit-bg"></span>
                <span class="lux-submit-ripple" id="submitRipple"></span>
                <span class="lux-submit-content">
                    <span class="lux-submit-label">{{ __('Create account') }}</span>
                    <svg class="lux-submit-arrow" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </span>
                <span class="lux-submit-spinner" aria-hidden="true"></span>
            </button>

            {{-- Already registered --}}
            <p class="lux-aux-row">
                <span class="lux-aux-text">{{ __('Already have an account?') }}</span>
                <a class="lux-aux-link lux-aux-link--forward" href="{{ route('login') }}">
                    {{ __('Sign in') }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
            </p>
        </form>
    </x-luminous-card>

    {{-- Register-only interactions: password reveal + strength meter --}}
    <style>
        .lux-strength {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-top: 0.45rem;
            font-size: 0.75rem;
        }
        .lux-strength-bar {
            flex: 1;
            height: 4px;
            border-radius: 999px;
            background: var(--glass-border);
            overflow: hidden;
            position: relative;
        }
        .lux-strength-fill {
            display: block;
            height: 100%;
            width: 0%;
            border-radius: 999px;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981);
            transition: width 0.35s cubic-bezier(0.16, 1, 0.3, 1), background 0.35s ease;
        }
        .lux-strength-label {
            color: var(--text-muted);
            min-width: 72px;
            text-align: right;
            transition: color 0.25s ease;
        }
        .lux-strength[data-level="weak"] .lux-strength-label { color: #ef4444; }
        .lux-strength[data-level="fair"] .lux-strength-label { color: #f59e0b; }
        .lux-strength[data-level="good"] .lux-strength-label { color: #3b82f6; }
        .lux-strength[data-level="strong"] .lux-strength-label { color: #10b981; }

        .lux-match {
            margin: 0.45rem 0 0;
            font-size: 0.78rem;
            color: var(--text-muted);
            min-height: 1.1em;
            transition: color 0.2s ease;
        }
        .lux-match[data-state="match"] { color: #10b981; }
        .lux-match[data-state="mismatch"] { color: #ef4444; }
    </style>

    <script>
        /* ============================================================
           PASSWORD REVEAL (both password fields)
           ============================================================ */
        (function () {
            document.querySelectorAll('.lux-reveal[data-toggle]').forEach(function (btn) {
                var targetId = btn.getAttribute('data-toggle');
                var input = document.getElementById(targetId);
                if (!input) return;
                btn.addEventListener('click', function () {
                    var isPw = input.type === 'password';
                    input.type = isPw ? 'text' : 'password';
                    btn.classList.toggle('active', isPw);
                });
            });
        })();

        /* ============================================================
           PASSWORD STRENGTH METER
           ============================================================ */
        (function () {
            var pw = document.getElementById('password');
            var wrap = document.getElementById('pwStrength');
            var fill = document.getElementById('pwStrengthFill');
            var label = document.getElementById('pwStrengthLabel');
            if (!pw || !wrap || !fill || !label) return;

            function score(v) {
                if (!v) return 0;
                var s = 0;
                if (v.length >= 8) s++;
                if (v.length >= 12) s++;
                if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
                if (/[0-9]/.test(v)) s++;
                if (/[^A-Za-z0-9]/.test(v)) s++;
                return Math.min(s, 4);
            }

            var LABELS = ['{{ __('Too short') }}', '{{ __('Weak') }}', '{{ __('Fair') }}', '{{ __('Good') }}', '{{ __('Strong') }}'];
            var LEVELS = ['empty', 'weak', 'weak', 'fair', 'good', 'strong'];
            var WIDTHS = ['0%', '22%', '45%', '68%', '88%', '100%'];

            pw.addEventListener('input', function () {
                var v = pw.value;
                var idx = v.length === 0 ? 0 : Math.max(1, score(v));
                var l = v.length < 6 ? 0 : idx;
                fill.style.width = WIDTHS[l];
                label.textContent = LABELS[l];
                wrap.setAttribute('data-level', LEVELS[l]);

                var confirmEl = document.getElementById('password_confirmation');
                if (confirmEl && confirmEl.value) confirmEl.dispatchEvent(new Event('input'));
            });
        })();

        /* ============================================================
           PASSWORD MATCH INDICATOR
           ============================================================ */
        (function () {
            var pw = document.getElementById('password');
            var cpw = document.getElementById('password_confirmation');
            var out = document.getElementById('pwMatch');
            if (!pw || !cpw || !out) return;

            function update() {
                if (!cpw.value) { out.textContent = ''; out.removeAttribute('data-state'); return; }
                if (cpw.value === pw.value) {
                    out.textContent = '{{ __('Passwords match.') }}';
                    out.setAttribute('data-state', 'match');
                } else {
                    out.textContent = '{{ __('Passwords do not match yet.') }}';
                    out.setAttribute('data-state', 'mismatch');
                }
            }
            cpw.addEventListener('input', update);
            pw.addEventListener('input', update);
        })();
    </script>
</x-guest-layout>
