<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" title="Export as Excel" wire:click="export()">
                        <i class="demo-pli-file-excel me-1"></i> Export
                    </button>
                </div>
            </div>
            <div class="col-md-8">
                <div class="d-flex gap-2 justify-content-md-end align-items-center">
                    <div class="form-group">
                        <select wire:model.live="limit" class="form-select form-select-sm">
                            <option value="10">10 rows</option>
                            <option value="100">100 rows</option>
                            <option value="500">500 rows</option>
                        </select>
                    </div>
                    <div class="form-group" style="width: 250px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0">
                                <i class="demo-pli-magnifi-glass"></i>
                            </span>
                            <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search..." autofocus>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="demo-pli-layout-grid me-1"></i> Columns
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm p-2">
                            @foreach ($columnDefinitions as $columnKey => $columnLabel)
                                <li class="dropdown-item p-0 mb-1">
                                    <label class="d-flex align-items-center w-100 px-2 py-1 cursor-pointer">
                                        <input type="checkbox" class="form-check-input me-2" wire:model.live="inventory_log_visible_column.{{ $columnKey }}">
                                        {{ $columnLabel }}
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <hr class="mt-3 mb-0">
        {{-- filter area --}}
        <div class="col-12 mt-3">
            <div class="bg-light rounded-3 border shadow-sm">
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="from_date">
                                    <i class="demo-psi-calendar-4 me-1"></i> From Date
                                </label>
                                {{ html()->date('from_date')->value('')->class('form-control form-control-sm')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="to_date">
                                    <i class="demo-psi-calendar-4 me-1"></i> To Date
                                </label>
                                {{ html()->date('to_date')->value('')->class('form-control form-control-sm')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                            </div>
                        </div>
                        <div class="col-md-2" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="branch_id">
                                    <i class="demo-psi-home me-1"></i> Branch
                                </label>
                                {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All Branches') }}
                            </div>
                        </div>
                        <div class="col-md-6" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="product_id">
                                    <i class="demo-pli-cart me-1"></i> Product
                                </label>
                                {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->attribute('type', 'product')->id('product_id')->placeholder('All Products') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body px-0 pb-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle mb-0 border-bottom">
                <thead class="bg-light text-nowrap">
                    <tr class="text-capitalize">
                        @if ($inventory_log_visible_column['id'] ?? true)
                            <th class="ps-3"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventory_logs.id" label="#" /> </th>
                        @endif
                        @if ($inventory_log_visible_column['date'] ?? true)
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventory_logs.created_at" label="Date" /> </th>
                        @endif
                        @if ($inventory_log_visible_column['branch'] ?? true)
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branch_id" label="Branch" /> </th>
                        @endif
                        @if ($inventory_log_visible_column['department'] ?? true)
                            <th>Department</th>
                        @endif
                        @if ($inventory_log_visible_column['main_category'] ?? true)
                            <th>Main Category</th>
                        @endif
                        @if ($inventory_log_visible_column['product'] ?? true)
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.name" label="Product" /> </th>
                        @endif
                        @if ($inventory_log_visible_column['barcode'] ?? true)
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="barcode" label="Barcode" /> </th>
                        @endif
                        @if ($inventory_log_visible_column['batch'] ?? true)
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="batch" label="Batch" /> </th>
                        @endif
                        @if ($inventory_log_visible_column['quantity_in'] ?? true)
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_in" label="In" /> </th>
                        @endif
                        @if ($inventory_log_visible_column['quantity_out'] ?? true)
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_out" label="Out" /> </th>
                        @endif
                        @if ($inventory_log_visible_column['balance'] ?? true)
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="Balance" /> </th>
                        @endif
                        @if ($inventory_log_visible_column['remarks'] ?? true)
                            <th width="30%"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="remarks" label="Remarks" /> </th>
                        @endif
                        @if ($inventory_log_visible_column['user'] ?? true)
                            <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="user_name" label="User" /> </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            @if ($inventory_log_visible_column['id'] ?? true)
                                <td class="ps-3">{{ $item->id }}</td>
                            @endif
                            @if ($inventory_log_visible_column['date'] ?? true)
                                <td>{{ systemDateTime($item->created_at) }}</td>
                            @endif
                            @if ($inventory_log_visible_column['branch'] ?? true)
                                <td>{{ $item->branch?->name }}</td>
                            @endif
                            @if ($inventory_log_visible_column['department'] ?? true)
                                <td>{{ $item->product?->department?->name }}</td>
                            @endif
                            @if ($inventory_log_visible_column['main_category'] ?? true)
                                <td>{{ $item->product?->mainCategory?->name }}</td>
                            @endif
                            @if ($inventory_log_visible_column['product'] ?? true)
                                <td> <a href="{{ route('inventory::product::view', $item->product_id) }}">{{ $item->product?->name }}</a> </td>
                            @endif
                            @if ($inventory_log_visible_column['barcode'] ?? true)
                                <td>{{ $item->barcode }}</td>
                            @endif
                            @if ($inventory_log_visible_column['batch'] ?? true)
                                <td>{{ $item->batch }}</td>
                            @endif
                            @if ($inventory_log_visible_column['quantity_in'] ?? true)
                                <td class="text-end">{{ $item->quantity_in }}</td>
                            @endif
                            @if ($inventory_log_visible_column['quantity_out'] ?? true)
                                <td class="text-end">{{ $item->quantity_out }}</td>
                            @endif
                            @if ($inventory_log_visible_column['balance'] ?? true)
                                <td class="text-end">{{ $item->balance }}</td>
                            @endif
                            @if ($inventory_log_visible_column['remarks'] ?? true)
                                <td>
                                    @php
                                        switch ($item->model ?? '') {
                                            case 'Sale':
                                                $href = route('sale::view', $item->model_id);
                                                break;
                                            case 'SaleReturn':
                                                $href = route('sale_return::view', $item->model_id);
                                                break;
                                            default:
                                                $href = '';
                                                break;
                                        }
                                    @endphp
                                    @if ($href)
                                        <a href="{{ $href }}">{{ $item->remarks }}</a>
                                    @else
                                        {{ $item->remarks }}
                                    @endif
                                </td>
                            @endif
                            @if ($inventory_log_visible_column['user'] ?? true)
                                <td>{{ $item->user_name }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#product_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('product_id', value);
                });
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
            });
        </script>
        <style>
            .cursor-pointer {
                cursor: pointer;
            }
        </style>
    @endpush
</div>
