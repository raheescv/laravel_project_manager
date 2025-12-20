<div class="py-3">
    <form wire:submit="save">
        <div class="row mb-2">
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title fw-bold mb-3">
                                    <i class="fa fa-tag fs-5 me-2 text-primary"></i>
                                    Product Details
                                </h5>
                            </div>
                            @if ($type == 'product')
                                <div>
                                    <div class="form-check form-switch">
                                        {{ html()->checkbox('is_selling')->value('')->class('form-check-input')->checked($products['is_selling'])->attribute('wire:model', 'products.is_selling') }}
                                        <label class="form-check-label ms-2 fw-medium" for="is_selling">Selling Product</label>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="row g-1">
                                <div class="col-md-4">
                                    <label for="code" class="form-label fw-medium">
                                        <i class="fa fa-code text-primary me-1 small"></i>
                                        UPC/EAN/ISBN/SKU <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-primary-subtle">
                                            <i class="fa fa-barcode"></i>
                                        </span>
                                        {{ html()->input('code')->value('')->class('form-control border-primary-subtle shadow-sm')->required(true)->placeholder('Enter your code')->attribute('wire:model', 'products.code') }}
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label for="name" class="form-label fw-medium">
                                        <i class="fa fa-tag text-primary me-1 small"></i>
                                        Product Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text bg-light border-primary-subtle">
                                            <i class="fa fa-pencil"></i>
                                        </span>
                                        {{ html()->input('name')->value('')->class('form-control border-primary-subtle shadow-sm')->required(true)->placeholder('Enter product name')->id('name')->autofocus()->attribute('wire:model', 'products.name') }}
                                    </div>
                                </div>
                            </div>
                            <div class="row g-1">
                                <div class="col-md-8">
                                    <label for="name_arabic" class="form-label fw-medium">
                                        <i class="fa fa-pencil text-primary me-1 small"></i>
                                        Arabic Name
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-secondary-subtle">
                                            <i class="fa fa-flag"></i>
                                        </span>
                                        {{ html()->input('name_arabic')->value('')->class('form-control shadow-sm')->attribute('dir', 'rtl')->placeholder('Enter arabic name')->id('name_arabic')->attribute('wire:model', 'products.name_arabic') }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label fw-medium">
                                        <i class="fa fa-toggle-on text-primary me-1 small"></i>
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    {{ html()->select('status', activeOrDisabled())->value('')->class('form-select border-primary-subtle shadow-sm')->placeholder('Select Status')->id('status')->attribute('wire:model', 'products.status') }}
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-4" wire:ignore>
                                    <label for="department_id" class="form-label fw-medium">
                                        <i class="fa fa-building text-primary me-1 small"></i>
                                        Department <span class="text-danger">*</span>
                                    </label>
                                    {{ html()->select('department_id', $departments)->value('')->class('select-department_id border-primary-subtle shadow-sm')->placeholder('Select department')->id('department_id') }}
                                </div>

                                <div class="col-md-4" wire:ignore>
                                    <label for="main_category_id" class="form-label fw-medium">
                                        <i class="fa fa-folder text-primary me-1 small"></i>
                                        Main Category <span class="text-danger">*</span>
                                    </label>
                                    {{ html()->select('main_category_id', [])->value('')->class('select-category_id-parent border-primary-subtle shadow-sm')->placeholder('Select Main Category')->id('main_category_id') }}
                                </div>

                                <div class="col-md-4" wire:ignore>
                                    <label for="sub_category_id" class="form-label fw-medium">
                                        <i class="fa fa-folder-open text-primary me-1 small"></i>
                                        Sub Category
                                    </label>
                                    {{ html()->select('sub_category_id', [])->value('')->class('select-category_id border-secondary-subtle shadow-sm')->placeholder('Select Sub Category')->id('sub_category_id') }}
                                </div>

                                <div class="col-md-4" wire:ignore @if ($type == 'service') hidden @endif>
                                    <label for="unit_id" class="form-label fw-medium">
                                        <i class="fa fa-cube text-primary me-1 small"></i>
                                        Base Unit <span class="text-danger">*</span>
                                    </label>
                                    {{ html()->select('unit_id', $units)->value('')->class('tomSelect border-primary-subtle shadow-sm')->placeholder('Select your unit')->id('unit_id')->attribute('wire:model', 'products.unit_id') }}
                                </div>
                                @if ($type == 'product')
                                    <div class="col-md-4" wire:ignore>
                                        <label for="unit_id" class="form-label fw-medium">
                                            <i class="fa fa-cube text-primary me-1 small"></i>
                                            Brand
                                        </label>
                                        {{ html()->select('brand_id', $brands)->value('')->class('select-brand_id border-secondary-subtle shadow-sm')->placeholder('Select Brand')->id('brand_id') }}
                                    </div>
                                    <div class="col-md-4">
                                        <label for="unit_id" class="form-label fw-medium">
                                            <i class="fa fa-cube text-primary me-1 small"></i>
                                            Size
                                        </label>
                                        {{ html()->input('size')->value('')->class('form-control')->placeholder('Enter your size')->attribute('wire:model', 'products.size')->id('size') }}
                                    </div>
                                @endif
                                @if ($type == 'product')
                                    <div class="col-12">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="hsn_code" class="form-label fw-medium">
                                                    <i class="fa fa-file-code-o text-primary me-1 small"></i>
                                                    HSN Code
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-secondary-subtle">
                                                        <i class="fa fa-file-text-o"></i>
                                                    </span>
                                                    {{ html()->input('hsn_code')->value('')->class('form-control border-secondary-subtle shadow-sm')->placeholder('Enter your hsn code')->id('hsn_code')->attribute('wire:model', 'products.hsn_code') }}
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="tax" class="form-label fw-medium">
                                                    <i class="fa fa-file-text text-primary me-1 small"></i>
                                                    Tax Rate
                                                </label>
                                                <div class="input-group">
                                                    {{ html()->number('tax')->value('')->attribute('step', 'any')->class('form-control border-secondary-subtle shadow-sm')->placeholder('Enter tax percentage')->id('tax')->attribute('wire:model', 'products.tax') }}
                                                    <span class="input-group-text bg-light border-secondary-subtle">
                                                        <i class="fa fa-calculator"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($type == 'product')
                                    @if ($barcode_type == 'product_wise')
                                        <div class="col-12">
                                            <label for="barcode" class="form-label fw-medium">
                                                <i class="fa fa-barcode text-primary me-1 small"></i>
                                                Barcode <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-primary-subtle">
                                                    <i class="fa fa-qrcode"></i>
                                                </span>
                                                {{ html()->input('barcode')->value('')->class('form-control border-primary-subtle shadow-sm')->placeholder('Enter a unique barcode here')->attribute('wire:model', 'products.barcode') }}
                                            </div>
                                            <small class="text-muted mt-1 d-block">Enter a unique barcode identifier for this product</small>
                                        </div>
                                    @endif
                                @endif

                                <div class="col-12 mt-2">
                                    <label for="description" class="form-label fw-medium">
                                        <i class="fa fa-file-text-o text-primary me-1 small"></i>
                                        Description
                                    </label>
                                    {{ html()->textarea('description')->value('')->class('form-control border-secondary-subtle shadow-sm')->rows(3)->placeholder('Enter product description')->id('description')->attribute('wire:model', 'products.description') }}
                                    <small class="text-muted mt-1 d-block">Add details about the product specifications, features, or any other relevant information</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom" id="second_header_area">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div class="d-flex align-items-center">
                                <div class="form-check form-switch">
                                    {{ html()->checkbox('is_favorite')->value('')->class('form-check-input')->checked($products['is_favorite'])->attribute('wire:model', 'products.is_favorite') }}
                                    <label class="form-check-label ms-2 fw-medium" for="is_favorite">
                                        <span class="text-primary">
                                            <i class="fa fa-star-o me-1"></i>Favorite
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <div class="d-flex flex-wrap gap-2 justify-content-end">
                                    @if (!isset($table_id))
                                        <button type="submit" class="btn btn-sm btn-success d-flex align-items-center gap-2 shadow-sm">
                                            <i class="fa fa-plus fs-5"></i> Save & Create New
                                        </button>
                                        <button type="button" wire:click="save(1)" class="btn btn-sm btn-primary d-flex align-items-center gap-2 shadow-sm">
                                            <i class="fa fa-save fs-5"></i> Save & Edit
                                        </button>
                                    @else
                                        @if ($type == 'product')
                                            @can('product.create')
                                                <a class="btn btn-sm btn-info text-white d-flex align-items-center gap-2 shadow-sm" href="{{ route('product::create') }}">
                                                    <i class="fa fa-file-o fs-5"></i> Create New
                                                </a>
                                            @endcan
                                            @can('product.edit')
                                                <button type="submit" class="btn btn-sm btn-success d-flex align-items-center gap-2 shadow-sm">
                                                    <i class="fa fa-save fs-5"></i> Save Changes
                                                </button>
                                            @endcan
                                        @else
                                            @can('service.create')
                                                <a class="btn btn-sm btn-info text-white d-flex align-items-center gap-2 shadow-sm" href="{{ route('service::create') }}">
                                                    <i class="fa fa-file-o fs-5"></i> Create New
                                                </a>
                                            @endcan
                                            @can('service.edit')
                                                <button type="submit" class="btn btn-sm btn-success d-flex align-items-center gap-2 shadow-sm">
                                                    <i class="fa fa-save fs-5"></i> Save Changes
                                                </button>
                                            @endcan
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        @if (count($this->getErrorBag()->toArray()))
                            <div class="alert alert-danger d-flex align-items-center" role="alert" @if (!$this->getErrorBag()->count()) hidden @endif>
                                <i class="fa fa-exclamation-circle fs-5 me-2"></i>
                                <div>
                                    <ol class="list-unstyled mb-0 ps-0">
                                        @foreach ($this->getErrorBag()->toArray() as $value)
                                            <li><i class="bi bi-exclamation-triangle me-1"></i>{{ $value[0] }}</li>
                                        @endforeach
                                    </ol>
                                </div>
                            </div>
                        @endif
                        <div class="row g-4">
                            @if ($type == 'product')
                                <div class="col-md-6">
                                    <div class="card h-100 bg-light border-0 rounded-3">
                                        <div class="card-body p-3">
                                            <h6 class="card-subtitle mb-3 d-flex align-items-center">
                                                <span class="badge bg-primary p-2 me-2">
                                                    <i class="fa fa-cart-plus"></i>
                                                </span>
                                                Purchase Information
                                            </h6>
                                            <div class="mb-3">
                                                <label for="cost" class="form-label fw-medium">
                                                    <i class="fa fa-file-text-o text-primary me-1 small"></i>
                                                    Buying Price <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-primary-subtle">
                                                        <i class="fa fa-dollar"></i>
                                                    </span>
                                                    {{ html()->number('cost')->value('')->attribute('step', 'any')->class('form-control border-primary-subtle shadow-sm')->required(true)->placeholder('Enter buying price')->attribute('wire:model', 'products.cost') }}
                                                </div>
                                            </div>
                                            <div class="row g-2 opacity-50" hidden>
                                                <div class="col-md-4">
                                                    <label for="name" class="form-label small">Without Tax</label>
                                                    <input id="_dm-inputCity" type="text" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="name" class="form-label small">Cost</label>
                                                    <input id="_dm-inputCity" type="text" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-5">
                                                    <label for="name" class="form-label small">Tax Amount</label>
                                                    <input id="_dm-inputCity" type="text" class="form-control form-control-sm">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card h-100 bg-light border-0 rounded-3">
                                        <div class="card-body p-3">
                                            <h6 class="card-subtitle mb-3 d-flex align-items-center">
                                                <span class="badge bg-success p-2 me-2">
                                                    <i class="fa fa-tags"></i>
                                                </span>
                                                Sales Information
                                            </h6>
                                            <div class="mb-3">
                                                <label for="mrp" class="form-label fw-medium">
                                                    <i class="fa fa-tag text-primary me-1 small"></i>
                                                    Selling Price <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-primary-subtle">
                                                        <i class="fa fa-dollar"></i>
                                                    </span>
                                                    {{ html()->number('mrp')->value('')->attribute('step', 'any')->class('form-control border-primary-subtle shadow-sm')->required(true)->placeholder('Enter selling price')->attribute('wire:model', 'products.mrp') }}
                                                </div>
                                            </div>
                                            <div class="row g-2 opacity-50" hidden>
                                                <div class="col-md-4">
                                                    <label for="name" class="form-label small">Without Tax</label>
                                                    <input id="_dm-inputCity" type="text" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="name" class="form-label small">Profit </label>
                                                    <input id="_dm-inputCity" type="text" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-5">
                                                    <label for="name" class="form-label small">Tax Amount</label>
                                                    <input id="_dm-inputCity" type="text" class="form-control form-control-sm">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if ($type == 'service')
                                <div class="col-md-6">
                                    <div class="card h-100 bg-light border-0 rounded-3">
                                        <div class="card-body p-3">
                                            <h6 class="card-subtitle mb-3 d-flex align-items-center">
                                                <span class="badge bg-primary p-2 me-2">
                                                    <i class="fa fa-money"></i>
                                                </span>
                                                Service Pricing
                                            </h6>
                                            <div class="mb-3">
                                                <label for="mrp" class="form-label fw-medium">
                                                    <i class="fa fa-tag text-primary me-1 small"></i>
                                                    Service Price <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-primary-subtle">
                                                        <i class="fa fa-dollar"></i>
                                                    </span>
                                                    {{ html()->number('mrp')->value('')->attribute('step', 'any')->class('form-control border-primary-subtle shadow-sm')->required(true)->placeholder('Enter service price')->attribute('wire:model', 'products.mrp') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card h-100 bg-light border-0 rounded-3">
                                        <div class="card-body p-3">
                                            <h6 class="card-subtitle mb-3 d-flex align-items-center">
                                                <span class="badge bg-info p-2 me-2 text-white">
                                                    <i class="fa fa-clock-o"></i>
                                                </span>
                                                Service Duration
                                            </h6>
                                            <div class="mb-3">
                                                <label for="time" class="form-label fw-medium">
                                                    <i class="fa fa-hourglass text-primary me-1 small"></i>
                                                    Time in Minutes <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    {{ html()->number('time')->value('')->attribute('step', 'any')->class('form-control border-primary-subtle shadow-sm')->required(true)->placeholder('Enter duration in minutes')->attribute('wire:model', 'products.time') }}
                                                    <span class="input-group-text bg-white border-primary-subtle">
                                                        <i class="fa fa-clock-o"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="col-12">
                                <div class="card bg-light border-0 rounded-3 mt-2">
                                    <div class="card-body p-3">
                                        <h6 class="card-subtitle mb-3 d-flex align-items-center">
                                            <span class="badge bg-secondary p-2 me-2">
                                                <i class="fa fa-camera"></i>
                                            </span>
                                            Product Images
                                        </h6>
                                        <div class="mb-2">
                                            <x-filepond::upload wire:model="images" multiple max-files="5" class="border border-dashed rounded-3" />
                                            <div class="text-muted small text-center mt-2">
                                                <i class="fa fa-info-circle me-1"></i>
                                                Upload up to 5 product images (JPG, PNG or GIF)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card bg-light border-0 rounded-3 mt-2">
                                    <div class="card-body p-3">
                                        <h6 class="card-subtitle mb-3 d-flex align-items-center">
                                            <span class="badge bg-primary p-2 me-2">
                                                <i class="fa fa-globe"></i>
                                            </span>
                                            360° Product View
                                        </h6>
                                        <div class="mb-2">
                                            <x-filepond::upload wire:model="angles_360" multiple max-files="36" class="border border-dashed rounded-3" />
                                            <div class="text-muted small text-center mt-2">
                                                <i class="fa fa-info-circle me-1"></i>
                                                Upload multiple images for 360° view (up to 36 images, JPG, PNG, GIF, BMP, WEBP, SVG - Max 10MB each)
                                            </div>
                                        </div>

                                        @if (count($angles_360) > 0)
                                            <div class="mt-3">
                                                <h6 class="mb-2">Image Angles:</h6>
                                                <div class="row">
                                                    @foreach ($angles_360 as $index => $image)
                                                        <div class="col-md-4 mb-2">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">Image {{ $index + 1 }}</span>
                                                                <input type="number" wire:model="degree.{{ $index }}" class="form-control" placeholder="Angle (0-359)" min="0"
                                                                    max="359">
                                                                <span class="input-group-text">°</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if ($product && $product->angleImages()->count() > 0)
                                            <div class="mt-3">
                                                <div class="alert alert-info">
                                                    <h6 class="mb-2">
                                                        <i class="fa fa-check-circle me-2"></i>
                                                        Current 360° Images ({{ $product->angleImages()->count() }})
                                                    </h6>
                                                    <div class="row">
                                                        @foreach ($product->angleImages()->orderedByAngle()->get() as $image)
                                                            <div class="col-md-2 mb-2">
                                                                <div class="text-center">
                                                                    <img src="{{ $image->path }}" alt="{{ $image->alt_text ?? '360° Image' }}" class="img-thumbnail"
                                                                        style="width: 60px; height: 60px; object-fit: cover;">
                                                                    <div class="small text-muted">{{ $image->degree }}°</div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-12 mt-4">
                <div class="card shadow-sm">
                    <div class="accordion" id="_dm-openAccordion">
                        <div class="accordion-item border-0">
                            <div class="accordion-header bg-light rounded-top" id="_dm-openAccHeadingTwo">
                                <button class="accordion-button fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#_dm-openAccCollapseTwo" aria-expanded="false"
                                    aria-controls="_dm-openAccCollapseTwo">
                                    <i class="fa fa-edit fs-5 me-2"></i>
                                    Additional Product Details
                                </button>
                            </div>
                            <div id="_dm-openAccCollapseTwo" class="accordion-collapse collapse show" aria-labelledby="_dm-openAccHeadingTwo">
                                <div class="accordion-body p-4">
                                    <div class="col-12">
                                        <div class="tab-base">
                                            <ul class="nav nav-tabs nav-tabs-bordered mb-3" role="tablist">
                                                @if (isset($table_id))
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link @if ($selectedTab == 'Prices') active show @endif d-flex align-items-center gap-2" data-bs-toggle="tab"
                                                            wire:click="tabSelect('Prices')" data-bs-target="#tabPrices" type="button" role="tab" aria-controls="profile"
                                                            aria-selected="false" tabindex="-1">
                                                            <i class="fa fa-money text-success"></i>
                                                            Prices
                                                        </button>
                                                    </li>
                                                @endif
                                                @if ($type == 'product')
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link @if ($selectedTab == 'Attributes') active @endif d-flex align-items-center gap-2" data-bs-toggle="tab"
                                                            wire:click="tabSelect('Attributes')" data-bs-target="#tabAttributes" type="button" role="tab" aria-controls="home"
                                                            aria-selected="true">
                                                            <i class="fa fa-table text-primary"></i>
                                                            Attributes
                                                        </button>
                                                    </li>
                                                @endif
                                                @if ($type == 'product')
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link @if ($selectedTab == 'Stock') active show @endif d-flex align-items-center gap-2" data-bs-toggle="tab"
                                                            wire:click="tabSelect('Stock')" data-bs-target="#tabStock" type="button" role="tab" aria-controls="profile" aria-selected="false"
                                                            tabindex="-1">
                                                            <i class="fa fa-archive text-warning"></i>
                                                            Stock Details
                                                        </button>
                                                    </li>
                                                @endif
                                                @if ($type == 'product')
                                                    @if (isset($table_id))
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link @if ($selectedTab == 'Uom') active show @endif d-flex align-items-center gap-2" data-bs-toggle="tab"
                                                                wire:click="tabSelect('Uom')" data-bs-target="#tabUom" type="button" role="tab" aria-controls="profile" aria-selected="false"
                                                                tabindex="-1">
                                                                <i class="fa fa-cubes text-info"></i>
                                                                Unit of Measures
                                                            </button>
                                                        </li>
                                                    @endif
                                                @endif
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link @if ($selectedTab == 'Images') active show @endif d-flex align-items-center gap-2" data-bs-toggle="tab"
                                                        wire:click="tabSelect('Images')" data-bs-target="#tabImages" type="button" role="tab" aria-controls="profile" aria-selected="false"
                                                        tabindex="-1">
                                                        <i class="fa fa-picture-o text-danger"></i>
                                                        Images
                                                    </button>
                                                </li>
                                                @if (isset($table_id) && $type == 'product')
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link @if ($selectedTab == 'Related') active show @endif d-flex align-items-center gap-2" data-bs-toggle="tab"
                                                            wire:click="tabSelect('Related')" data-bs-target="#tabRelated" type="button" role="tab" aria-controls="profile" aria-selected="false"
                                                            tabindex="-1">
                                                            <i class="fa fa-link text-info"></i>
                                                            Related Products
                                                        </button>
                                                    </li>
                                                @endif
                                            </ul>
                                            <div class="tab-content">
                                                @if (isset($table_id))
                                                    <div id="tabPrices" class="tab-pane fade @if ($selectedTab == 'Prices') active show @endif" role="tabpanel">
                                                        <div class="row g-2">
                                                            <h5 class="card-title ">Prices </h5>
                                                            <div class="col-md-1">
                                                                <div class="d-flex flex-wrap justify-content-center">
                                                                    <button type="button" id="ProductPriceAdd" class="btn btn-primary hstack gap-2 align-self-center">
                                                                        <i class="fa fa-plus fs-5"></i>
                                                                        <span class="vr"></span>
                                                                        Add
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card mb-3">
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-striped">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Type</th>
                                                                                <th>Start Date</th>
                                                                                <th>End Date</th>
                                                                                <th class="text-end">Amount</th>
                                                                                <th>Status</th>
                                                                                <th>Action</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($products['prices'] as $item)
                                                                                <tr>
                                                                                    <td>{{ priceTypes()[$item['price_type']] }}</td>
                                                                                    <td>{{ systemDate($item['start_date']) }}</td>
                                                                                    <td>{{ systemDate($item['end_date']) }}</td>
                                                                                    <td class="text-end">{{ currency($item['amount']) }}</td>
                                                                                    <td>{{ ucFirst($item['status']) }}</td>
                                                                                    <td>
                                                                                        <i table_id="{{ $item['id'] }}" class="fa fa-pencil fs-5 me-2 pointer product_price_edit"></i>
                                                                                        <i wire:confirm="Are You sure?" wire:click="priceDelete({{ $item['id'] }})"
                                                                                            class="fa fa-trash fs-5 me-2 pointer delete"></i>
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if ($type == 'product')
                                                    <div id="tabAttributes" class="tab-pane fade @if ($selectedTab == 'Attributes') active show @endif" role="tabpanel">
                                                        <div class="row g-3 ">
                                                            <h5 class="card-title ">Attributes</h5>
                                                            <div class="col-md-4">
                                                                <label for="pattern" class="form-label">Pattern</label>
                                                                {{ html()->input('pattern')->value('')->class('form-control')->placeholder('Enter your pattern')->attribute('wire:model', 'products.pattern') }}
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="color" class="form-label">Color</label>
                                                                {{ html()->input('color')->value('')->class('form-control')->placeholder('Enter your color')->attribute('wire:model', 'products.color') }}
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="model" class="form-label">Model</label>
                                                                {{ html()->input('model')->value('')->class('form-control')->placeholder('Enter your model')->attribute('wire:model', 'products.model') }}
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="part_no" class="form-label">Part No</label>
                                                                {{ html()->input('part_no')->value('')->class('form-control')->placeholder('Enter your part no')->attribute('wire:model', 'products.part_no') }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if ($type == 'product')
                                                    <div id="tabStock" class="tab-pane fade @if ($selectedTab == 'Stock') active show @endif" role="tabpanel">
                                                        <div class="row g-3 ">
                                                            <h5 class="card-title ">Stock details</h5>
                                                            <div class="col-md-4">
                                                                <label for="min_stock" class="form-label">Min Stock</label>
                                                                {{ html()->number('min_stock')->value('')->attribute('step', 'any')->class('form-control number')->placeholder('Enter your min stock')->attribute('wire:model', 'products.min_stock') }}
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="max_stock" class="form-label">Max Stock</label>
                                                                {{ html()->number('max_stock')->value('')->attribute('step', 'any')->class('form-control number')->placeholder('Enter your max stock')->attribute('wire:model', 'products.max_stock') }}
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="location" class="form-label">Location</label>
                                                                {{ html()->input('location')->value('')->class('form-control')->placeholder('Enter location')->attribute('wire:model', 'products.location') }}
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="reorder_level" class="form-label">Reorder Level</label>
                                                                {{ html()->input('reorder_level')->value('')->class('form-control')->placeholder('Enter reorder level')->attribute('wire:model', 'products.reorder_level') }}
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="plu" class="form-label">PLU</label>
                                                                {{ html()->input('plu')->value('')->class('form-control')->placeholder('Enter plu')->attribute('wire:model', 'products.plu') }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if ($type == 'product')
                                                    @if (isset($table_id))
                                                        <div id="tabUom" class="tab-pane fade @if ($selectedTab == 'Uom') active show @endif" role="tabpanel">
                                                            <div class="row g-2">
                                                                <h5 class="card-title ">Unit of Measures </h5>
                                                                <div class="col-md-1">
                                                                    <div class="mt-4 d-flex flex-wrap justify-content-center">
                                                                        <button type="button" id="ProductUnitAdd" class="btn btn-primary hstack gap-2 align-self-center">
                                                                            <i class="fa fa-plus fs-5"></i>
                                                                            <span class="vr"></span>
                                                                            Add
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="card mb-3">
                                                                <div class="card-body">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-striped">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Convert To (1 ({{ $products['unit']['name'] }}) base unit = ? Sub unit)</th>
                                                                                    <th class="text-end">Conversion Factor</th>
                                                                                    <th>Barcode </th>
                                                                                    <th>Action</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($products['units'] as $item)
                                                                                    <tr>
                                                                                        <td>{{ $item['sub_unit']['name'] }}</td>
                                                                                        <td class="text-end">{{ $item['conversion_factor'] }}</td>
                                                                                        <td>{{ $item['barcode'] }}</td>
                                                                                        <td>
                                                                                            <i table_id="{{ $item['id'] }}" class="fa fa-pencil fs-5 me-2 pointer product_unit_edit"></i>
                                                                                            <i wire:confirm="Are You sure?" wire:click="unitDelete({{ $item['id'] }})"
                                                                                                class="fa fa-trash fs-5 me-2 pointer delete"></i>
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endif
                                                <div id="tabImages" class="tab-pane fade @if ($selectedTab == 'Images') active show @endif" role="tabpanel">
                                                    <div class="col-12">
                                                        <div class="row g-1 mb-3">
                                                            @foreach ($products['images'] as $item)
                                                                <div class="col-4 position-relative pointer">
                                                                    <img class="img-fluid rounded" width="100%" height="10%" src="{{ $item['path'] }}" alt="thumbs" loading="lazy"
                                                                        wire:confirm="Are you sure you want this as the default image?" wire:click="defaultImage('{{ $item['path'] }}')">
                                                                    <i class="fa fa-trash fs-5 me-2 pointer position-absolute top-0 end-0 m-2"
                                                                        wire:confirm="Are you sure you want to delete this image?" wire:click="deleteImage('{{ $item['id'] }}')">
                                                                    </i>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                                @if (isset($table_id) && $type == 'product')
                                                    <div id="tabRelated" class="tab-pane fade @if ($selectedTab == 'Related') active show @endif" role="tabpanel">
                                                        <div class="row g-2">
                                                            <h5 class="card-title">Related Products (Same Code: <code>{{ $products['code'] ?? '-' }}</code>)</h5>
                                                        </div>
                                                        @if (count($relatedProducts) > 0)
                                                            <div class="card mb-3">
                                                                <div class="card-body">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-striped">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>ID</th>
                                                                                    <th>Name</th>
                                                                                    <th>Department</th>
                                                                                    <th>Category</th>
                                                                                    <th>Brand</th>
                                                                                    <th>Size</th>
                                                                                    <th>Barcode</th>
                                                                                    <th class="text-end">Cost</th>
                                                                                    <th class="text-end">Price</th>
                                                                                    <th>Status</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($relatedProducts as $item)
                                                                                    <tr>
                                                                                        <td>
                                                                                            <span class="badge bg-secondary rounded-pill">{{ $item['id'] }}</span>
                                                                                        </td>
                                                                                        <td>
                                                                                            <a href="{{ route('product::edit', $item['id']) }}" class="text-decoration-none fw-semibold link-primary">
                                                                                                {{ $item['name'] }}
                                                                                            </a>
                                                                                        </td>
                                                                                        <td>
                                                                                            <span class="fw-medium text-primary">{{ $item['department']['name'] ?? '-' }}</span>
                                                                                        </td>
                                                                                        <td>
                                                                                            <span class="text-secondary">{{ $item['main_category']['name'] ?? '-' }}</span>
                                                                                        </td>
                                                                                        <td>
                                                                                            @if (isset($item['brand']['name']))
                                                                                                <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle">{{ $item['brand']['name'] }}</span>
                                                                                            @else
                                                                                                <span class="text-muted">-</span>
                                                                                            @endif
                                                                                        </td>
                                                                                        <td>
                                                                                            <span class="badge bg-light text-dark border border-secondary-subtle">{{ $item['size'] ?? '-' }}</span>
                                                                                        </td>
                                                                                        <td>
                                                                                            @if (isset($item['barcode']) && $item['barcode'])
                                                                                                <code class="bg-light rounded px-2 py-1 small">{{ $item['barcode'] }}</code>
                                                                                            @else
                                                                                                <span class="text-muted">-</span>
                                                                                            @endif
                                                                                        </td>
                                                                                        <td class="text-end fw-semibold">
                                                                                            <span class="text-success">{{ currency($item['cost'] ?? 0) }}</span>
                                                                                        </td>
                                                                                        <td class="text-end fw-bold">
                                                                                            <span class="text-dark">{{ currency($item['mrp'] ?? 0) }}</span>
                                                                                        </td>
                                                                                        <td>
                                                                                            <span class="badge bg-{{ $item['status'] == 'active' ? 'success' : 'secondary' }}">{{ ucFirst($item['status'] ?? 'active') }}</span>
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="alert alert-info">
                                                                <i class="fa fa-info-circle me-2"></i>
                                                                No related products found with the same code.
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @push('scripts')
        @filepondScripts
        <script>
            $(document).ready(function() {
                $('#unit_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('products.unit_id', value);
                    document.querySelector('#department_id').tomselect.open();
                });
                $('#department_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('products.department_id', value);
                    document.querySelector('#main_category_id').tomselect.open();
                });
                $('#brand_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('products.brand_id', value);
                    $("#size").select();
                });
                $('#main_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('products.main_category_id', value);
                    document.querySelector('#sub_category_id').tomselect.open();
                });
                $('#sub_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('products.sub_category_id', value);
                    $("#hsn_code").select();
                });
            });
            window.addEventListener('SelectDropDownValues', event => {
                var product = event.detail[0];

                var unitElement = document.querySelector('#unit_id');
                if (unitElement && unitElement.tomselect) {
                    try {
                        var tomSelectInstance = unitElement.tomselect;
                        if (product['unit_id']) {
                            tomSelectInstance.addItem(product['unit_id']);
                        } else {
                            tomSelectInstance.clear();
                        }
                    } catch (error) {
                        console.warn('Error handling unit_id:', error);
                    }
                }

                var brandElement = document.querySelector('#brand_id');
                if (brandElement && brandElement.tomselect) {
                    try {
                        var tomSelectInstance = brandElement.tomselect;
                        if (product['brand_id']) {
                            if (product['brand']) {
                                var preselectedData = {
                                    id: product['brand']['id'],
                                    name: product['brand']['name'],
                                };
                                tomSelectInstance.addOption(preselectedData);
                            }
                            tomSelectInstance.addItem(product['brand_id']);
                        } else {
                            tomSelectInstance.clear();
                        }
                    } catch (error) {
                        console.warn('Error handling brand_id:', error);
                    }
                }
                var departmentElement = document.querySelector('#department_id');
                if (departmentElement && departmentElement.tomselect) {
                    try {
                        var tomSelectInstance = departmentElement.tomselect;
                        if (product['department_id']) {
                            if (product['department']) {
                                var preselectedData = {
                                    id: product['department']['id'],
                                    name: product['department']['name'],
                                };
                                tomSelectInstance.addOption(preselectedData);
                            }
                            tomSelectInstance.addItem(product['department_id']);
                        } else {
                            tomSelectInstance.clear();
                        }
                    } catch (error) {
                        console.warn('Error handling department_id:', error);
                    }
                }

                var mainCategoryElement = document.querySelector('#main_category_id');
                if (mainCategoryElement && mainCategoryElement.tomselect) {
                    try {
                        var tomSelectInstance = mainCategoryElement.tomselect;
                        if (product['main_category_id']) {
                            if (product['main_category']) {
                                var preselectedData = {
                                    id: product['main_category']['id'],
                                    name: product['main_category']['name'],
                                };
                                tomSelectInstance.addOption(preselectedData);
                            }
                            tomSelectInstance.addItem(product['main_category_id']);
                        } else {
                            tomSelectInstance.clear();
                        }
                    } catch (error) {
                        console.warn('Error handling main_category_id:', error);
                    }
                }

                var subCategoryElement = document.querySelector('#sub_category_id');
                if (subCategoryElement && subCategoryElement.tomselect) {
                    try {
                        var tomSelectInstance = subCategoryElement.tomselect;
                        if (product['sub_category_id']) {
                            if (product['sub_category']) {
                                var preselectedData = {
                                    id: product['sub_category']['id'],
                                    name: product['sub_category']['name'],
                                };
                                tomSelectInstance.addOption(preselectedData);
                            }
                            tomSelectInstance.addItem(product['sub_category_id']);
                        } else {
                            tomSelectInstance.clear();
                        }
                    } catch (error) {
                        console.warn('Error handling sub_category_id:', error);
                    }
                }
                $('#name').select();
            });

            $('#ProductUnitAdd').click(function() {
                Livewire.dispatch("Product-Units-Create-Component");
            });
            $(document).on('click', '.product_unit_edit', function() {
                Livewire.dispatch("Product-Units-Update-Component", {
                    id: $(this).attr('table_id')
                });
            });

            $('#ProductPriceAdd').click(function() {
                Livewire.dispatch("Product-Prices-Create-Component");
            });
            $(document).on('click', '.product_price_edit', function() {
                Livewire.dispatch("Product-Prices-Update-Component", {
                    id: $(this).attr('table_id')
                });
            });
        </script>
        <script>
            document.getElementById("name").addEventListener("keyup", function() {
                const text = this.value;

                if (!text) {
                    document.getElementById("name_arabic").value = "";
                    @this.set('products.name_arabic', '');
                    return;
                }

                // Free Google Translate API
                fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=ar&dt=t&q=${encodeURI(text)}`)
                    .then(res => res.json())
                    .then(data => {
                        const translated = data[0][0][0];
                        document.getElementById("name_arabic").value = translated;
                        @this.set('products.name_arabic', translated); // Update Livewire
                    })
                    .catch(err => console.error("Translation error:", err));
            });
        </script>
    @endpush
</div>
