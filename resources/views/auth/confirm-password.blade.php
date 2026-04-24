<x-guest-layout>
    {{-- ======================================================
         LUMINOUS GATEWAY — Confirm password (secure area)
         ====================================================== --}}
    <x-luminous-card
        icon="shield"
        title="Confirm your password"
        subtitle="You're entering a <strong>secure area</strong> of {{ config('app.name', 'Size Run') }}. Please confirm your password to continue."
    >
        <x-slot name="trust">
            <span class="lux-trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                Verified session
            </span>
            <span class="lux-trust-sep"></span>
            <span class="lux-trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Encrypted
            </span>
        </x-slot>

        <form method="POST" action="{{ route('password.confirm') }}" id="confirmForm" autocomplete="on" novalidate>
            @csrf

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
                        placeholder="{{ __('Enter your current password') }}"
                        required
                        autofocus
                        autocomplete="current-password"
                        class="lux-input lux-input--has-toggle"
                    />
                    <button class="lux-reveal" type="button" id="togglePassword" aria-label="{{ __('Toggle password visibility') }}">
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

            {{-- Submit --}}
            <button type="submit" class="lux-submit" id="submitBtn">
                <span class="lux-submit-bg"></span>
                <span class="lux-submit-ripple" id="submitRipple"></span>
                <span class="lux-submit-content">
                    <span class="lux-submit-label">{{ __('Confirm and continue') }}</span>
                    <svg class="lux-submit-arrow" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </span>
                <span class="lux-submit-spinner" aria-hidden="true"></span>
            </button>
        </form>
    </x-luminous-card>

    <script>
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
    </script>
</x-guest-layout>
