<div>
    <div class="card-header">
        <div class="row" hidden>
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    {{-- <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button> --}}
                </div>
            </div>
        </div>
        {{-- <hr> --}}
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-2">
                    <label for="from_date">From Date</label>
                    {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                </div>
                <div class="col-md-2">
                    <label for="to_date">To Date</label>
                    {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                </div>
                <div class="col-md-4" wire:ignore>
                    <label for="branch_id">Branch</label>
                    {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('Branch') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="date" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branches.name" label="branch" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="net_sales" label="net sales" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="no_of_invoices" label="no of invoices" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales_discount" label="sales discount" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total_sales" label="total sales" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="credit" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="paid" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="item_total" label="item total" /> </th>
                        @foreach ($paymentMethods as $name)
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="{{ $name }}" /> </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>{{ systemDate($item['date']) }}</td>
                            <td>{{ $item['branch_name'] }}</td>
                            <td class="text-end">{{ currency($item['net_sales']) }}</td>
                            <td class="text-end">{{ $item['no_of_invoices'] }}</td>
                            <td class="text-end">{{ currency($item['sales_discount']) }}</td>
                            <td class="text-end">{{ currency($item['total_sales']) }}</td>
                            <td class="text-end">{{ currency($item['credit']) }}</td>
                            <td class="text-end">{{ currency($item['paid']) }}</td>
                            <td class="text-end">{{ currency($item['item_total']) }}</td>
                            @foreach ($paymentMethods as $name)
                                <td class="text-end">{{ currency($item[$name]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th class="text-end" colspan="2">Total</th>
                        <th class="text-end">{{ currency($total['net_sales']) }}</th>
                        <th class="text-end">{{ $total['no_of_invoices'] }}</th>
                        <th class="text-end">{{ currency($total['sales_discount']) }}</th>
                        <th class="text-end">{{ currency($total['total_sales']) }}</th>
                        <th class="text-end">{{ currency($total['credit']) }}</th>
                        <th class="text-end">{{ currency($total['paid']) }}</th>
                        <th class="text-end">{{ currency($total['item_total']) }}</th>
                        @foreach ($paymentMethods as $name)
                            <th class="text-end">{{ currency($total[$name]) }}</th>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
            });
        </script>
    @endpush
</div>
