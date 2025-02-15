<div>
    <div class="table-top">
        <div class="search-set w-100 search-order">
            <div class="search-input w-100">
                <div id="DataTables_Table_0_filter" class="dataTables_filter">
                    <label>
                        <input type="search" wire:model.live="search" class="form-control form-control-sm" placeholder="Search" aria-controls="DataTables_Table_0">
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="order-body">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>invoice no</th>
                    <th>Customer</th>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th class="text-end">Grand Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lists as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ systemDate($item->date) }}</td>
                        <td> <a href="{{ route('sale::edit', $item->id) }}">{{ $item->invoice_no }}</a> </td>
                        <td> {{ $item->name }}</td>
                        <td>{{ $item->customer_name }}</td>
                        <td>{{ $item->customer_mobile }}</td>
                        <td class="text-end">{{ currency($item->grand_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
