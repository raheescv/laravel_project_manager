@php
    $label = $label ?? ucwords(str_replace('_', ' ', $element));
@endphp

<div class="element-config">
    <div class="row g-3">
        <!-- Font Size -->
        <div class="col-md-6">
            <label class="form-label">Font Size</label>
            <div class="input-group">
                <input type="number" wire:model="barcode.{{ $element }}.font_size" class="form-control" min="8" max="72" required>
                <span class="input-group-text">px</span>
            </div>
        </div>

        <!-- Alignment -->
        <div class="col-md-6">
            <label class="form-label">Alignment</label>
            <select wire:model="barcode.{{ $element }}.align" class="form-select">
                <option value="left">Left</option>
                <option value="center">Center</option>
                <option value="right">Right</option>
            </select>
        </div>

        @if (isset($withLetterCount) && $withLetterCount)
            <!-- Letter Count -->
            <div class="col-md-12">
                <label class="form-label">Maximum Characters</label>
                <input type="number" wire:model="barcode.{{ $element }}.no_of_letters" class="form-control" min="1" max="100" required>
            </div>
        @endif

        <!-- Margins -->
        <div class="col-12">
            <label class="form-label d-flex align-items-center">
                Margins
                <i class="fa fa-info-circle ms-2" data-bs-toggle="tooltip" data-bs-placement="right" title="Set the space around the element"></i>
            </label>
            <div class="row g-2">
                <div class="col-3">
                    <div class="input-group input-group-sm">
                        <input type="number" wire:model="barcode.{{ $element }}.top" class="form-control" placeholder="Top">
                        <span class="input-group-text">px</span>
                    </div>
                </div>
                <div class="col-3">
                    <div class="input-group input-group-sm">
                        <input type="number" wire:model="barcode.{{ $element }}.right" class="form-control" placeholder="Right">
                        <span class="input-group-text">px</span>
                    </div>
                </div>
                <div class="col-3">
                    <div class="input-group input-group-sm">
                        <input type="number" wire:model="barcode.{{ $element }}.bottom" class="form-control" placeholder="Bottom">
                        <span class="input-group-text">px</span>
                    </div>
                </div>
                <div class="col-3">
                    <div class="input-group input-group-sm">
                        <input type="number" wire:model="barcode.{{ $element }}.left" class="form-control" placeholder="Left">
                        <span class="input-group-text">px</span>
                    </div>
                </div>
            </div>
            <div class="form-text text-muted small mt-1">
                Top, Right, Bottom, Left margins in pixels
            </div>
        </div>
    </div>
</div>
