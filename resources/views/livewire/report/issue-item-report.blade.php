<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-6 d-flex align-items-center">
                <span class="text-muted small">Issue / return items by product and date</span>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2 justify-content-md-end align-items-center">
                    <div class="form-group mb-0">
                        <select wire:model.live="limit" class="form-select form-select-sm" style="width: auto;">
                            <option value="10">10 rows</option>
                            <option value="25">25 rows</option>
                            <option value="50">50 rows</option>
                            <option value="100">100 rows</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <hr class="mt-3 mb-0">
        <div class="col-12 mt-3">
            <div class="bg-light rounded-3 border shadow-sm">
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label text-muted fw-semibold small mb-2" for="from_date">From Date</label>
                            <input type="date" wire:model.live="from_date" class="form-control form-control-sm" id="from_date">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-muted fw-semibold small mb-2" for="to_date">To Date</label>
                            <input type="date" wire:model.live="to_date" class="form-control form-control-sm" id="to_date">
                        </div>
                        <div class="col-md-5" wire:ignore>
                            <label class="form-label text-muted fw-semibold small mb-2" for="product_id">Product</label>
                            {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->attribute('type', 'product')->id('issue_report_product_id')->placeholder('All Products') }}
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted fw-semibold small mb-2" for="search">Search (product / customer)</label>
                            <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" id="search" placeholder="Search...">
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
                    <tr>
                        <th class="ps-3"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="issue_items.id" label="#" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="issue_items.date" label="date" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="issue_items.issue_id" label="issue id" /></th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="issue_items.quantity_out" label="qty out" /></th>
                        <th class="text-end pe-3"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="issue_items.quantity_in" label="qty in" /></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="ps-3 text-muted">{{ $item->id }}</td>
                            <td>{{ systemDate($item->date) }}</td>
                            <td>
                                @can('issue.view')
                                    <a href="{{ route('issue::view', $item->issue_id) }}" class="text-primary">{{ $item->issue_id }}</a>
                                @else
                                    {{ $item->issue_id }}
                                @endcan
                            </td>
                            <td>{{ $item->issue?->account?->name ?? '—' }}</td>
                            <td>
                                {{ $item->product?->name ?? '—' }}
                                @if($item->product?->code)
                                    <small class="text-muted">({{ $item->product->code }})</small>
                                @endif
                            </td>
                            <td class="text-end">{{ $item->quantity_out > 0 ? number_format($item->quantity_out, 2) : '—' }}</td>
                            <td class="text-end pe-3">{{ $item->quantity_in > 0 ? number_format($item->quantity_in, 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="demo-psi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                No issue items found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($data->isNotEmpty())
                    <tfoot class="table-group-divider">
                        <tr class="bg-light fw-semibold">
                            <th colspan="5" class="ps-3 text-end">Total</th>
                            <th class="text-end">{{ number_format($total['quantity_out'], 2) }}</th>
                            <th class="text-end pe-3">{{ number_format($total['quantity_in'], 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#issue_report_product_id').on('change', function() {
                    @this.set('product_id', $(this).val() || '');
                });
            });
        </script>
    @endpush
</div>
