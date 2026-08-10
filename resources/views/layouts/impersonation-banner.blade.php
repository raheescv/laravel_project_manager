@php
    $impersonation = app(\App\Services\ImpersonationService::class);
@endphp
@if ($impersonation->isImpersonating())
    @php
        $impersonator = $impersonation->impersonator();
        $secondsLeft = $impersonation->secondsRemaining();
    @endphp
    <div class="impersonation-banner d-flex flex-wrap align-items-center justify-content-center gap-2 px-3 py-2 text-center">
        <i class="fa fa-user-secret"></i>
        <span>
            Viewing as <strong>{{ auth()->user()->name }}</strong>
            @if ($impersonator)
                &mdash; impersonated by <strong>{{ $impersonator->name }}</strong>
            @endif
            . Every action is recorded against this account.
        </span>
        <span class="impersonation-banner__timer badge rounded-pill" id="impersonationTimer" data-seconds-left="{{ $secondsLeft }}">
            <i class="fa fa-clock-o me-1"></i><span id="impersonationTimerText">{{ gmdate('i:s', $secondsLeft) }}</span> left
        </span>
        <a href="{{ route('users::impersonate-leave') }}" class="btn btn-sm btn-light fw-semibold text-nowrap">
            <i class="fa fa-sign-out me-1"></i>Return to my account
        </a>
    </div>
    @push('scripts')
        <script>
            (function () {
                var timer = document.getElementById('impersonationTimer');
                if (!timer) return;
                var text = document.getElementById('impersonationTimerText');
                var remaining = parseInt(timer.dataset.secondsLeft, 10) || 0;
                var tick = setInterval(function () {
                    remaining -= 1;
                    if (remaining <= 0) {
                        clearInterval(tick);
                        // The middleware ends the session server-side; reloading is
                        // what makes that happen without waiting for the next click.
                        window.location.reload();
                        return;
                    }
                    var minutes = Math.floor(remaining / 60);
                    var seconds = remaining % 60;
                    text.textContent = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                    if (remaining <= 60) timer.classList.add('is-urgent');
                }, 1000);
            })();
        </script>
    @endpush
@endif
