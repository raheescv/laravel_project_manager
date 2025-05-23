<div>
    <form wire:submit="save">
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h5 class="card-title">UID: {{ $table_id ?? '' }}</h5>
                            </div>
                            @if ($type == 'product')
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-center pt-1 mb-2">
                                        <label class="form-check-label flex-fill" style="text-align: right">Selling Product</label>
                                        <div class="form-check form-switch">
                                            {{ html()->checkbox('is_selling')->value('')->class('form-check-input ms-0')->checked($products['is_selling'])->attribute('wire:model', 'products.is_selling') }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="code" class="form-label">UPC/EAN/ISBN/SKU *</label>
                                {{ html()->input('code')->value('')->class('form-control')->required(true)->placeholder('Enter your code')->attribute('wire:model', 'products.code') }}
                            </div>
                            <div class="col-md-8">
                                <label for="name" class="form-label">Name *</label>
                                {{ html()->input('name')->value('')->class('form-control')->required(true)->placeholder('Enter your name')->id('name')->autofocus()->attribute('wire:model', 'products.name') }}
                            </div>
                            <div class="col-md-8">
                                <label for="name_arabic" class="form-label">Arabic Name</label>
                                {{ html()->input('name_arabic')->value('')->class('form-control')->attribute('dir', 'rtl')->placeholder('Enter your arabic name')->attribute('wire:model', 'products.name_arabic') }}
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label">Status *</label>
                                {{ html()->select('status', activeOrDisabled())->value('')->class('form-control')->placeholder('Select Status')->id('status')->attribute('wire:model', 'products.status') }}
                            </div>
                            <div class="col-md-4" wire:ignore>
                                <label for="department_id" class="form-label">Department *</label>
                                {{ html()->select('department_id', $departments)->value('')->class('select-department_id')->placeholder('Select department')->id('department_id') }}
                            </div>
                            <div class="col-md-4" wire:ignore>
                                <label for="main_category_id" class="form-label">Main Category *</label>
                                {{ html()->select('main_category_id', [])->value('')->class('select-category_id-parent')->placeholder('Select Main Category')->id('main_category_id') }}
                            </div>
                            <div class="col-md-4" wire:ignore>
                                <label for="sub_category_id" class="form-label">Sub Category</label>
                                {{ html()->select('sub_category_id', [])->value('')->class('select-category_id')->placeholder('Select Sub Category')->id('sub_category_id') }}
                            </div>
                            <div class="col-md-4" wire:ignore @if ($type == 'service') hidden @endif>
                                <label for="unit_id" class="form-label">Base Unit *</label>
                                {{ html()->select('unit_id', $units)->value('')->class('tomSelect')->placeholder('Select your unit')->id('unit_id')->attribute('wire:model', 'products.unit_id') }}
                            </div>
                            @if ($type == 'product')
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                {{ html()->input('hsn_code')->value('')->class('form-control')->placeholder('Enter your hsn code')->id('hsn_code')->attribute('wire:model', 'products.hsn_code') }}
                                                <label for="hsn_code" class="form-label">HSN Code</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                {{ html()->number('tax')->value('')->attribute('step', 'any')->class('form-control number')->placeholder('Enter your tax')->id('tax')->attribute('wire:model', 'products.tax') }}
                                                <label for="tax" class="form-label">Tax</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if ($type == 'product')
                                @if ($barcode_type == 'product_wise')
                                    <div class="col-md-12">
                                        <label for="barcode">Barcode</label>
                                        {{ html()->input('barcode')->value('')->class('form-control')->placeholder('Enter a unique barcode here')->required(true)->attribute('wire:model', 'products.barcode') }}
                                    </div>
                                @endif
                            @endif
                            <div class="col-md-12">
                                <div class="form-floating">
                                    {{ html()->textarea('description')->value('')->class('form-control')->placeholder('Leave a comment here')->id('description')->attribute('wire:model', 'products.description') }}
                                    <label for="description">Description</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check form-switch">
                                <div class="d-flex align-items-center pt-1 mb-2">
                                    <label class="form-check-label flex-fill" style="text-align: right">Favorite</label>
                                    <div class="form-check form-switch">
                                        {{ html()->checkbox('is_favorite')->value('')->class('form-check-input ms-0')->checked($products['is_favorite'])->attribute('wire:model', 'products.is_favorite') }}
                                    </div>
                                </div>
                            </div>
                            <div>
                                @if (!isset($table_id))
                                    <button type="submit" class="btn btn-sm btn-success me-1">Save & Create New</button>
                                    <button type="button" wire:click="save(1)" class="btn btn-sm btn-primary">Save & Edit</button>
                                @else
                                    @if ($type == 'product')
                                        @can('product.create')
                                            <a class="btn btn-sm btn-info me-1" href="{{ route('product::create') }}">Create New</a>
                                        @endcan
                                        @can('product.edit')
                                            <button type="submit" class="btn btn-sm btn-success">Save</button>
                                        @endcan
                                    @else
                                        @can('service.create')
                                            <a class="btn btn-sm btn-info me-1" href="{{ route('service::create') }}">Create New</a>
                                        @endcan
                                        @can('service.edit')
                                            <button type="submit" class="btn btn-sm btn-success">Save</button>
                                        @endcan
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger" role="alert" @if (!$this->getErrorBag()->count()) hidden @endif>
                            <ol class="list-unstyled mb-0">
                                <?php foreach ($this->getErrorBag()->toArray() as $value): ?>
                                <li class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>{{ $value[0] }}</li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                        <div class="row">
                            @if ($type == 'product')
                                <div class="col-md-6 mb-3">
                                    <h5 class="card-title">Purchase</h5>
                                    <div class="mb-3">
                                        <label for="cost" class="form-label">Buying Price</label>
                                        {{ html()->number('cost')->value('')->attribute('step', 'any')->class('form-control number')->required(true)->placeholder('Enter your Buying Price')->attribute('wire:model', 'products.cost') }}
                                    </div>
                                    <div class="row" style="padding-top:10px;" hidden>
                                        <div class="col-md-4">
                                            <label for="name" class="form-label">Without Tax</label>
                                            <input id="_dm-inputCity" type="text" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="name" class="form-label">Cost</label>
                                            <input id="_dm-inputCity" type="text" class="form-control">
                                        </div>
                                        <div class="col-md-5">
                                            <label for="name" class="form-label">Tax Amount</label>
                                            <input id="_dm-inputCity" type="text" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h5 class="card-title">Sales</h5>
                                    <div class="mb-3">
                                        <label for="mrp" class="form-label">Selling Price</label>
                                        {{ html()->number('mrp')->value('')->attribute('step', 'any')->class('form-control number')->required(true)->placeholder('Enter your Selling Price')->attribute('wire:model', 'products.mrp') }}
                                    </div>
                                    <div class="row" style="padding-top:10px;" hidden>
                                        <div class="col-md-4">
                                            <label for="name" class="form-label">Without Tax</label>
                                            <input id="_dm-inputCity" type="text" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="name" class="form-label">Profit </label>
                                            <input id="_dm-inputCity" type="text" class="form-control">
                                        </div>
                                        <div class="col-md-5">
                                            <label for="name" class="form-label">Tax Amount</label>
                                            <input id="_dm-inputCity" type="text" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if ($type == 'service')
                                <div class="col-md-6 mb-3">
                                    <h5 class="card-title">Price</h5>
                                    <div class="mb-3">
                                        <label for="mrp" class="form-label">Price</label>
                                        {{ html()->number('mrp')->value('')->attribute('step', 'any')->class('form-control number')->required(true)->placeholder('Enter your Buying Price')->attribute('wire:model', 'products.mrp') }}
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h5 class="card-title">Time</h5>
                                    <div class="mb-3">
                                        <label for="time" class="form-label">Time in Minutes</label>
                                        {{ html()->number('time')->value('')->attribute('step', 'any')->class('form-control number')->required(true)->placeholder('Enter your Buying Price')->attribute('wire:model', 'products.time') }}
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <x-filepond::upload wire:model="images" multiple max-files="5" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 mb-3">
                <div class="card h-100">
                    <div class="accordion" id="_dm-openAccordion">
                        <div class="accordion-item">
                            <div class="accordion-header" id="_dm-openAccHeadingTwo">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#_dm-openAccCollapseTwo" aria-expanded="false"
                                    aria-controls="_dm-openAccCollapseTwo">
                                    More Details
                                </button>
                            </div>
                            <div id="_dm-openAccCollapseTwo" class="accordion-collapse collapse show" aria-labelledby="_dm-openAccHeadingTwo">
                                <div class="accordion-body">
                                    <div class="col-md-12 mb-3">
                                        <div class="tab-base">
                                            <ul class="nav nav-tabs justify-content-end" role="tablist">
                                                @if (isset($table_id))
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link @if ($selectedTab == 'Prices') active show @endif" data-bs-toggle="tab" wire:click="tabSelect('Prices')"
                                                            data-bs-target="#tabPrices" type="button" role="tab" aria-controls="profile" aria-selected="false" tabindex="-1">
                                                            Prices
                                                        </button>
                                                    </li>
                                                @endif
                                                @if ($type == 'product')
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link @if ($selectedTab == 'Attributes') active @endif" data-bs-toggle="tab" wire:click="tabSelect('Attributes')"
                                                            data-bs-target="#tabAttributes" type="button" role="tab" aria-controls="home" aria-selected="true">Attributes
                                                        </button>
                                                    </li>
                                                @endif
                                                @if ($type == 'product')
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link @if ($selectedTab == 'Stock') active show @endif" data-bs-toggle="tab" wire:click="tabSelect('Stock')"
                                                            data-bs-target="#tabStock" type="button" role="tab" aria-controls="profile" aria-selected="false" tabindex="-1">Stock details
                                                        </button>
                                                    </li>
                                                @endif
                                                @if ($type == 'product')
                                                    @if (isset($table_id))
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link @if ($selectedTab == 'Uom') active show @endif" data-bs-toggle="tab" wire:click="tabSelect('Uom')"
                                                                data-bs-target="#tabUom" type="button" role="tab" aria-controls="profile" aria-selected="false" tabindex="-1">
                                                                Unit of Measures
                                                            </button>
                                                        </li>
                                                    @endif
                                                @endif
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link @if ($selectedTab == 'Images') active show @endif" data-bs-toggle="tab" wire:click="tabSelect('Images')"
                                                        data-bs-target="#tabImages" type="button" role="tab" aria-controls="profile" aria-selected="false" tabindex="-1">Images
                                                    </button>
                                                </li>
                                            </ul>
                                            <div class="tab-content">
                                                @if (isset($table_id))
                                                    <div id="tabPrices" class="tab-pane fade @if ($selectedTab == 'Prices') active show @endif" role="tabpanel">
                                                        <div class="row g-2">
                                                            <h5 class="card-title ">Prices </h5>
                                                            <div class="col-md-1">
                                                                <div class="d-flex flex-wrap justify-content-center">
                                                                    <button type="button" id="ProductPriceAdd" class="btn btn-primary hstack gap-2 align-self-center">
                                                                        <i class="demo-psi-add fs-5"></i>
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
                                                                                        <i table_id="{{ $item['id'] }}" class="demo-psi-pencil fs-5 me-2 pointer product_price_edit"></i>
                                                                                        <i wire:confirm="Are You sure?" wire:click="priceDelete({{ $item['id'] }})"
                                                                                            class="demo-psi-trash fs-5 me-2 pointer delete"></i>
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
                                                                <label for="size" class="form-label">Size</label>
                                                                {{ html()->input('size')->value('')->class('form-control')->placeholder('Enter your size')->attribute('wire:model', 'products.size') }}
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="model" class="form-label">Model</label>
                                                                {{ html()->input('model')->value('')->class('form-control')->placeholder('Enter your model')->attribute('wire:model', 'products.model') }}
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="brand" class="form-label">Brand</label>
                                                                {{ html()->input('brand')->value('')->class('form-control')->placeholder('Enter your brand')->attribute('wire:model', 'products.brand') }}
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="part_no" class="form-label">PartNo</label>
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
                                                                            <i class="demo-psi-add fs-5"></i>
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
                                                                                            <i table_id="{{ $item['id'] }}" class="demo-psi-pencil fs-5 me-2 pointer product_unit_edit"></i>
                                                                                            <i wire:confirm="Are You sure?" wire:click="unitDelete({{ $item['id'] }})"
                                                                                                class="demo-psi-trash fs-5 me-2 pointer delete"></i>
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
                                                                    <i class="demo-psi-trash fs-5 me-2 pointer position-absolute top-0 end-0 m-2"
                                                                        wire:confirm="Are you sure you want to delete this image?" wire:click="deleteImage('{{ $item['id'] }}')">
                                                                    </i>
                                                                </div>
                                                            @endforeach
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

                var tomSelectInstance = document.querySelector('#unit_id').tomselect;
                if (product['unit_id']) {
                    tomSelectInstance.addItem(product['unit_id']);
                } else {
                    tomSelectInstance.clear();
                }

                var tomSelectInstance = document.querySelector('#department_id').tomselect;
                if (product['department_id']) {
                    if (product['department']) {
                        preselectedData = {
                            id: product['department']['id'],
                            name: product['department']['name'],
                        };
                        tomSelectInstance.addOption(preselectedData);
                    }
                    tomSelectInstance.addItem(product['department_id']);
                } else {
                    tomSelectInstance.clear();
                }

                var tomSelectInstance = document.querySelector('#main_category_id').tomselect;
                if (product['main_category_id']) {
                    if (product['main_category']) {
                        preselectedData = {
                            id: product['main_category']['id'],
                            name: product['main_category']['name'],
                        };
                        tomSelectInstance.addOption(preselectedData);
                    }
                    tomSelectInstance.addItem(product['main_category_id']);
                } else {
                    tomSelectInstance.clear();
                }

                var tomSelectInstance = document.querySelector('#sub_category_id').tomselect;
                if (product['sub_category_id']) {
                    if (product['sub_category']) {
                        preselectedData = {
                            id: product['sub_category']['id'],
                            name: product['sub_category']['name'],
                        };
                        tomSelectInstance.addOption(preselectedData);
                    }
                    tomSelectInstance.addItem(product['sub_category_id']);
                } else {
                    tomSelectInstance.clear();
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
    @endpush
</div>
