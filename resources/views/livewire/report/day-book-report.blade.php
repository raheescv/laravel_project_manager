<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                    @can('report.day book export')
                        <button class="btn btn-sm btn-outline-primary" title="Export as Excel" wire:click="export()">
                            <i class="demo-pli-file-excel me-1"></i> Export
                        </button>
                    @endcan
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
                            <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search entries..." autofocus>
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
                                <label class="form-label text-muted fw-semibold small mb-2" for="account_id">
                                    <i class="demo-psi-building me-1"></i> Account
                                </label>
                                {{ html()->select('account_id', [])->value('')->class('select-account_id-list')->id('account_id')->placeholder('All Accounts') }}
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
            <table class="table table-striped table-hover table-sm align-middle mb-0 border-bottom table-sm">
                <thead class="bg-light text-nowrap">
                    <tr>
                        <th class="ps-3">
                            <div class="d-flex align-items-center">
                                <div class="form-check me-2">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll" id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_entries.id" label="#" />
                            </div>
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_entries.date" label="date" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="account name" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_entries.description" label="description" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_entries.reference_number" label="reference no" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_entries.remarks" label="remarks" /> </th>
                        <th class="text-nowrap"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_entries.journal_remarks" label="journal remarks" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_entries.debit" label="debit" /> </th>
                        <th class="text-nowrap text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_entries.credit" label="credit" /> </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                    </div>
                                    <span class="text-muted">#{{ $item->id }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                    <span>{{ systemDate($item->date) }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-building fs-5 text-warning"></i>
                                    <a href="{{ route('account::view', $item->account_id) }}?from_date={{ $from_date }}&to_date={{ $to_date }}" class="text-decoration-none fw-semibold">
                                        {{ $item->account_name }}
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($item->model)
                                        <span class="badge bg-info bg-opacity-10 text-info">{{ $item->model }}</span>
                                    @endif
                                    @if ($item->journal_model)
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $item->journal_model }}</span>
                                    @endif
                                    @switch($item->model)
                                        @case('Sale')
                                            <a href="{{ route('sale::view', $item->model_id) }}" class="text-primary text-decoration-none">{{ $item->description }}</a>
                                        @break

                                        @case('SalePayment')
                                            <a href="{{ route('sale::view', $item->journal?->model_id) }}" class="text-primary text-decoration-none">{{ $item->description }}</a>
                                        @break

                                        @case('SaleReturn')
                                            <a href="{{ route('sale_return::view', $item->model_id) }}" class="text-primary text-decoration-none">{{ $item->description }}</a>
                                        @break

                                        @case('SaleReturnPayment')
                                            <a href="{{ route('sale_return::view', $item->journal?->model_id) }}" class="text-primary text-decoration-none">{{ $item->description }}</a>
                                        @break

                                        @default
                                            <span>{{ $item->description }}</span>
                                    @endswitch
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <span class="text-muted">{{ $item->reference_number ?? '_' }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $item->remarks ?? '_' }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $item->journal_remarks ?? '_' }}</span>
                            </td>
                            <td>
                                <div class="text-end fw-medium">{{ $item->debit != 0 ? currency($item->debit) : '_' }}</div>
                            </td>
                            <td>
                                <div class="text-end fw-medium">{{ $item->credit != 0 ? currency($item->credit) : '_' }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="bg-light">
                        <th colspan="7" class="ps-3"><strong>TOTALS</strong></th>
                        <th>
                            <div class="text-end fw-bold">{{ currency($total['debit']) }}</div>
                        </th>
                        <th>
                            <div class="text-end fw-bold">{{ currency($total['credit']) }}</div>
                        </th>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('account_id', value);
                });
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
            });
        </script>
    @endpush
</div>
