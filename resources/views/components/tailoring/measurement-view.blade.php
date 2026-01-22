@props(['item'])

@php
    $modelName = $item->categoryModel?->name ?? ($item->category_model?->name ?? ($item->tailoring_category_model_name ?? 'Standard'));

    $groups = [
        'dimensions' => ['length', 'shoulder', 'sleeve', 'chest', 'stomach', 'sl_chest', 'sl_so', 'neck', 'bottom'],
        'components' => ['mar_size', 'cuff_size', 'collar_size', 'regal_size', 'knee_loose', 'fp_size', 'side_pt_size', 'button_no', 'neck_d_button'],
        'styles' => ['mar_model', 'cuff', 'cuff_cloth', 'cuff_model', 'collar', 'collar_cloth', 'collar_model', 'fp_down', 'fp_model', 'pen', 'side_pt_model', 'stitching', 'button', 'mobile_pocket'],
    ];

    $labels = [
        'sl_chest' => 'Sleeve Chest',
        'sl_so' => 'Sleeve Shoulder',
        'mar_size' => 'Mar Size',
        'mar_model' => 'Mar Model',
        'cuff_cloth' => 'Cuff Cloth',
        'cuff_model' => 'Cuff Model',
        'collar_cloth' => 'Collar Cloth',
        'collar_model' => 'Collar Model',
        'fp_down' => 'FP Down',
        'fp_model' => 'FP Model',
        'fp_size' => 'FP Size',
        'side_pt_size' => 'Side Pkt Size',
        'side_pt_model' => 'Side Pkt Model',
        'neck_d_button' => 'Neck D Button',
        'button_no' => 'Btn No',
        'mobile_pocket' => 'Mob Pkt',
    ];

    $formatLabel = function ($key) use ($labels) {
        return $labels[$key] ?? str_replace('_', ' ', ucwords($key, '_'));
    };

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
                    @foreach ($groups['dimensions'] as $key)
                        <div class="row g-0 border-bottom @if ($loop->last) border-bottom-0 @endif">
                            <div class="col-7 bg-light p-2 fw-semibold text-muted small border-end d-flex align-items-center">
                                {{ $formatLabel($key) }}
                            </div>
                            <div class="col-5 p-2 fw-bold text-dark small d-flex align-items-center {{ !$getValue($key) ? 'text-muted opacity-50' : '' }}">
                                {{ $getValue($key) ?? '-' }}
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
                    @foreach ($groups['components'] as $key)
                        <div class="row g-0 border-bottom @if ($loop->last) border-bottom-0 @endif">
                            <div class="col-7 bg-light p-2 fw-semibold text-muted small border-end d-flex align-items-center">
                                {{ $formatLabel($key) }}
                            </div>
                            <div class="col-5 p-2 fw-bold text-dark small d-flex align-items-center {{ !$getValue($key) ? 'text-muted opacity-50' : '' }}">
                                {{ $getValue($key) ?? '-' }}
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
                        <div class="col-sm-6 border-end">
                            @foreach (array_slice($groups['styles'], 0, 7) as $key)
                                <div class="row g-0 border-bottom @if ($loop->last && count($groups['styles']) <= 7) border-bottom-0 @endif">
                                    <div class="col-7 bg-light p-2 fw-semibold text-muted small border-end d-flex align-items-center">
                                        {{ $formatLabel($key) }}
                                    </div>
                                    <div class="col-5 p-2 fw-bold text-dark small d-flex align-items-center {{ !$getValue($key) ? 'text-muted opacity-50' : '' }}">
                                        {{ $getValue($key) ?? '-' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="col-sm-6">
                            @foreach (array_slice($groups['styles'], 7) as $key)
                                <div class="row g-0 border-bottom @if ($loop->last) border-bottom-0 @endif">
                                    <div class="col-7 bg-light p-2 fw-semibold text-muted small border-end d-flex align-items-center">
                                        {{ $formatLabel($key) }}
                                    </div>
                                    <div class="col-5 p-2 fw-bold text-dark small d-flex align-items-center {{ !$getValue($key) ? 'text-muted opacity-50' : '' }}">
                                        {{ $getValue($key) ?? '-' }}
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
