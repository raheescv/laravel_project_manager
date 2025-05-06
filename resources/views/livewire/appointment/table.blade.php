<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    @can('appointment.export')
                        {{-- <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button> --}}
                    @endcan
                    @can('appointment.delete')
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
                    <input type="text" wire:model.live="filter.search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-3">
                    <b><label for="from_date">From Date</label></b>
                    {{ html()->date('from_date')->value('')->class('form-control')->attribute('wire:model.live', 'filter.from_date') }}
                </div>
                <div class="col-md-3">
                    <b><label for="to_date">To Date</label></b>
                    {{ html()->date('to_date')->value('')->class('form-control')->attribute('wire:model.live', 'filter.to_date') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="customer_id">Customer</label></b>
                    {{ html()->select('customer_id', [])->value('')->class('select-customer_id-list')->id('customer_id')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="branch_id">Branch</label></b>
                    {{ html()->select('branch_id', [auth()->user()->default_branch_id => auth()->user()->branch?->name])->value(auth()->user()->default_branch_id)->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}
                </div>
            </div>
            <div class="row my-2">
                <div class="col-md-3" wire:ignore>
                    <b><label for="employee_id">Employee</label></b>
                    {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list')->id('employee_id')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="service_id">Service</label></b>
                    {{ html()->select('service_id', [])->value('')->class('select-product_id-list')->attribute('type', 'service')->id('service_id')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="created_by">Created By</label></b>
                    {{ html()->select('created_by', [])->value('')->class('select-user_id-list')->id('created_by')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="status">Status</label></b>
                    {{ html()->select('status', saleStatuses())->value('')->class('tomSelect')->id('status')->placeholder('All') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm table-bordered">
                <thead>
                    <tr class="text-capitalize">
                        <th>
                            <input type="checkbox" wire:model.live="selectAll" />
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="customer_name" label="Customer" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="start_time" label="start time" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="end_time" label="end time" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="employee_name" label="Employee" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="service_name" label="Service" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="creator_name" label="creator" /> </th>
                        <th> Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" />
                                {{ $item->id }}
                            </td>
                            <td>{{ $item->customer_name }}</td>
                            <td>{{ systemDateTime($item->start_time) }}</td>
                            <td>{{ systemDateTime($item->end_time) }}</td>
                            <td>{{ $item->employee_name }}</td>
                            <td>{{ $item->service_name }}</td>
                            <td>{{ $item->creator_name }}</td>
                            <td> <i class="fa fa-2x fa-edit" wire:click="edit({{ $item->appointment_id }})"></i> </td>
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
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('filter.branch_id', value);
                });
                $('#customer_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('filter.customer_id', value);
                });
                $('#service_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('filter.service_id', value);
                });
                $('#employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('filter.employee_id', value);
                });
                $('#created_by').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('filter.created_by', value);
                });
                $('#status').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('filter.status', value);
                });
            });
        </script>
    @endpush
</div>
