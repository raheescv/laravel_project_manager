<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('vendor.create')
                    <button class="btn btn-primary hstack gap-2 align-self-center" id="VendorAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </button>
                @endcan
                <div class="btn-group">
                    @can('vendor.export')
                        <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                    @endcan
                    @can('vendor.delete')
                        <button class="btn btn-icon btn-outline-light" title="To delete the selected items" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
            <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                <div class="form-group">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" wire:model.live="search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
                <div class="btn-group">
                    @can('vendor.import')
                        <button class="btn btn-icon btn-outline-light" data-bs-toggle="modal" data-bs-target="#VendorImportModal">
                            <i class="demo-pli-download-from-cloud fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th>
                            <input type="checkbox" wire:model.live="selectAll" />
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="id" />
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="name" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="vendor_type" label="type" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="mobile" label="mobile" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="email" label="email" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="place" label="place" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="cr_number" label="CR / tax no." /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit_period_days" label="credit period days" /> </th>
                        <th class="text-end"> Action </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" />
                                {{ $item->id }}
                            </td>
                            <td>
                                {{ $item->name }}
                                @if ($item->alias_name)
                                    <div class="small text-muted" dir="rtl">{{ $item->alias_name }}</div>
                                @endif
                            </td>
                            <td>{{ \App\Enums\Account\VendorType::tryFrom((string) $item->vendor_type)?->label() ?? '-' }}</td>
                            <td>{{ $item->mobile }}</td>
                            <td>{{ $item->email }}</td>
                            <td>{{ $item->place }}</td>
                            <td>{{ $item->cr_number ?: ($item->tax_no ?: '-') }}</td>
                            <td class="text-end">{{ $item->credit_period_days ?? '-' }}</td>
                            <td class="text-end">
                                @can('vendor.edit')
                                    <i table_id="{{ $item->id }}" class="demo-psi-pencil fs-5 me-2 pointer edit" title="Edit vendor"></i>
                                @endcan
                                @can('vendor.delete')
                                    <i class="fa fa-trash-o fs-5 pointer text-danger" title="Delete vendor" wire:click="deleteOne({{ $item->id }})" wire:confirm="Delete {{ $item->name }}? Vendors with contracts or ledger entries are kept."></i>
                                @endcan
                            </td>
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
                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("Vendor-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                $('#VendorAdd').click(function() {
                    Livewire.dispatch("Vendor-Page-Create-Component");
                });
                window.addEventListener('RefreshVendorTable', event => {
                    Livewire.dispatch("Vendor-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
