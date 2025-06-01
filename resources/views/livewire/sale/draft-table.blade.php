<div>
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-search"></i></span>
                <input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search drafts...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Invoice No</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th class="text-end">Grand Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lists as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ systemDate($item->date) }}</td>
                        <td>
                            <a href="{{ route('sale::edit', $item->id) }}" class="fw-semibold text-primary">{{ $item->invoice_no }}</a>
                        </td>
                        <td>{{ $item->customer_name ?: ($item->name ?: 'N/A') }}</td>
                        <td>{{ $item->customer_mobile ?: 'N/A' }}</td>
                        <td class="text-end fw-bold">{{ currency($item->grand_total) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fa fa-box-open fa-2x text-muted mb-2"></i>
                            <p class="mb-0 text-muted">No draft sales found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
