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
                            <option value="50">50 rows</option>
                            <option value="100">100 rows</option>
                            <option value="500">500 rows</option>
                        </select>
                    </div>
                    <div class="form-group" style="width: 250px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0">
                                <i class="demo-pli-magnifi-glass"></i>
                            </span>
                            <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search purchase payments..." autofocus>
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="from_date">
                                    <i class="demo-psi-calendar-4 me-1"></i> From Date
                                </label>
                                {{ html()->date('from_date')->value('')->class('form-control form-control-sm')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="to_date">
                                    <i class="demo-psi-calendar-4 me-1"></i> To Date
                                </label>
                                {{ html()->date('to_date')->value('')->class('form-control form-control-sm')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="vendor_id">
                                    <i class="demo-psi-building me-1"></i> Vendor
                                </label>
                                {{ html()->select('vendor_id', [])->value('')->class('select-vendor_id-list')->id('vendor_id')->placeholder('All Vendors') }}
                            </div>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="branch_id">
                                    <i class="demo-psi-home me-1"></i> Branch
                                </label>
                                {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All Branches') }}
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
                        <th class="ps-3"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="purchases.account_id" label="#" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="Vendor" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="grand_total" label="Grand Total" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="Paid" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="Balance" /> </th>
                        <th class="text-nowrap text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td class="ps-3">
                                <span class="text-muted">#{{ $item->account_id }}</span>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-building fs-5 text-warning"></i>
                                    <span>{{ $item->name }} ({{ $item->count }})</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-end fw-semibold">{{ currency($item->grand_total) }}</div>
                            </td>
                            <td>
                                <div class="text-end text-success fw-semibold">{{ currency($item->paid) }}</div>
                            </td>
                            <td>
                                <div class="text-end text-danger fw-semibold">{{ currency($item->balance) }}</div>
                            </td>
                            <td class="text-nowrap text-end">
                                <button type="button" class="btn btn-sm btn-outline-success" wire:click='openPurchasesList({{ json_encode($item->name) }}, {{ $item->account_id }})' title="Make Payment">
                                    <i class="fa fa-money me-1"></i> Pay
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="bg-light">
                        <th colspan="2" class="ps-3"><strong>TOTALS</strong></th>
                        <th>
                            <div class="text-end fw-bold">{{ currency($total['grand_total']) }}</div>
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
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
                $('#vendor_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('vendor_id', value);
                });
            });
        </script>
    @endpush
</div>
