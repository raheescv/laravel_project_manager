@props([
    'code' => '404',
    'title' => 'Error',
    'subtitle' => 'Something went wrong.',
    'infoText' => 'Please try again or contact support.',
    'icon' => 'alert',
    'color' => '#6366f1',
    'colorEnd' => null,
    'primaryAction' => 'back',
    'countdown' => false,
    'details' => null,
])

@php
    $colorEnd = $colorEnd ?? $color;
    $colorRgb = implode(', ', array_map('hexdec', str_split(ltrim($color, '#'), 2)));
    $digits = str_split($code);
@endphp

<x-guest-layout>
    <div class="fade-up">
        <div class="text-center mb-4">
            {{-- Animated Icon --}}
            <div class="error-icon-wrap mb-4 fade-up fade-up-delay-1">
                <div class="error-icon-ring" style="border-color: rgba({{ $colorRgb }}, 0.2);"></div>
                <div class="error-icon-ring error-icon-ring--2" style="border-color: rgba({{ $colorRgb }}, 0.06);"></div>
                <div class="error-icon-inner" style="background: linear-gradient(135deg, {{ $color }}, {{ $colorEnd }}); box-shadow: 0 8px 24px rgba({{ $colorRgb }}, 0.3);">
                    @if($icon === 'shield')
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    @elseif($icon === 'lock')
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            <line x1="12" y1="16" x2="12" y2="19"/>
                        </svg>
                    @elseif($icon === 'compass')
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    @endif
                </div>
            </div>

            {{-- Error Code --}}
            <div class="error-code-wrap fade-up fade-up-delay-2">
                @foreach($digits as $i => $digit)
                    @if($i === 1)
                        <span class="error-code-number error-code-number--accent" style="background: linear-gradient(135deg, {{ $color }}, {{ $colorEnd }});">{{ $digit }}</span>
                    @else
                        <span class="error-code-number">{{ $digit }}</span>
                    @endif
                @endforeach
            </div>

            {{-- Title --}}
            <h2 class="error-title mb-2 fade-up fade-up-delay-2">{{ $title }}</h2>
            <p class="error-subtitle fade-up fade-up-delay-2">{{ $subtitle }}</p>
        </div>

        {{-- Action Card --}}
        <div class="card login-card border-0 fade-up fade-up-delay-3">
            <div class="card-body p-4 p-lg-5">
                <div class="error-info-box mb-4" style="background: rgba({{ $colorRgb }}, 0.06); border-color: rgba({{ $colorRgb }}, 0.12);">
                    <div class="error-info-icon" style="color: {{ $color }};">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <p class="error-info-text">{{ $infoText }}</p>
                </div>

                @if($details && is_array($details) && array_filter($details))
                    <div class="error-details-box mb-4">
                        <div class="error-details-header">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                            <span>Denied Details</span>
                        </div>
                        <div class="error-details-body">
                            @if(!empty($details['permission']))
                                <div class="error-detail-row">
                                    <span class="error-detail-label">Permission</span>
                                    <span class="error-detail-value error-detail-value--highlight">{{ $details['permission'] }}</span>
                                </div>
                            @endif
                            @if(!empty($details['resource']))
                                <div class="error-detail-row">
                                    <span class="error-detail-label">Resource</span>
                                    <span class="error-detail-value">{{ str_replace('Controller', '', $details['resource']) }}</span>
                                </div>
                            @endif
                            @if(!empty($details['action']))
                                <div class="error-detail-row">
                                    <span class="error-detail-label">Action</span>
                                    <span class="error-detail-value">{{ $details['action'] }}</span>
                                </div>
                            @endif
                            @if(!empty($details['url']))
                                <div class="error-detail-row">
                                    <span class="error-detail-label">Path</span>
                                    <span class="error-detail-value error-detail-value--mono">/{{ $details['url'] }}</span>
                                </div>
                            @endif
                            @if(!empty($details['user_role']))
                                <div class="error-detail-row">
                                    <span class="error-detail-label">Your Role</span>
                                    <span class="error-detail-value">{{ $details['user_role'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="d-grid gap-3">
                    @if($primaryAction === 'refresh')
                        <button type="button" onclick="window.location.reload()" class="btn btn-primary btn-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2" style="position:relative;z-index:1;">
                                <polyline points="23 4 23 10 17 10"/>
                                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                            </svg>
                            <span>Refresh Page</span>
                        </button>
                    @else
                        <button type="button" onclick="window.history.back()" class="btn btn-primary btn-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2" style="position:relative;z-index:1;">
                                <line x1="19" y1="12" x2="5" y2="12"/>
                                <polyline points="12 19 5 12 12 5"/>
                            </svg>
                            <span>Go Back</span>
                        </button>
                    @endif

                    <a href="{{ route('home') }}" class="btn btn-outline-glass btn-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        <span>Return Home</span>
                    </a>
                </div>

                @if($countdown)
                    <div class="auth-divider">
                        <span>auto-refreshing</span>
                    </div>
                    <div class="text-center">
                        <p class="error-countdown-text">
                            Redirecting in <span id="countdown" class="error-countdown-num" style="color: {{ $color }};">10</span>s
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <div class="text-center mt-4 auth-footer fade-up fade-up-delay-3">
            <p class="mb-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1" style="vertical-align: -2px;">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Secured by {{ config('app.name', 'Laravel') }}
            </p>
        </div>
    </div>

    <style>
        [data-theme="light"] .error-info-box {
            background: rgba({{ $colorRgb }}, 0.04) !important;
            border-color: rgba({{ $colorRgb }}, 0.15) !important;
        }
        [data-theme="light"] .error-icon-inner {
            box-shadow: 0 4px 16px rgba({{ $colorRgb }}, 0.2) !important;
        }
    </style>

    @if($countdown)
        <script>
            (function() {
                let seconds = 10;
                const el = document.getElementById('countdown');
                const timer = setInterval(function() {
                    seconds--;
                    if (el) el.textContent = seconds;
                    if (seconds <= 0) {
                        clearInterval(timer);
                        window.location.reload();
                    }
                }, 1000);
            })();
        </script>
    @endif
</x-guest-layout>
