<div class="search-section-wrapper">
    @push('styles')

    @endpush

    <div class="search-container">
        <div class="search-box barcode-box" wire:loading.class="loading">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fa fa-barcode"></i>
                </span>
                <input type="search" class="form-control" wire:model.live="barcode_key" placeholder="Scan Barcode" autocomplete="off">
            </div>
        </div>

        <div class="search-box" wire:loading.class="loading">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fa fa-search"></i>
                </span>
                <input type="search" class="form-control" wire:model.live="product_key" placeholder="Search Products" autocomplete="off">
            </div>
        </div>

        <button type="button" id="viewDraftedSales" class="view-draft-btn">
            <i class="fa fa-file-alt"></i>
            <span>View Draft</span>
        </button>
    </div>
</div>
