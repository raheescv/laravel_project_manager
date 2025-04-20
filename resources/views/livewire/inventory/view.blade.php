<div>
    @if ($product->type == 'product')
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle text-capitalize table-sm">
                                <tr>
                                    <th>Department</th>
                                    <td>{{ $product->department?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Main Category</th>
                                    <td>{{ $product->mainCategory?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Sub Category</th>
                                    <td>{{ $product->subCategory?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Unit</th>
                                    <td>{{ $product->unit?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Location</th>
                                    <td>{{ $product->location }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>{{ $product->status }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle text-capitalize table-sm">
                                <tr>
                                    <th>MRP</th>
                                    <td> {{ currency($product->mrp) }} <br> </td>
                                </tr>
                                <tr>
                                    <th>Cost</th>
                                    <td> {{ currency($product->cost) }} <br> </td>
                                </tr>
                                <tr>
                                    <th>HSN Code</th>
                                    <td>{{ $product->hsn_code }}</td>
                                </tr>
                                <tr>
                                    <th>Barcode</th>
                                    <td>{{ $product->barcode }}</td>
                                </tr>
                                <tr>
                                    <th>Tax</th>
                                    <td>{{ $product->tax }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{{ $product->description }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle text-capitalize table-sm">
                                <tr>
                                    <th>Pattern</th>
                                    <td> {{ $product->pattern }} <br> </td>
                                </tr>
                                <tr>
                                    <th>Color</th>
                                    <td> {{ $product->color }} <br> </td>
                                </tr>
                                <tr>
                                    <th>Size</th>
                                    <td>{{ $product->size }}</td>
                                </tr>
                                <tr>
                                    <th>Model</th>
                                    <td>{{ $product->model }}</td>
                                </tr>
                                <tr>
                                    <th>Brand</th>
                                    <td>{{ $product->brand }}</td>
                                </tr>
                                <tr>
                                    <th>Part No</th>
                                    <td>{{ $product->part_no }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if ($product->type == 'service')
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle text-capitalize table-sm">
                                <tr>
                                    <th>Service</th>
                                    <td>
                                        {{ $product->name }} <br>
                                        @if ($product->name_arabic)
                                            <br>
                                            <span style="text-align: right; display: block;" dir="rtl">
                                                {{ $product->name_arabic }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Department</th>
                                    <td>{{ $product->department?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Main Category</th>
                                    <td>{{ $product->mainCategory?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Sub Category</th>
                                    <td>{{ $product->subCategory?->name }}</td>
                                </tr>
                                <tr>
                                    <th>Unit</th>
                                    <td>{{ $product->unit?->name }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle text-capitalize table-sm">
                                <tr>
                                    <th>Price</th>
                                    <td> {{ currency($product->mrp) }} <br> </td>
                                </tr>
                                <tr>
                                    <th>Time</th>
                                    <td> {{ $product->time }} <br> </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>{{ $product->status }}</td>
                                </tr>
                                <tr>
                                    <th>Favorite</th>
                                    <td>{{ $product->is_favorite ? 'Yes' : 'No' }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{{ $product->description }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header">
                    <h3>Inventory</h3>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="row ">
                                <input type="text" wire:model.live="search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle text-capitalize table-sm">
                            <thead>
                                <tr class="text-capitalize">
                                    <th> # </th>
                                    <th> Branch </th>
                                    <th> Barcode </th>
                                    <th> batch </th>
                                    <th class="text-end"> cost </th>
                                    <th class="text-end">
                                        @if ($product->type == 'product')
                                            quantity
                                        @else
                                            Used Count
                                        @endif
                                    </th>
                                    @if ($product->type == 'product')
                                        <th class="text-end">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->branch?->name }}</td>
                                        <td>{{ $item->barcode }}</td>
                                        <td>{{ $item->batch }}</td>
                                        <td class="text-end">{{ currency($item->cost) }}</td>
                                        <td class="text-end">
                                            @if ($product->type == 'product')
                                                {{ $item->quantity }}
                                            @else
                                                {{ abs($item->quantity) }}
                                            @endif
                                        </td>
                                        @if ($product->type == 'product')
                                            <td class="text-end">
                                                @can('inventory.edit')
                                                    <i table_id="{{ $item->id }}" class="demo-psi-pencil fs-5 me-2 pointer edit"></i>
                                                @endcan
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">Total</th>
                                    <th class="text-end">
                                        <b>
                                            @if ($product->type == 'product')
                                                {{ $data->sum('quantity') }}
                                            @else
                                                {{ abs($data->sum('quantity')) }}
                                            @endif
                                        </b>
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header">
                    <h3>Log</h3>
                    <div class="row">
                        <div class="col-lg-6">
                            <div wire:ignore>
                                {{ html()->select('branch_id', [auth()->user()->default_branch_id => auth()->user()->branch?->name])->value(auth()->user()->default_branch_id)->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row ">
                                <input type="text" wire:model.live="log_search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle text-capitalize table-sm">
                            <thead>
                                <tr class="text-capitalize">
                                    <th width="5%"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" /> </th>
                                    <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branch_id" label="Branch" /> </th>
                                    <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="barcode" label="barcode" /> </th>
                                    <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="batch" label="batch" /> </th>
                                    <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="cost" label="cost" /> </th>
                                    <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_in" label="In" /> </th>
                                    <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity_out" label="out" /> </th>
                                    <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="balance" /> </th>
                                    <th width="40%"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="remarks" label="remarks" /> </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logs as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->branch?->name }}</td>
                                        <td>{{ $item->barcode }}</td>
                                        <td>{{ $item->batch }}</td>
                                        <td class="text-end">{{ currency($item->cost) }}</td>
                                        <td class="text-end">{{ $item->quantity_in }}</td>
                                        <td class="text-end">{{ $item->quantity_out }}</td>
                                        <td class="text-end">
                                            @if ($product->type == 'product')
                                                {{ $item->balance }}
                                            @else
                                                {{ $item->balance * -1 }}
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                switch ($item->model) {
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("Inventory-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                window.addEventListener('RefreshInventoryTable', event => {
                    Livewire.dispatch("Inventory-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
