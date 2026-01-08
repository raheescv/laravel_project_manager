<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">

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
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-3">
                    <b><label for="from_date">From Date</label></b>
                    {{ html()->date('from_date')->value('')->class('form-control')->id('unit_id')->attribute('wire:model.live', 'from_date') }}
                </div>
                <div class="col-md-3">
                    <b><label for="to_date">To Date</label></b>
                    {{ html()->date('to_date')->value('')->class('form-control')->id('unit_id')->attribute('wire:model.live', 'to_date') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="customer_id">Customer</label></b>
                    {{ html()->select('customer_id', [])->value('')->class('select-customer_id-list')->id('customer_id')->placeholder('All') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <b><label for="branch_id">Branch</label></b>
                    {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm table-bordered">
                <thead>
                    <tr>
                        <th class="text-end" width="5%">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_id" label="#" />
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="Customer" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales.total" label="total" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales.paid" label="paid" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales.balance" label="balance" /> </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td class="text-end"> {{ $loop->iteration }} </td>
                            <td>{{ $item->name }} ({{ $item->count }})
                                <b class="pull-right pointer" wire:click="openSalesList('{{ $item->name }}','{{ $item->account_id }}')">
                                    <i class="fa fa-2x fa-money"></i>
                                </b>
                            </td>
                            <td class="text-end">{{ currency($item->grand_total) }}</td>
                            <td class="text-end">{{ currency($item->paid) }}</td>
                            <td class="text-end">{{ currency($item->balance) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th></th>
                        <th class="text-end">{{ currency($total['grand_total']) }}</th>
                        <th class="text-end">{{ currency($total['paid']) }}</th>
                        <th class="text-end">{{ currency($total['balance']) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
                $('#customer_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('customer_id', value);
                });
            });
        </script>
    @endpush
</div>
