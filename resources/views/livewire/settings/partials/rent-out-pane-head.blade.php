<div class="d-flex align-items-start gap-3 bg-primary-subtle border border-primary-subtle rounded-4 p-3 mb-4">
    <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm fs-5 p-2 lh-1">
        <i class="fa fa-fw {{ $section['icon'] }}"></i>
    </span>
    <div class="flex-grow-1">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h6 class="fw-bold text-primary-emphasis mb-0">{{ $section['title'] }}</h6>
            @if ($section['hasError'])
                <span class="badge rounded-pill text-bg-danger"><i class="fa fa-exclamation-circle me-1"></i>Needs attention</span>
            @else
                <span class="badge rounded-pill {{ $section['ok'] ? 'text-bg-success' : 'text-bg-warning' }}">{{ $section['status'] }}</span>
            @endif
        </div>
        <p class="small text-primary-emphasis opacity-75 mb-0 mt-1">{{ $section['sub'] }}</p>
    </div>
</div>
