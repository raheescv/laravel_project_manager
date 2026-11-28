@php
    $cols = $columns;
    $show = fn (string $key, bool $default = true): bool => (bool) ($cols[$key] ?? $default);
    $showCodes = $show('code') || $show('barcode');
    $showCategory = $show('department') || $show('main_category') || $show('sub_category');
    $columnCount = 2 + (int) $show('image', false) + (int) $showCodes + (int) $show('variant_value') + (int) $show('size') + (int) $show('color') + (int) $showCategory + (int) $show('brand') + (int) $show('unit') + (int) $show('cost') + (int) $show('mrp');
    $panelFilterCount = count(array_diff_key($activeFilters, ['sku' => true]));
    $decimals = currency_decimals();
@endphp
<div class="pdx" x-data="{ filtersOpen: $persist(false).as('product-list-filters-open') }">
    <x-product.list-premium />

    <div class="pdx-shell">
        <div class="pdx-head bg-light">
        {{-- Header --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-3 pt-3 pb-3">
            @can('product.create')
                <a class="btn btn-primary d-none d-md-inline-flex align-items-center shadow-sm" href="{{ route('product::create') }}">
                    <i class="fa fa-plus me-2"></i>Add New Product
                </a>
            @endcan
            <div class="btn-group shadow-sm d-none d-md-inline-flex">
                @can('product.export')
                    <button type="button" class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" wire:click="export()" wire:loading.attr="disabled" wire:target="export">
                        <i class="fa fa-file-excel-o me-lg-1 fs-5"></i><span class="hide-lg">Export</span>
                    </button>
                @endcan
                @can('product.import')
                    <a class="btn btn-info btn-sm d-flex align-items-center text-white" title="Import" href="{{ route('product::import') }}">
                        <i class="fa fa-cloud-download me-lg-1 fs-5"></i><span class="hide-lg">Import</span>
                    </a>
                @endcan
                @can('product.view')
                    <a class="btn btn-secondary btn-sm d-flex align-items-center text-white" title="Image Gallery" href="{{ route('product::gallery') }}">
                        <i class="fa fa-picture-o me-lg-1 fs-5"></i><span class="hide-lg">Gallery</span>
                    </a>
                @endcan
                @can('product offer.view')
                    <a class="btn btn-warning btn-sm d-flex align-items-center" title="Offer prices" href="{{ route('product::offer::index') }}">
                        <i class="fa fa-tags me-lg-1 fs-5"></i><span class="hide-lg">Offers</span>
                    </a>
                @endcan
            </div>
            <div class="ms-md-auto d-flex align-items-center gap-2">
                <span class="fw-semibold">Products</span>
                <span class="badge bg-primary rounded-pill">{{ number_format($data->total()) }}</span>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="toolbar d-flex flex-wrap align-items-center gap-2 px-3 pb-3">
            <div class="search-wrap" style="flex: 1 1 260px; max-width: 420px">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">
                        <i class="fa fa-search" wire:loading.remove wire:target="search"></i>
                        <i class="fa fa-spinner fa-spin" wire:loading wire:target="search"></i>
                    </span>
                    <input type="text" id="search" wire:model.live.debounce.300ms="search" placeholder="Search name, Arabic name, code, colour, price…" class="form-control tool-input" autocomplete="off"
                        aria-label="Search products">
                </div>
            </div>
            <div class="search-wrap" style="flex: 0 1 200px">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">
                        <i class="fa fa-barcode" wire:loading.remove wire:target="sku"></i>
                        <i class="fa fa-spinner fa-spin" wire:loading wire:target="sku"></i>
                    </span>
                    <input type="text" id="sku" wire:model.live.debounce.300ms="sku" placeholder="SKU code" class="form-control tool-input mono" autocomplete="off" aria-label="Search SKU code">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-ghost" :class="{ 'on': filtersOpen }" @click="filtersOpen = ! filtersOpen" :aria-expanded="filtersOpen" aria-controls="productFilterPanel">
                <i class="fa fa-sliders me-1"></i>Filters
                @if ($panelFilterCount)
                    <span class="badge rounded-pill text-bg-primary ms-1">{{ $panelFilterCount }}</span>
                @endif
            </button>
            @if (count($activeFilters) || trim((string) $search) !== '')
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearFilters()" title="Clear search and all filters">
                    <i class="fa fa-times me-1"></i>Clear
                </button>
            @endif
            <div class="seg hide-sm" role="group" aria-label="Row density">
                <button type="button" class="{{ $compact ? '' : 'on' }}" wire:click="$set('compact', false)">Comfort</button>
                <button type="button" class="{{ $compact ? 'on' : '' }}" wire:click="$set('compact', true)">Compact</button>
            </div>
            <div class="seg ms-auto" role="group" aria-label="Variant grouping">
                <button type="button" class="{{ $groupVariants ? 'on' : '' }}" wire:click="$set('groupVariants', true)" title="One row per product family">
                    <i class="fa fa-th-large me-1"></i><span class="hide-sm">Grouped</span>
                </button>
                <button type="button" class="{{ $groupVariants ? '' : 'on' }}" wire:click="$set('groupVariants', false)" title="Every size / colour on its own row">
                    <i class="fa fa-list me-1"></i><span class="hide-sm">Every variant</span>
                </button>
            </div>
<div class="dropdown">
                <button type="button" class="btn btn-sm btn-ghost d-flex align-items-center" wire:ignore.self data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Visible columns">
                    <i class="fa fa-columns"></i><span class="hide-sm ms-1">Columns</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 col-menu" wire:ignore.self>
                    <li class="dropdown-header small text-uppercase" style="font-size: .65rem; letter-spacing: .04em;">Visible Columns</li>
                    @foreach ($columnLabels as $column => $label)
                        <li wire:key="col-{{ $column }}">
                            <div class="form-check px-3 py-1 ms-2">
                                <input class="form-check-input" type="checkbox" wire:model.live="columns.{{ $column }}" id="product_col_{{ $column }}">
                                <label class="form-check-label small" for="product_col_{{ $column }}">{{ $label }}</label>
                            </div>
                        </li>
                    @endforeach
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button type="button" class="dropdown-item small text-muted d-inline-flex align-items-center gap-2" wire:click="resetColumns">
                            <i class="fa fa-undo"></i> Reset columns
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Filter panel (kept in the DOM so the TomSelects stay initialised) --}}
        <div class="filter-panel px-3 py-3" id="productFilterPanel" x-show="filtersOpen" x-cloak x-collapse>
            <div class="row g-2">
                <div class="col-6 col-md-4 col-xl-3" wire:ignore>
                    <label for="department_id" class="form-label">Department</label>
                    {{ html()->select('department_id', [])->value('')->class('select-department_id-list')->id('department_id')->placeholder('All departments') }}
                </div>
                <div class="col-6 col-md-4 col-xl-3" wire:ignore>
                    <label for="main_category_id" class="form-label">Main category</label>
                    {{ html()->select('main_category_id', [])->value('')->class('select-category_id-list')->id('main_category_id')->placeholder('All categories') }}
                </div>
                <div class="col-6 col-md-4 col-xl-3" wire:ignore>
                    <label for="sub_category_id" class="form-label">Sub category</label>
                    {{ html()->select('sub_category_id', [])->value('')->class('select-category_id-list')->id('sub_category_id')->placeholder('All sub categories') }}
                </div>
                <div class="col-6 col-md-4 col-xl-3" wire:ignore>
                    <label for="brand_id" class="form-label">Brand</label>
                    {{ html()->select('brand_id', [])->value('')->class('select-brand_id-list')->id('brand_id')->placeholder('All brands') }}
                </div>
                <div class="col-6 col-md-4 col-xl-3" wire:ignore>
                    <label for="unit_id" class="form-label">Unit</label>
                    {{ html()->select('unit_id', [])->value('')->class('select-unit_id-list')->id('unit_id')->placeholder('All units') }}
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" wire:model.live="status" class="form-select form-select-sm">
                        <option value="">Any status</option>
                        @foreach (activeOrDisabled() as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <label for="variant" class="form-label">Variant</label>
                    <select id="variant" wire:model.live="variant" class="form-select form-select-sm">
                        <option value="">All products</option>
                        <option value="main">Main products only</option>
                        <option value="variants">Variants only</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <label for="image_status" class="form-label">Image</label>
                    <select id="image_status" wire:model.live="image_status" class="form-select form-select-sm">
                        <option value="">All products</option>
                        <option value="with_image">With image</option>
                        <option value="without_image">Without image</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <label for="size" class="form-label">Size</label>
                    <select id="size" wire:model.live="size" class="form-select form-select-sm">
                        <option value="">All sizes</option>
                        @foreach ($this->sizeOptions as $sizeOption)
                            <option value="{{ $sizeOption }}">{{ $sizeOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-3">
                    <label for="color" class="form-label">Colour</label>
                    <select id="color" wire:model.live="color" class="form-select form-select-sm">
                        <option value="">All colours</option>
                        @foreach ($this->colourOptions as $colourValue => $colourName)
                            <option value="{{ $colourValue }}">{{ $colourName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl-3 d-flex align-items-end">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" id="is_selling" wire:model.live="is_selling">
                        <label for="is_selling" class="form-check-label small">Currently selling</label>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-3 d-flex align-items-end justify-content-end ms-auto">
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearFilters()" @disabled(! count($activeFilters) && trim((string) $search) === '')>
                        <i class="fa fa-eraser me-1"></i>Clear filters
                    </button>
                </div>
            </div>
        </div>

        {{-- Active filter chips --}}
        @if (count($activeFilters))
            <div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2">
                @foreach ($activeFilters as $filterKey => $chip)
                    <span class="fchip" wire:key="chip-{{ $filterKey }}">
                        {{ $chip['label'] }}: <b class="{{ $filterKey === 'sku' ? 'mono' : '' }}">{{ $chip['value'] }}</b>
                        <button type="button" wire:click="clearFilter('{{ $filterKey }}')" aria-label="Remove {{ $chip['label'] }} filter"><i class="fa fa-times"></i></button>
                    </span>
                @endforeach
                <a href="#" class="small text-body-secondary ms-1" wire:click.prevent="clearFilters()">Clear all</a>
            </div>
        @endif

        </div>

        {{-- List --}}
        <div class="table-responsive">
            <table class="ptable {{ $compact ? 'is-compact' : '' }}" wire:loading.delay.class="is-loading"
                wire:target="search,sku,status,variant,image_status,size,color,is_selling,unit_id,department_id,main_category_id,sub_category_id,brand_id,groupVariants,limit,sortBy,clearFilter,clearFilters,gotoPage,nextPage,previousPage">
                <thead>
                    <tr>
                        <th style="width: 1%">
                            <div class="d-flex align-items-center gap-2">
                                <input class="form-check-input m-0" type="checkbox" wire:model.live="selectAll" id="selectAll" aria-label="Select all" />
                                @if ($show('id'))
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                                @endif
                            </div>
                        </th>
                        @if ($show('image', false))
                            <th style="width: 1%"></th>
                        @endif
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Product" /></th>
                        @if ($showCodes)
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="{{ $show('code') ? 'code' : 'barcode' }}" label="{{ $show('code') && $show('barcode') ? 'SKU / Barcode' : ($show('code') ? 'SKU' : 'Barcode') }}" /></th>
                        @endif
                        @if ($show('variant_value'))
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="variant_value" label="Design" /></th>
                        @endif
                        @if ($show('size'))
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="size" label="Size" /></th>
                        @endif
                        @if ($show('color'))
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="color" label="Colour" /></th>
                        @endif
                        @if ($showCategory)
                            <th class="hide-md"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="{{ $show('department') ? 'department_id' : ($show('main_category') ? 'main_category_id' : 'sub_category_id') }}" label="Category" /></th>
                        @endif
                        @if ($show('brand'))
                            <th class="hide-md"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="brand_id" label="Brand" /></th>
                        @endif
                        @if ($show('unit'))
                            <th class="hide-lg"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="unit_id" label="Unit" /></th>
                        @endif
                        @if ($show('cost'))
                            <th class="num hide-sm"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="cost" label="Cost" /></th>
                        @endif
                        @if ($show('mrp'))
                            <th class="num"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="mrp" label="MRP" /></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        @php
                            $isSelected = in_array((string) $item->id, array_map('strval', $selected), true);
                            $hasFamily = $groupVariants && ($item->variants_count ?? 0) > 0;
                            $rowDesigns = $rowFacets[$item->id]['designs'];
                            $rowSizes = $rowFacets[$item->id]['sizes'];
                            $rowColours = $rowFacets[$item->id]['colours'];
                        @endphp
                        <tr class="row-main {{ $isSelected ? 'is-selected' : '' }}" wire:key="product-{{ $item->id }}">
                            <td class="c-check">
                                <div class="d-flex align-items-center gap-2">
                                    <input class="form-check-input m-0" type="checkbox" value="{{ $item->id }}" wire:model.live="selected" id="product-{{ $item->id }}" aria-label="Select {{ $item->name }}" />
                                    @if ($show('id'))
                                        <span class="bc hide-sm">#{{ $item->id }}</span>
                                    @endif
                                </div>
                            </td>
                            @if ($show('image', false))
                                <td class="c-thumb">
                                    @if ($item->thumbnail)
                                        <img src="{{ $item->thumbnail }}" alt="" class="thumb" loading="lazy" decoding="async" width="36" height="36">
                                    @else
                                        <span class="thumb"><i class="fa fa-picture-o"></i></span>
                                    @endif
                                </td>
                            @endif
                            <td class="c-product" style="min-width: 220px">
                                <a href="{{ route('product::edit', $item->id) }}" class="name">
                                    {{ $item->name }}@if (! $groupVariants && ($item->parent_id || ($item->variants_count ?? 0)))
                                        <span class="fw-normal text-body-secondary">— {{ $item->variantName() }}</span>
                                    @endif
                                </a>
                                @if (! $item->is_selling)
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">Not selling</span>
                                @endif
                                @if ($item->status === 'disabled')
                                    <span class="badge bg-danger-subtle text-danger-emphasis ms-1">Disabled</span>
                                @endif
                                @if ($show('name_arabic') && $item->name_arabic)
                                    <div class="ar" dir="rtl">{{ $item->name_arabic }}</div>
                                @endif
                                @if ($hasFamily)
                                    @php
                                        $isOpen = in_array($item->id, $openFamilies, true);
                                    @endphp
                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                        <button type="button" class="fam-toggle" wire:click="toggleFamily({{ $item->id }})" aria-expanded="{{ $isOpen ? 'true' : 'false' }}" title="Show / hide the variants">
                                            <i class="fa {{ $isOpen ? 'fa-chevron-down' : 'fa-chevron-right' }} me-1"></i>{{ $item->variants_count + 1 }} variants
                                        </button>
                                    </div>
                                @endif
                            </td>
                            @if ($showCodes)
                                <td class="c-codes">
                                    @if ($show('code'))
                                        @if ($item->code)
                                            <span class="badge bg-light text-dark border sku-badge"><i class="fa fa-barcode me-1 opacity-50"></i>{{ $item->code }}</span>
                                        @else
                                            <span class="bc">—</span>
                                        @endif
                                    @endif
                                    @if ($show('barcode'))
                                        <div class="bc {{ $show('code') ? 'mt-1' : '' }}">{{ $item->barcode ?: '—' }}</div>
                                    @endif
                                </td>
                            @endif
                            @if ($show('variant_value'))
                                <td class="c-design" title="{{ $item->optionName() }}">
                                    @forelse ($rowDesigns as $rowDesign)
                                        <span class="chip-size">{{ $rowDesign }}</span>
                                    @empty
                                        <span class="bc">—</span>
                                    @endforelse
                                </td>
                            @endif
                            @if ($show('size'))
                                <td class="c-size">
                                    @forelse ($rowSizes as $rowSize)
                                        <span class="chip-size">{{ $rowSize }}</span>
                                    @empty
                                        <span class="bc">—</span>
                                    @endforelse
                                </td>
                            @endif
                            @if ($show('color'))
                                <td class="c-colour">
                                    @if ($rowColours->count() === 1)
                                        <span class="d-inline-flex align-items-center gap-1 text-nowrap">
                                            <span class="sw" style="background: {{ \App\Support\ProductColours::hexFor($rowColours->first(), $colourPalette) }}"></span>
                                            <small class="text-body-secondary">{{ \App\Support\ProductColours::displayName($rowColours->first()) }}</small>
                                        </span>
                                    @elseif ($rowColours->isNotEmpty())
                                        <span class="d-inline-flex flex-wrap align-items-center gap-1">
                                            @foreach ($rowColours as $rowColour)
                                                <span class="sw" title="{{ \App\Support\ProductColours::displayName($rowColour) }}" style="background: {{ \App\Support\ProductColours::hexFor($rowColour, $colourPalette) }}"></span>
                                            @endforeach
                                        </span>
                                    @else
                                        <span class="bc">—</span>
                                    @endif
                                </td>
                            @endif
                            @if ($showCategory)
                                <td class="c-cat hide-md">
                                    <div class="crumb">
                                        @if ($show('department'))
                                            <span class="badge bg-light text-dark border"><i class="fa fa-building-o me-1 opacity-50"></i>{{ $item->department?->name ?? '—' }}</span>
                                        @endif
                                        @if ($show('main_category') && $item->mainCategory)
                                            @if ($show('department'))
                                                ·
                                            @endif
                                            {{ $item->mainCategory->name }}
                                        @endif
                                        @if ($show('sub_category') && $item->subCategory)
                                            › {{ $item->subCategory->name }}
                                        @endif
                                    </div>
                                </td>
                            @endif
                            @if ($show('brand'))
                                <td class="c-brand hide-md">
                                    @if ($item->brand)
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $item->brand->name }}</span>
                                    @else
                                        <span class="bc">—</span>
                                    @endif
                                </td>
                            @endif
                            @if ($show('unit'))
                                <td class="c-unit hide-lg small text-body-secondary">{{ $item->unit?->name ?? '—' }}</td>
                            @endif
                            @if ($show('cost'))
                                <td class="c-cost num hide-sm text-body-secondary">{{ currency($item->cost, $decimals) }}</td>
                            @endif
                            @if ($show('mrp'))
                                <td class="c-price num fw-semibold">
                                    {{ currency($item->mrp, $decimals) }}
                                    @if ($show('cost') && $item->mrp > 0)
                                        @php $margin = round((1 - $item->cost / $item->mrp) * 100); @endphp
                                        <div class="margin fw-normal hide-md {{ $margin < 0 ? 'text-danger' : 'text-success' }}">{{ $margin }}% margin</div>
                                    @endif
                                </td>
                            @endif
                        </tr>
                        @if ($hasFamily && in_array($item->id, $openFamilies, true))
                            <tr class="fam-row" wire:key="family-{{ $item->id }}">
                                <td colspan="{{ $columnCount }}">
                                    <div class="fam-inner">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Variant</th>
                                                    <th>SKU</th>
                                                    <th class="hide-sm">Barcode</th>
                                                    <th class="text-end hide-sm">Cost</th>
                                                    <th class="text-end">MRP</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach (collect([$item])->merge($item->variants) as $member)
                                                    <tr wire:key="family-{{ $item->id }}-{{ $member->id }}">
                                                        <td>
                                                            <a href="{{ route('product::edit', $member->id) }}" class="fw-semibold text-decoration-none">
                                                                @if (trim((string) $member->color) !== '')
                                                                    <span class="sw me-1" style="background: {{ \App\Support\ProductColours::hexFor($member->color, $colourPalette) }}"></span>
                                                                @endif
                                                                {{ $member->variantName() ?: $member->name }}
                                                            </a>
                                                            @if ($member->id === $item->id)
                                                                <span class="badge bg-primary-subtle text-primary-emphasis ms-1">Original</span>
                                                            @endif
                                                        </td>
                                                        <td><span class="sku">{{ $member->code ?: '—' }}</span></td>
                                                        <td class="hide-sm"><span class="bc">{{ $member->barcode ?: '—' }}</span></td>
                                                        <td class="text-end hide-sm">{{ currency($member->cost, $decimals) }}</td>
                                                        <td class="text-end fw-semibold">{{ currency($member->mrp, $decimals) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ $columnCount }}" class="empty">
                                <i class="fa fa-search d-block mb-3"></i>
                                <h6 class="fw-semibold mb-1 text-body">No products found</h6>
                                <p class="small mb-2">Try a different search or remove a filter.</p>
                                @if (count($activeFilters))
                                    <button type="button" class="btn btn-sm btn-ghost" wire:click="clearFilters()">Clear all filters</button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div class="pdx-foot d-flex flex-wrap align-items-center gap-2 px-3 py-2">
            <span>Showing <b class="text-body">{{ $data->firstItem() ?? 0 }}–{{ $data->lastItem() ?? 0 }}</b> of <b class="text-body">{{ number_format($data->total()) }}</b></span>
            <select wire:model.live="limit" class="form-select form-select-sm w-auto" aria-label="Rows per page">
                <option value="10">10 / page</option>
                <option value="50">50 / page</option>
                <option value="100">100 / page</option>
                <option value="500">500 / page</option>
            </select>
            <div class="ms-auto">{{ $data->links() }}</div>
        </div>
    </div>

    {{-- Mobile actions --}}
    <div class="d-block d-md-none position-fixed bottom-0 end-0 mb-4 me-4" style="z-index: 1050;">
        <div class="dropup">
            <button class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center" style="width: 52px; height: 52px" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                id="mobileActionButton" aria-label="Product actions">
                <i class="fa fa-plus fs-5"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="mobileActionButton">
                @can('product.create')
                    <li><a class="dropdown-item" href="{{ route('product::create') }}"><i class="fa fa-plus me-2 text-primary"></i>New product</a></li>
                @endcan
                @can('product.export')
                    <li><a class="dropdown-item" href="#" wire:click.prevent="export()"><i class="fa fa-file-excel-o me-2 text-success"></i>Export</a></li>
                @endcan
                @can('product.import')
                    <li><a class="dropdown-item" href="{{ route('product::import') }}"><i class="fa fa-cloud-upload me-2 text-info"></i>Import</a></li>
                @endcan
                @can('product.view')
                    <li><a class="dropdown-item" href="{{ route('product::gallery') }}"><i class="fa fa-picture-o me-2"></i>Image gallery</a></li>
                @endcan
                @can('product offer.view')
                    <li><a class="dropdown-item" href="{{ route('product::offer::index') }}"><i class="fa fa-tags me-2 text-warning"></i>Offers</a></li>
                @endcan
                @can('option group.view')
                    <li><a class="dropdown-item" href="{{ route('product::option_group::index') }}"><i class="fa fa-list-ul me-2 text-success"></i>Option groups</a></li>
                @endcan
            </ul>
        </div>
    </div>

    {{-- Bulk actions --}}
    @if (count($selected))
        <div class="product-bulk-bar" role="toolbar" aria-label="Bulk actions for selected products">
            <div class="d-flex align-items-center gap-2 bg-dark text-white rounded-pill shadow-lg px-3 py-2">
                <span class="badge bg-primary rounded-pill">{{ count($selected) }}</span>
                <span class="small fw-semibold text-nowrap me-1 d-none d-sm-inline">selected</span>
                <span class="vr opacity-25"></span>
                @can('product.delete')
                    <button type="button" class="btn btn-sm btn-danger rounded-pill d-flex align-items-center" wire:click="delete()"
                        wire:confirm="Are you sure you want to delete the {{ count($selected) }} selected items?" wire:loading.attr="disabled">
                        <i class="fa fa-trash me-1"></i>
                        <span class="d-none d-sm-inline">Delete</span>
                    </button>
                @endcan
                @can('product.edit')
                    <div class="dropup">
                        <button type="button" class="btn btn-sm btn-light rounded-pill d-flex align-items-center dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"
                            wire:loading.attr="disabled">
                            <i class="fa fa-shopping-cart me-1"></i>
                            <span class="d-none d-sm-inline">Sellable</span>
                        </button>
                        <ul class="dropdown-menu shadow-lg">
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="#" wire:click.prevent="updateSelling(true)">
                                    <i class="fa fa-check-circle me-2 text-success"></i>
                                    Mark as selling
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="#" wire:click.prevent="updateSelling(false)">
                                    <i class="fa fa-ban me-2 text-danger"></i>
                                    Mark as not selling
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="dropup">
                        <button type="button" class="btn btn-sm btn-light rounded-pill d-flex align-items-center dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"
                            wire:loading.attr="disabled">
                            <i class="fa fa-star me-1"></i>
                            <span class="d-none d-sm-inline">Favorite</span>
                        </button>
                        <ul class="dropdown-menu shadow-lg">
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="#" wire:click.prevent="updateFavorite(true)">
                                    <i class="fa fa-star me-2 text-warning"></i>
                                    Mark as favorite
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="#" wire:click.prevent="updateFavorite(false)">
                                    <i class="fa fa-star-o me-2 text-muted"></i>
                                    Remove from favorites
                                </a>
                            </li>
                        </ul>
                    </div>
                @endcan
                <button type="button" class="btn btn-sm btn-link text-white-50 p-1 ms-1" title="Clear selection" aria-label="Clear selection" wire:click="clearSelection()">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                ['unit_id', 'department_id', 'main_category_id', 'sub_category_id', 'brand_id'].forEach(function(field) {
                    $('#' + field).on('change', function() {
                        @this.set(field, $(this).val() || '');
                    });
                });
            });
            document.addEventListener('livewire:init', function() {
                Livewire.on('product-filters-cleared', function({ keys }) {
                    (keys || []).forEach(function(key) {
                        const el = document.getElementById(key);
                        if (el && el.tomselect) {
                            el.tomselect.clear(true);
                        }
                    });
                });
            });
        </script>
    @endpush
</div>
