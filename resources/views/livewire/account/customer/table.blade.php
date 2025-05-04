<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('customer.create')
                    <button class="btn btn-primary hstack gap-2 align-self-center" id="CustomerAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </button>
                @endcan
                <div class="btn-group">
                    @can('customer.export')
                        <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                    @endcan
                    @can('customer.delete')
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
                    @can('customer.import')
                        <button class="btn btn-icon btn-outline-light" data-bs-toggle="modal" data-bs-target="#CustomerImportModal">
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
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="mobile" label="mobile" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="whatsapp_mobile" label="whatsapp mobile" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="email" label="email" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="dob" label="dob" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id_no" label="ID no" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="nationality" label="nationality" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="company" label="company" /> </th>
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
                            <td><a href="{{ route('account::customer::view', $item->id) }}">{{ $item->name }}</a> </td>
                            <td>{{ $item->mobile }}</td>
                            <td>{{ $item->whatsapp_mobile }}</td>
                            <td>{{ $item->email }}</td>
                            <td>{{ systemDate($item->dob) }}</td>
                            <td>{{ $item->id_no }}</td>
                            <td>{{ $item->nationality }}</td>
                            <td>{{ $item->company }}</td>
                            <td class="text-end">
                                @can('customer.edit')
                                    <i table_id="{{ $item->id }}" class="demo-psi-pencil fs-5 me-2 pointer edit"></i>
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
                    Livewire.dispatch("Customer-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                $('#CustomerAdd').click(function() {
                    Livewire.dispatch("Customer-Page-Create-Component");
                });
                window.addEventListener('RefreshCustomerTable', event => {
                    Livewire.dispatch("Customer-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
