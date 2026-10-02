@php
    $inputId = 'roc-' . $model;
    $hasPreview = $upload && method_exists($upload, 'isPreviewable') && $upload->isPreviewable();
    $isSet = $upload || $slot['existing'];
    $uploadShape = $isSet ? 'rounded-start-pill' : 'rounded-pill';
@endphp
<div class="border rounded-4 bg-body h-100 d-flex flex-column @error($model) border-danger @enderror">
    <label class="ratio ratio-21x9 rounded-top-4 border-bottom mb-0 {{ $isSet ? 'bg-white' : 'bg-primary-subtle' }}" role="button" for="{{ $inputId }}">
        <span class="d-flex flex-column align-items-center justify-content-center gap-2 p-3">
            @if ($hasPreview)
                <img src="{{ $upload->temporaryUrl() }}" class="mw-100 mh-100 object-fit-contain" alt="{{ $slot['label'] }} (new)">
            @elseif ($slot['existing'])
                <img src="{{ asset('storage/' . $slot['existing']) }}" class="mw-100 mh-100 object-fit-contain" alt="{{ $slot['label'] }}" loading="lazy">
            @else
                <span class="d-inline-flex align-items-center justify-content-center bg-white text-primary rounded-circle shadow-sm p-3 lh-1">
                    <i class="fa fa-fw fa-cloud-upload fs-5"></i>
                </span>
                <span class="small fw-semibold text-primary-emphasis">Click to upload</span>
            @endif
            @if ($upload)
                <span class="badge rounded-pill text-bg-primary shadow-sm position-absolute top-0 start-0 m-2"><i class="fa fa-clock-o me-1"></i>New</span>
            @elseif ($slot['existing'])
                <span class="badge rounded-pill text-bg-success shadow-sm position-absolute top-0 start-0 m-2"><i class="fa fa-check me-1"></i>Set</span>
            @else
                <span class="badge rounded-pill text-bg-warning shadow-sm position-absolute top-0 start-0 m-2">Not set</span>
            @endif
            <span class="position-absolute top-0 start-0 w-100 h-100 bg-body bg-opacity-75 align-items-center justify-content-center text-primary fs-4"
                wire:loading.flex wire:target="{{ $model }}"><i class="fa fa-spinner fa-spin"></i></span>
        </span>
    </label>
    <div class="d-flex align-items-center gap-2 p-3 mt-auto">
        <div class="flex-grow-1 lh-sm overflow-hidden">
            <div class="fw-semibold small">{{ $slot['label'] }}</div>
            <div class="small text-body-secondary text-truncate mt-1">{{ $upload ? 'Applies on save' : $slot['meta'] }}</div>
        </div>
        <div class="btn-group btn-group-sm flex-shrink-0">
            <label class="btn {{ $isSet ? 'btn-outline-primary' : 'btn-primary' }} {{ $uploadShape }}" for="{{ $inputId }}" title="{{ $isSet ? 'Replace' : 'Upload' }}">
                <i class="fa fa-upload"></i>
            </label>
            @if ($upload)
                <button type="button" class="btn btn-outline-secondary rounded-end-pill" wire:click="$set('{{ $model }}', null)" title="Undo">
                    <i class="fa fa-times"></i>
                </button>
            @elseif ($slot['existing'])
                <a class="btn btn-outline-secondary rounded-end-pill" href="{{ asset('storage/' . $slot['existing']) }}" target="_blank" rel="noopener" title="View">
                    <i class="fa fa-external-link"></i>
                </a>
            @endif
        </div>
    </div>
    @error($model)
        <div class="text-danger small px-3 pb-3">{{ $message }}</div>
    @enderror
    <input type="file" id="{{ $inputId }}" class="d-none" wire:model="{{ $model }}" accept="image/*">
</div>
