<div>
    @push('styles')
        <style>
            .pv-img-main { height: 280px; object-fit: contain; background: #f1f5f9; cursor: zoom-in; }
            .pv-thumb { width: 56px; height: 56px; object-fit: cover; cursor: pointer; border: 2px solid transparent; opacity: .65; transition: all .2s; }
            .pv-thumb:hover, .pv-thumb.active { border-color: #4f46e5; opacity: 1; }
            .pv-label { font-size: .8rem; color: #64748b; }
            .pv-value { font-size: .8rem; color: #1e293b; font-weight: 600; }
            .pv-value.text-price { color: #4f46e5; font-size: .9rem; }
            .pv-divider { border-color: #f1f5f9; }
            .pv-icon { width: 26px; height: 26px; font-size: .7rem; }
            .pv-section-title { font-size: .75rem; font-weight: 700; letter-spacing: .06em; }
            .pv-status { font-size: .75rem; }
            .pv-act-btn { width: 30px; height: 30px; font-size: .75rem; }
            .pv-gallery-img { height: 140px; object-fit: cover; }
            .pv-360-img { height: 80px; object-fit: cover; }
            .nav-pv .nav-link { color: #64748b; font-size: .85rem; border: none; border-bottom: 2px solid transparent; border-radius: 0; padding: .75rem 1.25rem; }
            .nav-pv .nav-link:hover { color: #4f46e5; background: #eef2ff; }
            .nav-pv .nav-link.active { color: #4f46e5; border-bottom-color: #4f46e5; font-weight: 600; background: transparent; }
            .table-pv th { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; font-weight: 600; }
            .table-pv td { font-size: .82rem; vertical-align: middle; }
            .table-pv tfoot th { background: #eef2ff; color: #4f46e5; }
        </style>
    @endpush

    @if ($product->type == 'product')
        {{-- Thumbnail + Summary Bar --}}
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    @if ($product->thumbnail)
                        <img src="{{ url($product->thumbnail) }}" alt="{{ $product->name }}" class="rounded-3 pv-thumb-click" role="button"
                             data-bs-toggle="modal" data-bs-target="#imagePreviewModal" data-img="{{ url($product->thumbnail) }}"
                             style="width:80px; height:80px; object-fit:cover; cursor:pointer;" title="Click to enlarge">
                    @else
                        <div class="rounded-3 bg-light d-flex align-items-center justify-content-center" style="width:80px; height:80px;">
                            <i class="fa fa-image fa-2x text-muted opacity-25"></i>
                        </div>
                    @endif
                    <div class="flex-grow-1">
                        <h5 class="mb-1 fw-bold">{{ $product->name }}</h5>
                        @if ($product->name_arabic)
                            <p class="mb-0 text-muted small" dir="rtl">{{ $product->name_arabic }}</p>
                        @endif
                        <div class="d-flex gap-2 mt-1 flex-wrap">
                            <span class="badge rounded-pill {{ $product->status == 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                <i class="fa fa-circle me-1" style="font-size:.35rem; vertical-align:middle;"></i>{{ ucfirst($product->status) }}
                            </span>
                            @if ($product->barcode)
                                <span class="badge bg-light text-dark border"><i class="fa fa-barcode me-1"></i>{{ $product->barcode }}</span>
                            @endif
                            @if ($product->document_file)
                                <a href="{{ url($product->document_file) }}" target="_blank" class="badge bg-info-subtle text-info border-0 text-decoration-none" title="View {{ $product->document_file_name }}">
                                    <i class="fa fa-eye me-1"></i>{{ $product->document_file_name ?: 'Document' }}
                                </a>
                                <a href="{{ route('product::download-document', $product->id) }}" class="badge bg-primary-subtle text-primary border-0 text-decoration-none" title="Download {{ $product->document_file_name }}">
                                    <i class="fa fa-download me-1"></i>Download
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-4 text-center">
                        <div>
                            <small class="text-secondary d-block">Total Stock</small>
                            <span class="fw-bold fs-5 text-primary">{{ $data->sum('quantity') }}</span>
                            <small class="text-muted">{{ $product->unit?->name ?? 'pcs' }}</small>
                        </div>
                        <div>
                            <small class="text-secondary d-block">Stock Value</small>
                            <span class="fw-bold fs-5 text-success">{{ currency($data->sum('total')) }}</span>
                        </div>
                        <div>
                            <small class="text-secondary d-block">MRP</small>
                            <span class="fw-bold fs-5 text-primary">{{ currency($product->mrp) }}</span>
                        </div>
                        <div>
                            <small class="text-secondary d-block">Cost</small>
                            <span class="fw-bold fs-5 text-dark">{{ currency($product->cost) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Image Preview Modal --}}
        <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden bg-transparent">
                    <div class="position-relative">
                        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal" aria-label="Close"></button>
                        <img id="imagePreviewImg" src="" alt="Preview" class="w-100 rounded-4" style="max-height:80vh; object-fit:contain; background:#000;">
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Cards --}}
        <div class="row g-3 mb-4">
            @php
                $sections = [
                    ['title' => 'Basic Info', 'icon' => 'fa-info-circle', 'bg' => 'bg-primary bg-opacity-10', 'fg' => 'text-primary', 'items' => [
                        ['Department', $product->department?->name],
                        ['Main Category', $product->mainCategory?->name],
                        ['Sub Category', $product->subCategory?->name],
                        ['Unit', $product->unit?->name],
                        ['Location', $product->location],
                    ]],
                    ['title' => 'Pricing', 'icon' => 'fa-tag', 'bg' => 'bg-success bg-opacity-10', 'fg' => 'text-success', 'items' => [
                        ['MRP', currency($product->mrp), true],
                        ['Cost', currency($product->cost), true],
                        ['HSN Code', $product->hsn_code],
                        ['Tax', $product->tax],
                        ['Description', $product->description],
                    ]],
                    ['title' => 'Specifications', 'icon' => 'fa-cog', 'bg' => 'bg-warning bg-opacity-10', 'fg' => 'text-warning', 'items' => [
                        ['Pattern', $product->pattern],
                        ['Color', $product->color],
                        ['Size', $product->size],
                        ['Model', $product->model],
                        ['Brand', $product->brand?->name],
                        ['Part No', $product->part_no],
                    ]],
                ];
            @endphp

            @foreach ($sections as $section)
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-transparent border-bottom py-3 px-3">
                            <h6 class="pv-section-title text-uppercase text-secondary mb-0 d-flex align-items-center gap-2">
                                <span class="pv-icon rounded d-inline-flex align-items-center justify-content-center {{ $section['bg'] }}">
                                    <i class="fa {{ $section['icon'] }} {{ $section['fg'] }}"></i>
                                </span>
                                {{ $section['title'] }}
                            </h6>
                        </div>
                        <div class="card-body px-3 py-2">
                            @foreach ($section['items'] as $item)
                                <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom pv-divider' : '' }}">
                                    <span class="pv-label">{{ $item[0] }}</span>
                                    <span class="pv-value {{ ($item[2] ?? false) ? 'text-price' : '' }} text-capitalize text-end">{{ $item[1] ?: '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($product->type == 'service')
        <div class="row g-3 mb-4">
            @php
                $serviceSections = [
                    ['title' => 'Service Details', 'icon' => 'fa-concierge-bell', 'bg' => 'bg-primary bg-opacity-10', 'fg' => 'text-primary', 'items' => [
                        ['Service', $product->name . ($product->name_arabic ? ' / ' . $product->name_arabic : '')],
                        ['Department', $product->department?->name],
                        ['Main Category', $product->mainCategory?->name],
                        ['Sub Category', $product->subCategory?->name],
                        ['Unit', $product->unit?->name],
                    ]],
                    ['title' => 'Pricing', 'icon' => 'fa-tag', 'bg' => 'bg-success bg-opacity-10', 'fg' => 'text-success', 'items' => [
                        ['Price', currency($product->mrp), true],
                        ['Time', $product->time],
                        ['Status', $product->status],
                        ['Favorite', $product->is_favorite ? 'Yes' : 'No'],
                        ['Description', $product->description],
                    ]],
                ];
            @endphp
            @foreach ($serviceSections as $section)
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-transparent border-bottom py-3 px-3">
                            <h6 class="pv-section-title text-uppercase text-secondary mb-0 d-flex align-items-center gap-2">
                                <span class="pv-icon rounded d-inline-flex align-items-center justify-content-center {{ $section['bg'] }}">
                                    <i class="fa {{ $section['icon'] }} {{ $section['fg'] }}"></i>
                                </span>
                                {{ $section['title'] }}
                            </h6>
                        </div>
                        <div class="card-body px-3 py-2">
                            @foreach ($section['items'] as $item)
                                <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom pv-divider' : '' }}">
                                    <span class="pv-label">{{ $item[0] }}</span>
                                    <span class="pv-value {{ ($item[2] ?? false) ? 'text-price' : '' }} text-capitalize text-end">{{ $item[1] ?: '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Inventory Table --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-transparent border-bottom py-3 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-bold"><i class="fa fa-boxes-stacked me-2 text-primary"></i>Inventory</h6>
                <div class="d-flex gap-2">
                    <select wire:model.live="inventory_filter" class="form-select form-select-sm rounded-3" style="width:150px;">
                        <option value="all">All Inventory</option>
                        <option value="main">Main Only</option>
                        <option value="employee">Employee Only</option>
                    </select>
                    <input type="text" wire:model.live="search" placeholder="Search..." class="form-control form-control-sm rounded-3" autocomplete="off" style="width:180px;">
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-pv align-middle text-capitalize mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Branch</th>
                        <th>Employee</th>
                        <th>Barcode</th>
                        <th>Batch</th>
                        <th class="text-end">Cost</th>
                        <th class="text-end">
                            @if ($product->type == 'product')
                                Qty ({{ $product->unit?->name ?? 'Base Unit' }})
                            @else
                                Used Count
                            @endif
                        </th>
                        @if ($product->type == 'product')
                            <th class="text-end">Total</th>
                            <th class="text-end">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>
                                @if ($product->type == 'product' && $product->units->count() > 0)
                                    <button class="btn btn-sm btn-link p-0 me-1" type="button" data-bs-toggle="collapse" data-bs-target="#pu{{ $item->id }}" aria-expanded="false">
                                        <i class="fa fa-chevron-down" style="font-size:.65rem;"></i>
                                    </button>
                                @endif
                                {{ $item->id }}
                            </td>
                            <td>{{ $item->branch?->name }}</td>
                            <td>
                                {{ $item->employee?->name }}
                                @if ($item->employee_id)
                                    <a href="{{ route('users::employee::view', $item->employee_id) }}" class="ms-1 text-primary"><i class="fa fa-user" style="font-size:.75rem;"></i></a>
                                @endif
                            </td>
                            <td>
                                {{ $item->barcode }}
                                <a href="{{ route('inventory::barcode::print', ['type' => 'inventory', 'id' => $item->id]) }}" class="ms-1 text-muted"><i class="fa fa-print" style="font-size:.75rem;"></i></a>
                            </td>
                            <td>{{ $item->batch }}</td>
                            <td class="text-end">{{ currency($item->cost) }}</td>
                            <td class="text-end fw-semibold">{{ $product->type == 'product' ? $item->quantity : abs($item->quantity) }}</td>
                            @if ($product->type == 'product')
                                <td class="text-end fw-semibold">{{ currency($item->total) }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        @can('inventory.edit')
                                            <button class="btn btn-sm btn-outline-primary pv-act-btn rounded-2 d-inline-flex align-items-center justify-content-center"
                                                    wire:click="$dispatch('Inventory-Page-Update-Component', {id: {{ $item->id }}})" title="Edit">
                                                <i class="demo-psi-pencil"></i>
                                            </button>
                                        @endcan
                                        @can('inventory.transfer')
                                            @if ($item->employee_id)
                                                <button class="btn btn-sm btn-outline-warning pv-act-btn rounded-2 d-inline-flex align-items-center justify-content-center"
                                                        wire:click="$dispatch('EmployeeInventory-Transfer-Component', {inventoryId: {{ $item->id }}, type: 'return'})"
                                                        title="Return" @if ($item->quantity <= 0) disabled @endif>
                                                    <i class="fa fa-share"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline-success pv-act-btn rounded-2 d-inline-flex align-items-center justify-content-center"
                                                        wire:click="$dispatch('EmployeeInventory-Transfer-Component', {inventoryId: {{ $item->id }}, type: 'transfer'})"
                                                        title="Transfer" @if ($item->quantity <= 0) disabled @endif>
                                                    <i class="fa fa-user"></i>
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            @endif
                        </tr>
                        @if ($product->type == 'product' && $product->units->count() > 0)
                            <tr class="collapse" id="pu{{ $item->id }}">
                                <td colspan="9" class="p-0 border-0">
                                    <div class="bg-light p-3 mx-3 mb-2 rounded-3">
                                        <small class="fw-bold text-uppercase text-secondary d-block mb-2" style="letter-spacing:.04em;">Product Units</small>
                                        <table class="table table-sm table-bordered mb-0 w-50 mx-auto" style="font-size:.8rem;">
                                            <thead class="table-light">
                                                <tr><th>Unit</th><th class="text-end">Factor</th><th class="text-end">Qty</th><th>Barcode</th></tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($product->units as $pu)
                                                    <tr>
                                                        <td>{{ $pu->subUnit?->name ?? 'N/A' }}</td>
                                                        <td class="text-end">{{ number_format($pu->conversion_factor, 3) }}</td>
                                                        <td class="text-end">{{ $item->quantity > 0 ? number_format($item->quantity / $pu->conversion_factor, 3) : number_format(0, 3) }}</td>
                                                        <td>{{ $pu->barcode ?? 'N/A' }} <a href="{{ route('inventory::barcode::print', ['type' => 'product_unit', 'id' => $pu->id]) }}"><i class="fa fa-print ms-1"></i></a></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" class="text-end">Total</th>
                        <th class="text-end fw-bold">{{ $product->type == 'product' ? $data->sum('quantity') : abs($data->sum('quantity')) }}</th>
                        @if ($product->type == 'product')
                            <th class="text-end fw-bold">{{ currency($data->sum('total')) }}</th>
                            <th></th>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Chart --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-transparent border-bottom py-3 px-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa fa-chart-line me-2 text-primary"></i>Inventory Movement</h6>
                <div class="btn-group btn-group-sm" role="group" x-data="{
                    view: @js($chartView),
                    toggle(v) { this.view = v; window.dispatchEvent(new CustomEvent('chart-view-changed', { detail: v })); }
                }">
                    <button type="button" class="btn rounded-start-3" :class="view === 'monthly' ? 'btn-primary' : 'btn-outline-primary'" @click="toggle('monthly')" :disabled="view === 'monthly'">Monthly</button>
                    <button type="button" class="btn rounded-end-3" :class="view === 'daily' ? 'btn-primary' : 'btn-outline-primary'" @click="toggle('daily')" :disabled="view === 'daily'">Daily</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div wire:ignore style="position:relative; height:280px;"><canvas id="inventoryChart"></canvas></div>
        </div>
    </div>

    {{-- Tabs: Log & Images --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-transparent border-bottom pb-0 px-3">
            <ul class="nav nav-pv" role="tablist">
                <li class="nav-item"><button class="nav-link @if ($selectedTab == 'log') active @endif" wire:click="tabSelect('log')" type="button"><i class="fa fa-history me-1"></i>Log</button></li>
                <li class="nav-item"><button class="nav-link @if ($selectedTab == 'image') active @endif" wire:click="tabSelect('image')" type="button"><i class="fa fa-image me-1"></i>Images</button></li>
            </ul>
        </div>
        <div class="card-body p-3">
            {{-- Log Tab --}}
            <div class="@if ($selectedTab != 'log') d-none @endif">
                <div class="row g-2 mb-3">
                    <div class="col-lg-4"><div wire:ignore>{{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}</div></div>
                    <div class="col-lg-4"><div wire:ignore>{{ html()->select('employee_id', [])->value('')->class('select-employee_id-list')->id('employee_id')->placeholder('All Employees') }}</div></div>
                    <div class="col-lg-4"><input type="text" wire:model.live="log_search" autofocus placeholder="Search logs..." class="form-control form-control-sm rounded-3" autocomplete="off"></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm table-pv align-middle text-capitalize mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="4%"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" /></th>
                                <th class="text-nowrap"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="Date" /></th>
                                <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branch_id" label="Branch" /></th>
                                <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="employee_id" label="Employee" /></th>
                                <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="barcode" label="Barcode" /></th>
                                <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="batch" label="Batch" /></th>
                                <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="cost" label="Cost" /></th>
                                <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_in" label="In" /></th>
                                <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_out" label="Out" /></th>
                                <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="Balance" /></th>
                                <th width="30%"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="remarks" label="Remarks" /></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td class="text-nowrap">{{ systemDateTime($item->created_at) }}</td>
                                    <td>{{ $item->branch?->name }}</td>
                                    <td>{{ $item->employee?->name }}</td>
                                    <td>{{ $item->barcode }}</td>
                                    <td>{{ $item->batch }}</td>
                                    <td class="text-end">{{ currency($item->cost) }}</td>
                                    <td class="text-end">{{ $item->quantity_in }}</td>
                                    <td class="text-end">{{ $item->quantity_out }}</td>
                                    <td class="text-end">{{ $product->type == 'product' ? $item->balance : $item->balance * -1 }}</td>
                                    <td>
                                        @php
                                            $href = match($item->model) {
                                                'Sale' => route('sale::view', $item->model_id),
                                                'SaleReturn' => route('sale_return::view', $item->model_id),
                                                'InventoryTransfer' => route('inventory::transfer::view', $item->model_id),
                                                'Purchase' => route('purchase::edit', $item->model_id),
                                                default => '',
                                            };
                                        @endphp
                                        @if ($href)
                                            <a href="{{ $href }}" class="text-primary">{{ $item->remarks }}</a>
                                        @else
                                            {{ $item->remarks }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $logs->links() }}</div>
            </div>

            {{-- Images Tab --}}
            <div class="@if ($selectedTab != 'image') d-none @endif">
                <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-3">
                    @if ($product->thumbnail)
                        <div class="col">
                            <div class="card border shadow-sm rounded-3 overflow-hidden h-100">
                                <img src="{{ url($product->thumbnail) }}" class="pv-gallery-img" alt="Thumbnail" role="button"
                                     data-bs-toggle="modal" data-bs-target="#imagePreviewModal" data-img="{{ url($product->thumbnail) }}" style="cursor:pointer;">
                                <div class="card-footer bg-light text-center py-1"><small class="text-muted">Thumbnail</small></div>
                            </div>
                        </div>
                    @endif
                    @foreach ($product->images as $image)
                        <div class="col">
                            <div class="card border shadow-sm rounded-3 overflow-hidden h-100">
                                <img src="{{ url($image->path) }}" class="pv-gallery-img" alt="{{ $image->name }}" role="button"
                                     data-bs-toggle="modal" data-bs-target="#imagePreviewModal" data-img="{{ url($image->path) }}" style="cursor:pointer;">
                                <div class="card-footer bg-light text-center py-1"><small class="text-muted">{{ $image->name ?: 'Image' }}</small></div>
                            </div>
                        </div>
                    @endforeach
                    @if (!$product->thumbnail && count($product->images) == 0)
                        <div class="col-12 text-center py-5">
                            <i class="fa fa-image fa-3x text-muted opacity-25 d-block mb-2"></i>
                            <small class="text-muted">No images available</small>
                        </div>
                    @endif
                </div>

                @if ($product->angleImages()->count() > 0)
                    <hr class="my-4">
                    <h6 class="pv-section-title text-uppercase text-secondary mb-3"><i class="fa fa-rotate me-1"></i> 360° View</h6>
                    <div class="row row-cols-3 row-cols-md-5 row-cols-lg-8 g-2">
                        @foreach ($product->angleImages()->orderedByAngle()->get() as $image)
                            <div class="col">
                                <div class="card border rounded-3 overflow-hidden text-center shadow-sm">
                                    <img src="{{ url($image->path) }}" class="pv-360-img" alt="{{ $image->degree }}°">
                                    <small class="bg-light py-1 text-muted fw-semibold">{{ $image->degree }}°</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/chart.js/chartjs-plugin-datalabels@2.min.js') }}"></script>
        <script>
            Chart.register(ChartDataLabels);
            let inventoryChart = null;

            function createChart(chartData, labels, currentView) {
                const ctx = document.getElementById('inventoryChart').getContext('2d');
                if (inventoryChart) inventoryChart.destroy();

                inventoryChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Quantity In', data: chartData.map(i => i.total_in), borderColor: 'rgb(75, 192, 192)', backgroundColor: 'rgba(75, 192, 192, 0.1)', tension: 0.3, fill: false },
                            { label: 'Quantity Out', data: chartData.map(i => i.total_out), borderColor: 'rgb(255, 99, 132)', backgroundColor: 'rgba(255, 99, 132, 0.1)', tension: 0.3, fill: false }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { display: true, position: 'top' },
                            title: { display: true, text: currentView === 'monthly' ? 'Monthly Inventory Movement (Last 1 Year)' : 'Daily Inventory Movement (Last 30 Days)' },
                            datalabels: { display: true, color: 'black', align: 'top', formatter: v => v.toLocaleString() }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: v => v.toLocaleString() } },
                            x: { grid: { display: false } }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
                return inventoryChart;
            }

            document.addEventListener('livewire:initialized', () => {
                const monthlyData = @json($monthly_summary), dailyData = @json($daily_summary);
                let currentView = @json($chartView);
                const getData = v => v === 'monthly' ? monthlyData : dailyData;
                const getLabels = (d, v) => d.map(i => v === 'monthly' ? i.month_name : i.day_name);

                inventoryChart = createChart(getData(currentView), getLabels(getData(currentView), currentView), currentView);

                window.addEventListener('chart-view-changed', e => {
                    currentView = e.detail;
                    if (inventoryChart) inventoryChart.destroy();
                    inventoryChart = createChart(getData(currentView), getLabels(getData(currentView), currentView), currentView);
                });

                Livewire.on('chartViewUpdated', view => {
                    currentView = view;
                    if (inventoryChart) inventoryChart.destroy();
                    inventoryChart = createChart(getData(currentView), getLabels(getData(currentView), currentView), currentView);
                });
            });

            $('#branch_id').on('change', function() { @this.set('branch_id', $(this).val() || null); });
            $('#employee_id').on('change', function() { @this.set('employee_id', $(this).val() || null); });
            window.addEventListener('RefreshInventoryTable', () => Livewire.dispatch("Inventory-Refresh-Component"));

            // Image preview modal
            const previewModal = document.getElementById('imagePreviewModal');
            if (previewModal) {
                previewModal.addEventListener('show.bs.modal', function(e) {
                    const trigger = e.relatedTarget;
                    const imgSrc = trigger?.getAttribute('data-img') || trigger?.src || '';
                    document.getElementById('imagePreviewImg').src = imgSrc;
                });
                previewModal.addEventListener('hidden.bs.modal', function() {
                    document.getElementById('imagePreviewImg').src = '';
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const t = document.querySelector(this.getAttribute('data-bs-target'));
                        if (t) this.setAttribute('aria-expanded', this.getAttribute('aria-expanded') !== 'true');
                    });
                });
            });
        </script>
    @endpush
</div>
