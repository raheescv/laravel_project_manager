@php $tone = $section['hasError'] ? 'danger' : ($section['ok'] ? 'success' : 'warning'); @endphp
<div class="rtx-hero">
    <span class="rtx-hero-ic"><i class="fa fa-fw {{ $section['icon'] }}"></i></span>
    <div class="flex-grow-1 min-w-0">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h5 class="rtx-hero-title">{{ $section['title'] }}</h5>
            <span class="rtx-pill is-{{ $tone }}">
                @if ($section['hasError'])
                    <i class="fa fa-exclamation-circle"></i>Needs attention
                @else
                    <i class="fa {{ $section['ok'] ? 'fa-check-circle' : 'fa-clock-o' }}"></i>{{ $section['status'] }}
                @endif
            </span>
        </div>
        <p class="rtx-hero-sub">{{ $section['sub'] }}</p>
    </div>
</div>
