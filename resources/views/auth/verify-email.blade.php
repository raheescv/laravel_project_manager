<x-guest-layout>
    {{-- ======================================================
         LUMINOUS GATEWAY — Verify email
         ====================================================== --}}
    <x-luminous-card
        icon="mail"
        title="Verify your email"
        subtitle="Thanks for signing up for <strong>{{ config('app.name', 'Size Run') }}</strong>. Please verify the email we just sent to activate your account."
        :showTrust="false"
    >
        <div class="lux-notice">
            {{ __("Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.") }}
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="lux-notice lux-notice--success">
                <strong>{{ __('Link sent.') }}</strong>
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </div>
        @endif

        <div class="lux-actions">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="lux-submit" id="submitBtn">
                    <span class="lux-submit-bg"></span>
                    <span class="lux-submit-ripple" id="submitRipple"></span>
                    <span class="lux-submit-content">
                        <span class="lux-submit-label">{{ __('Resend verification email') }}</span>
                        <svg class="lux-submit-arrow" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </span>
                    <span class="lux-submit-spinner" aria-hidden="true"></span>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="lux-submit lux-submit--ghost">
                    <span class="lux-submit-bg"></span>
                    <span class="lux-submit-content">
                        <span class="lux-submit-label">{{ __('Log out') }}</span>
                    </span>
                </button>
            </form>
        </div>
    </x-luminous-card>
</x-guest-layout>
