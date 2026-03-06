@props(['item'])

@php
    $modelName = $item->categoryModel?->name ?? ($item->category_model?->name ?? ($item->tailoring_category_model_name ?? 'Standard'));

    $activeMeasurements = $item->category?->activeMeasurements ?? collect();

    $getFieldsBySection = function ($sectionId) use ($activeMeasurements) {
        return $activeMeasurements->where('section', $sectionId)->sortBy('sort_order');
    };

    $sectionGroups = [
        'dimensions' => 'basic_body',
        'components' => 'collar_cuff',
        'styles' => 'specifications',
    ];

    $getValue = function ($key) use ($item) {
        $val = $item->$key ?? null;
        if ($val === '' || $val === null) {
            return null;
        }
        return $val;
    };
@endphp

<div>
    <!-- Header Area -->
    <div class="d-flex align-items-center justify-content-between mb-4 px-1">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex flex-column align-items-center bg-light border rounded-3 px-3 py-1">
                <span class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.05em;">ITEM</span>
                <span class="h6 fw-bolder mb-0">#{{ $item->item_no }}</span>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0">{{ $item->product_name ?? 'Generic Item' }}</h5>
                <div class="small text-secondary fw-medium">
                    {{ $item->category?->name ?? 'Item' }} <span class="mx-1 opacity-25">•</span> {{ $modelName }}
                </div>
            </div>
        </div>
    </div>

    <!-- Grid Layout -->
    <div class="row g-4">
        <!-- Dimensions Column -->
        <div class="col-xl-4 col-md-6">
            <div>
                <div class="text-uppercase fw-bold text-muted small mb-2 ps-1">
                    <i class="fa fa-ruler-combined me-2"></i>
                    DIMENSIONS
                </div>
                <div class="card shadow-sm rounded-3 overflow-hidden border">
                    @foreach ($getFieldsBySection($sectionGroups['dimensions']) as $m)
                        <div class="row g-0 border-bottom @if ($loop->last) border-bottom-0 @endif">
                            <div class="col-7 bg-light p-2 fw-semibold text-muted small border-end d-flex align-items-center">
                                {{ $m->label }}
                            </div>
                            <div class="col-5 p-2 fw-bold text-dark small d-flex align-items-center {{ !$getValue($m->field_key) ? 'text-muted opacity-50' : '' }}">
                                {{ $getValue($m->field_key) ?? '-' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Components Column -->
        <div class="col-xl-4 col-md-6">
            <div>
                <div class="text-uppercase fw-bold text-muted small mb-2 ps-1">
                    <i class="fa fa-puzzle-piece me-2"></i>
                    COMPONENTS
                </div>
                <div class="card shadow-sm rounded-3 overflow-hidden border">
                    @foreach ($getFieldsBySection($sectionGroups['components']) as $m)
                        <div class="row g-0 border-bottom @if ($loop->last) border-bottom-0 @endif">
                            <div class="col-7 bg-light p-2 fw-semibold text-muted small border-end d-flex align-items-center">
                                {{ $m->label }}
                            </div>
                            <div class="col-5 p-2 fw-bold text-dark small d-flex align-items-center {{ !$getValue($m->field_key) ? 'text-muted opacity-50' : '' }}">
                                {{ $getValue($m->field_key) ?? '-' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Styles Column -->
        <div class="col-xl-4 col-12">
            <div>
                <div class="text-uppercase fw-bold text-muted small mb-2 ps-1">
                    <i class="fa fa-cut me-2"></i>
                    STYLES & MODELS
                </div>
                <div class="card shadow-sm rounded-3 overflow-hidden border">
                    <div class="row g-0">
                        @php
                            $styleFields = $getFieldsBySection($sectionGroups['styles']);
                            $halfCount = ceil($styleFields->count() / 2);
                        @endphp
                        <div class="col-sm-6 border-end">
                            @foreach ($styleFields->take($halfCount) as $m)
                                <div class="row g-0 border-bottom @if ($loop->last && $styleFields->count() <= $halfCount) border-bottom-0 @endif">
                                    <div class="col-7 bg-light p-2 fw-semibold text-muted small border-end d-flex align-items-center">
                                        {{ $m->label }}
                                    </div>
                                    <div class="col-5 p-2 fw-bold text-dark small d-flex align-items-center {{ !$getValue($m->field_key) ? 'text-muted opacity-50' : '' }}">
                                        {{ $getValue($m->field_key) ?? '-' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="col-sm-6">
                            @foreach ($styleFields->skip($halfCount) as $m)
                                <div class="row g-0 border-bottom @if ($loop->last) border-bottom-0 @endif">
                                    <div class="col-7 bg-light p-2 fw-semibold text-muted small border-end d-flex align-items-center">
                                        {{ $m->label }}
                                    </div>
                                    <div class="col-5 p-2 fw-bold text-dark small d-flex align-items-center {{ !$getValue($m->field_key) ? 'text-muted opacity-50' : '' }}">
                                        {{ $getValue($m->field_key) ?? '-' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Section -->
    @if ($item->tailoring_notes)
        <div class="mt-4 p-3 bg-warning bg-opacity-10 border border-warning rounded-3 border-opacity-25" style="border-style: dashed !important;">
            <div class="small fw-bold text-warning-emphasis text-uppercase mb-1" style="letter-spacing: 0.05em;">
                <i class="fa fa-info-circle me-1"></i>
                SPECIAL INSTRUCTIONS
            </div>
            <div class="text-dark-emphasis fw-medium small">
                {{ $item->tailoring_notes }}
            </div>
        </div>
    @endif
</div>
