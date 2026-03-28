<div>
    <div class="card shadow-sm">
        {{-- Card Header: Actions & Filters --}}
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                {{-- Left: Action Buttons --}}
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    <a class="btn btn-outline-secondary d-flex align-items-center shadow-sm"
                       href="{{ route('product::index') }}">
                        <i class="demo-psi-arrow-left me-2"></i>
                        Back to Products
                    </a>
                    @can('product.delete')
                        <button class="btn btn-danger btn-sm d-flex align-items-center shadow-sm"
                                title="Delete Selected Images"
                                data-bs-toggle="tooltip"
                                wire:click="deleteSelected()"
                                wire:confirm="Are you sure you want to delete the selected images? This cannot be undone.">
                            <i class="demo-pli-recycling me-md-1 fs-5"></i>
                            <span class="d-none d-md-inline">Delete Selected</span>
                        </button>
                    @endcan
                    @if(count($selected))
                        <span class="badge bg-primary fs-6">{{ count($selected) }} selected</span>
                    @endif
                </div>

                {{-- Right: Search & Per-Page --}}
                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 text-muted small fw-semibold">Show:</label>
                        </div>
                        <div class="col-auto">
                            <select wire:model.live="limit"
                                    class="form-select form-select-sm border-secondary-subtle shadow-sm">
                                <option value="24">24</option>
                                <option value="48">48</option>
                                <option value="96">96</option>
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="demo-psi-magnifi-glass"></i>
                                </span>
                                <input type="text"
                                       wire:model.live="search"
                                       placeholder="Search by product name, code, barcode..."
                                       class="form-control border-secondary-subtle shadow-sm"
                                       autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            {{-- Filter Row --}}
            <div class="row g-3">
                <div class="col-lg-3 col-md-6" wire:ignore>
                    <label for="gallery_department_id" class="form-label small fw-medium text-capitalize">
                        <i class="demo-psi-building me-1 text-muted"></i> Department
                    </label>
                    {{ html()->select('gallery_department_id', [])->value('')->class('select-department_id-list shadow-sm border-secondary-subtle')->id('gallery_department_id')->placeholder('All Departments') }}
                </div>

                <div class="col-lg-3 col-md-6" wire:ignore>
                    <label for="gallery_main_category_id" class="form-label small fw-medium text-capitalize">
                        <i class="demo-psi-folder me-1 text-muted"></i> Main Category
                    </label>
                    {{ html()->select('gallery_main_category_id', [])->value('')->class('select-category_id-list shadow-sm border-secondary-subtle')->id('gallery_main_category_id')->placeholder('All Categories') }}
                </div>

                <div class="col-lg-2 col-md-6" wire:ignore>
                    <label for="gallery_brand_id" class="form-label small fw-medium text-capitalize">
                        <i class="demo-psi-tag me-1 text-muted"></i> Brand
                    </label>
                    {{ html()->select('gallery_brand_id', [])->value('')->class('select-brand_id-list shadow-sm border-secondary-subtle')->id('gallery_brand_id')->placeholder('All Brands') }}
                </div>

                <div class="col-lg-2 col-md-6">
                    <label for="image_type" class="form-label small fw-medium text-capitalize">
                        <i class="demo-psi-camera me-1 text-muted"></i> Image Type
                    </label>
                    <select wire:model.live="image_type"
                            class="form-select form-select-sm shadow-sm border-secondary-subtle"
                            id="image_type">
                        <option value="">All Types</option>
                        <option value="normal">Normal</option>
                        <option value="angle">360 Angle</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               wire:model.live="selectAll" id="gallerySelectAll" />
                        <label class="form-check-label fw-medium" for="gallerySelectAll">
                            Select All
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Body: Image Grid --}}
        <div class="card-body">
            @if($data->count() > 0)
                <div class="row g-3">
                    @foreach($data as $image)
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6">
                            <div class="card h-100 border position-relative {{ in_array($image->id, $selected) ? 'border-primary border-2' : '' }}"
                                 style="overflow: hidden; border-radius: 8px;">

                                {{-- Selection Checkbox --}}
                                <div class="position-absolute top-0 start-0 m-2" style="z-index: 2;">
                                    <input class="form-check-input" type="checkbox"
                                           value="{{ $image->id }}"
                                           wire:model.live="selected"
                                           style="width: 1.2em; height: 1.2em;">
                                </div>

                                {{-- Image Type Badge --}}
                                <div class="position-absolute top-0 end-0 m-2" style="z-index: 2;">
                                    <span class="badge {{ $image->method === 'angle' ? 'bg-info' : 'bg-success' }} bg-opacity-75">
                                        {{ $image->method === 'angle' ? '360°' : 'Normal' }}
                                        @if($image->method === 'angle' && $image->degree)
                                            {{ $image->degree }}°
                                        @endif
                                    </span>
                                </div>

                                {{-- Thumbnail Badge --}}
                                @if($image->path === $image->product_thumbnail)
                                    <div class="position-absolute" style="top: 30px; left: 8px; z-index: 2;">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fa fa-star"></i> Thumbnail
                                        </span>
                                    </div>
                                @endif

                                {{-- Image --}}
                                <div style="aspect-ratio: 1/1; overflow: hidden; cursor: pointer;"
                                     wire:click="setPreview('{{ $image->path }}', '{{ addslashes($image->product_name) }}')">
                                    <img src="{{ $image->path }}"
                                         alt="{{ $image->name }}"
                                         class="w-100 h-100"
                                         style="object-fit: cover;"
                                         loading="lazy">
                                </div>

                                {{-- Product Info Footer --}}
                                <div class="card-body p-2">
                                    <a href="{{ route('product::edit', $image->product_id) }}"
                                       class="text-decoration-none fw-semibold link-primary small d-block text-truncate"
                                       title="{{ $image->product_name }}">
                                        {{ $image->product_name }}
                                    </a>
                                    <small class="text-muted">{{ $image->product_code }}</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <div class="text-muted my-4">
                        <i class="demo-psi-magnifi-glass fs-1 d-block mb-3 text-secondary-emphasis opacity-50"></i>
                        <h5 class="fw-semibold mb-2">No Images Found</h5>
                        <p class="mb-0">Try adjusting your search or filter criteria.</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Pagination Footer --}}
        @if($data->total() > 0)
            <div class="p-3 border-top bg-light">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-2 mb-lg-0">
                        <p class="text-muted small mb-0">
                            Showing {{ $data->firstItem() }} to {{ $data->lastItem() }}
                            of {{ $data->total() }} images
                        </p>
                    </div>
                    <div class="col-lg-6 d-flex justify-content-lg-end">
                        {{ $data->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Image Preview Modal --}}
    @if($previewImage)
        <div class="modal fade show d-block" tabindex="-1"
             style="background: rgba(0,0,0,0.8);"
             wire:click.self="closePreview()">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $previewProductName }}</h5>
                        <button type="button" class="btn-close"
                                wire:click="closePreview()"></button>
                    </div>
                    <div class="modal-body text-center p-0">
                        <img src="{{ $previewImage }}"
                             alt="Preview"
                             class="img-fluid"
                             style="max-height: 70vh; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- jQuery bridge for TomSelect dropdowns --}}
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#gallery_department_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('department_id', value);
                });
                $('#gallery_main_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('main_category_id', value);
                });
                $('#gallery_brand_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('brand_id', value);
                });
            });
        </script>
    @endpush
</div>
