<x-guest-layout>
    {{-- ======================================================
         LUMINOUS GATEWAY — Set a new password
         ====================================================== --}}
    <x-luminous-card
        icon="reset"
        title="Set a new password"
        subtitle="Choose a strong new password to regain access to your <strong>{{ config('app.name', 'Size Run') }}</strong> account."
    >
        <x-slot name="trust">
            <span class="lux-trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                Secure reset
            </span>
            <span class="lux-trust-sep"></span>
            <span class="lux-trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Encrypted
            </span>
            <span class="lux-trust-sep"></span>
            <span class="lux-trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                One-time link
            </span>
        </x-slot>

        <form method="POST" action="{{ route('password.store') }}" id="resetForm" autocomplete="on" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

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
                        value="{{ old('email', $request->email) }}"
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

            {{-- New password --}}
            <div class="lux-field" data-field="password">
                <label class="lux-label" for="password">
                    <span class="lux-label-text">{{ __('New Password') }}</span>
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
                        placeholder="{{ __('Create a strong password') }}"
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
                @error('password')
                    <p class="lux-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm --}}
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
                        placeholder="{{ __('Repeat your new password') }}"
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
                    <span class="lux-submit-label">{{ __('Reset Password') }}</span>
                    <svg class="lux-submit-arrow" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </span>
                <span class="lux-submit-spinner" aria-hidden="true"></span>
            </button>

            {{-- Back to sign in --}}
            <p class="lux-aux-row">
                <a class="lux-aux-link" href="{{ route('login') }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    {{ __('Back to sign in') }}
                </a>
            </p>
        </form>
    </x-luminous-card>

    <style>
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
        /* PASSWORD REVEAL (both password fields) */
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

        /* PASSWORD MATCH INDICATOR */
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
