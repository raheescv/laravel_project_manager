<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                </div>
            </div>
            <div class="col-md-8">
                <div class="d-flex gap-2 justify-content-md-end align-items-center">
                    <div class="form-group">
                        <select wire:model.live="limit" class="form-select form-select-sm">
                            <option value="10">10 rows</option>
                            <option value="25">25 rows</option>
                            <option value="50">50 rows</option>
                            <option value="100">100 rows</option>
                        </select>
                    </div>
                    <div class="form-group" style="width: 280px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0">
                                <i class="demo-pli-magnifi-glass"></i>
                            </span>
                            <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Customer name, mobile, order no..." autofocus>
                        </div>
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
                                <label class="form-label text-muted fw-semibold small mb-2" for="tailoring_from_date">
                                    <i class="demo-psi-calendar-4 me-1"></i> From Date
                                </label>
                                <input type="date" wire:model.live="from_date" id="tailoring_from_date" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="tailoring_to_date">
                                    <i class="demo-psi-calendar-4 me-1"></i> To Date
                                </label>
                                <input type="date" wire:model.live="to_date" id="tailoring_to_date" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="col-md-6" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="tailoring_receipts_customer_id">
                                    <i class="demo-psi-building me-1"></i> Customer
                                </label>
                                {{ html()->select('customer_id', [])->value('')->class('select-customer_id-list')->id('tailoring_receipts_customer_id')->attribute('data-receipts', '1')->placeholder('All Customers') }}
                            </div>
                        </div>
                        <div class="col-md-2" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="tailoring_receipts_branch_id">
                                    <i class="demo-psi-home me-1"></i> Branch
                                </label>
                                {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->class('select-assigned-branch_id-list')->id('tailoring_receipts_branch_id')->placeholder('All Branches') }}
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
                    <tr>
                        <th class="ps-3" width="5%">#</th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tailoring_orders.customer_name" label="Customer" /> </th>
                        <th class="text-nowrap text-end">Total</th>
                        <th class="text-nowrap text-end">Paid</th>
                        <th class="text-nowrap text-end">Balance</th>
                        <th class="text-center" width="80">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td class="ps-3">
                                <span class="text-muted">#{{ $loop->iteration + $data->firstItem() - 1 }}</span>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-building fs-5 text-warning"></i>
                                    <span>{{ $item->customer_name . ' (' . $item->customer_mobile . ')' }} <span class="badge bg-secondary">{{ $item->count }}</span></span>
                                </div>
                            </td>
                            <td>
                                <div class="text-end fw-semibold">{{ currency($item->grand_total) }}</div>
                            </td>
                            <td>
                                <div class="text-end text-success fw-semibold">{{ currency($item->paid) }}</div>
                            </td>
                            <td>
                                <div class="text-end text-danger fw-semibold">{{ $item->balance != 0 ? currency($item->balance) : '_' }}</div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-success py-0 px-2" wire:click="openReceiptModal({{ $item->account_id ?? 'null' }}, {{ json_encode($item->customer_name ?? '') }}, {{ json_encode($item->customer_mobile ?? '') }}, {{ json_encode($item->customer_display) }})" title="Collect payment / Print receipt">
                                    <i class="fa fa-receipt me-1"></i> Receipt
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="bg-light">
                        <th class="ps-3"><strong>TOTALS</strong></th>
                        <th></th>
                        <th>
                            <div class="text-end fw-bold text-primary">{{ currency($total['grand_total']) }}</div>
                        </th>
                        <th>
                            <div class="text-end text-success fw-bold">{{ currency($total['paid']) }}</div>
                        </th>
                        <th>
                            <div class="text-end text-danger fw-bold">{{ currency($total['balance']) }}</div>
                        </th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#tailoring_receipts_branch_id').on('change', function() {
                    const value = $(this).val() || '';
                    @this.set('branch_id', value);
                });
                $('#tailoring_receipts_customer_id').on('change', function() {
                    const value = $(this).val() || '';
                    @this.set('customer_id', value);
                });
            });
        </script>
    @endpush
</div>
