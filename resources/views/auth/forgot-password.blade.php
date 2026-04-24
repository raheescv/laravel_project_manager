<x-guest-layout>
    {{-- ======================================================
         LUMINOUS GATEWAY — Password recovery
         ====================================================== --}}
    <x-luminous-card
        icon="key"
        title="Reset your password"
        subtitle="Enter the email tied to your <strong>{{ config('app.name', 'Size Run') }}</strong> account &mdash; we'll send a secure reset link."
    >
        <x-slot name="trust">
            <span class="lux-trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                Secure
            </span>
            <span class="lux-trust-sep"></span>
            <span class="lux-trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Encrypted
            </span>
            <span class="lux-trust-sep"></span>
            <span class="lux-trust-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                1-hour link
            </span>
        </x-slot>

        <form method="POST" action="{{ route('password.email') }}" id="forgotForm" autocomplete="on" novalidate>
            @csrf

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

            {{-- Helper link row --}}
            <div class="lux-row" style="justify-content: flex-start; margin-top: 0.25rem;">
                <a class="lux-aux-link" href="{{ route('login') }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    {{ __('Back to sign in') }}
                </a>
            </div>

            {{-- Submit --}}
            <button type="submit" class="lux-submit" id="submitBtn">
                <span class="lux-submit-bg"></span>
                <span class="lux-submit-ripple" id="submitRipple"></span>
                <span class="lux-submit-content">
                    <span class="lux-submit-label">{{ __('Email Password Reset Link') }}</span>
                    <svg class="lux-submit-arrow" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </span>
                <span class="lux-submit-spinner" aria-hidden="true"></span>
            </button>
        </form>
    </x-luminous-card>
</x-guest-layout>
