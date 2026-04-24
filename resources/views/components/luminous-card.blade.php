@props([
    'title' => 'Welcome',
    'subtitle' => null,
    'icon' => 'lock',
    'showFooter' => true,
    'showTrust' => true,
    'stageId' => 'gatewayStage',
    'cardId' => 'holoCard',
    'glareId' => 'holoGlare',
    'badgeId' => 'lockBadge',
])

{{-- ======================================================
     Luminous Gateway — reusable auth stage.
     Provides the shared badge / title / subtitle / holo-card
     chrome. Inner form content is rendered in the default slot.
     ====================================================== --}}
<div class="gateway-stage" id="{{ $stageId }}">

    {{-- Animated badge --}}
    <div class="lock-badge fade-up fade-up-delay-1" id="{{ $badgeId }}">
        <div class="lock-badge-rings" aria-hidden="true">
            <div class="lock-ring lock-ring--1"></div>
            <div class="lock-ring lock-ring--2"></div>
            <div class="lock-ring lock-ring--3"></div>
        </div>
        <div class="lock-badge-core">
            @isset($badge)
                {{ $badge }}
            @else
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="36" height="36" class="lock-svg" fill="none">
                    <g stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        @switch($icon)
                            @case('key')
                                {{-- Envelope / recovery letter --}}
                                <rect class="lock-body" x="6" y="13" width="36" height="22" rx="3"/>
                                <path class="lock-shackle" d="M6 15 L24 28 L42 15"/>
                                <path class="lock-dot-tail" d="M24 28 L24 34"/>
                                <circle class="lock-dot" cx="24" cy="22" r="1.6" fill="currentColor"/>
                                @break
                            @case('user')
                                {{-- New user / account creation --}}
                                <circle class="lock-dot" cx="24" cy="17" r="6"/>
                                <path class="lock-body" d="M10 42 a14 14 0 0 1 28 0"/>
                                <path class="lock-shackle" d="M33 11 h8"/>
                                <path class="lock-dot-tail" d="M37 7 v8"/>
                                @break
                            @case('mail')
                                {{-- Mail / verification --}}
                                <rect class="lock-body" x="6" y="11" width="36" height="26" rx="3"/>
                                <path class="lock-shackle" d="M6 14 L24 27 L42 14"/>
                                <path class="lock-dot-tail" d="M19 24 L9 34"/>
                                <path class="lock-dot-tail" d="M29 24 L39 34"/>
                                @break
                            @case('shield')
                                {{-- Security confirm / shield --}}
                                <path class="lock-body" d="M24 6 L40 12 V25 C40 34 33 40 24 43 C15 40 8 34 8 25 V12 Z"/>
                                <polyline class="lock-shackle" points="16 24 22 30 32 18"/>
                                @break
                            @case('reset')
                                {{-- Reset / rotate key --}}
                                <circle class="lock-dot" cx="30" cy="18" r="7"/>
                                <path class="lock-body" d="M30 25 L30 42 L25 42 L25 38 L20 38 L20 34 L16 34"/>
                                <path class="lock-shackle" d="M11 14 a9 9 0 0 1 15 -4"/>
                                <polyline class="lock-dot-tail" points="10 10 11 14 15 13"/>
                                @break
                            @default
                                {{-- Lock (default) --}}
                                <path class="lock-shackle" d="M15 22 V15 a9 9 0 0 1 18 0 V22"/>
                                <rect class="lock-body" x="10" y="22" width="28" height="20" rx="4"/>
                                <circle class="lock-dot" cx="24" cy="32" r="2.4" fill="currentColor"/>
                                <path class="lock-dot-tail" d="M24 34 v4"/>
                        @endswitch
                    </g>
                </svg>
            @endisset
        </div>
        <span class="lock-status">
            <span class="lock-status-dot"></span>
        </span>
    </div>

    {{-- Title + subtitle --}}
    <h1 class="gateway-title fade-up fade-up-delay-2">
        <span class="gateway-title-text">{{ $title }}</span>
        <span class="gateway-title-shine" aria-hidden="true"></span>
    </h1>
    @if ($subtitle)
        <p class="gateway-subtitle fade-up fade-up-delay-2">{!! $subtitle !!}</p>
    @elseif (isset($subtitleSlot))
        <p class="gateway-subtitle fade-up fade-up-delay-2">{{ $subtitleSlot }}</p>
    @endif

    {{-- ==================== HOLO CARD ==================== --}}
    <div class="holo-card fade-up fade-up-delay-3" id="{{ $cardId }}">
        <div class="holo-border" aria-hidden="true"></div>
        <div class="holo-glare" id="{{ $glareId }}" aria-hidden="true"></div>

        <div class="holo-body">
            @isset($status)
                {{ $status }}
            @else
                <x-auth-session-status class="mb-3" :status="session('status')" />
            @endisset

            {{ $slot }}

            @if ($showTrust || isset($trust))
                <div class="lux-trust" aria-hidden="true">
                    @isset($trust)
                        {{ $trust }}
                    @else
                        <span class="lux-trust-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                            SSL
                        </span>
                        <span class="lux-trust-sep"></span>
                        <span class="lux-trust-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Encrypted
                        </span>
                        <span class="lux-trust-sep"></span>
                        <span class="lux-trust-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                            Trusted
                        </span>
                    @endisset
                </div>
            @endif
        </div>
    </div>

    @if ($showFooter)
        <p class="gateway-footer fade-up fade-up-delay-3">
            @isset($footer)
                {{ $footer }}
            @else
                &copy; {{ date('Y') }} {{ config('app.name', 'Size Run') }} &middot; Protected workspace
            @endisset
        </p>
    @endif
</div>
