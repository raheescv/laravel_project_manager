<div>
    <div class="card-header bg-white p-4">
        <!-- Header Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="btn-group">
                    @can('general voucher.create')
                        <button class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" id="PageAdd">
                            <i class="demo-psi-add fs-5"></i>
                            Add New
                        </button>
                    @endcan
                    @can('general voucher.delete')
                        <button class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                            Delete
                        </button>
                    @endcan
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="input-group input-group-sm" style="width: 120px;">
                    <select wire:model.live="limit" class="form-select border-start-0">
                        <option value="10">10 rows</option>
                        <option value="100">100 rows</option>
                        <option value="500">500 rows</option>
                    </select>
                </div>
                <div class="input-group input-group-sm" style="width: 200px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa fa-search"></i>
                    </span>
                    <input type="text" wire:model.live="filter.search" class="form-control border-start-0" placeholder="Search vouchers..." autofocus>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="demo-pli-layout-grid me-1"></i> Columns
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#generalVoucherColumnVisibility" aria-controls="generalVoucherColumnVisibility">
                                <i class="demo-pli-column-width me-2"></i>Column Visibility
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-light rounded-3 border p-3 mt-3">
            <div class="row g-3">
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label text-muted small fw-semibold mb-2">
                            <i class="fa fa-calendar me-1"></i> From Date
                        </label>
                        {{ html()->date('from_date')->value('')->class('form-control form-control-sm')->id('from_date')->attribute('wire:model.live', 'filter.from_date') }}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label text-muted small fw-semibold mb-2">
                            <i class="fa fa-calendar me-1"></i> To Date
                        </label>
                        {{ html()->date('to_date')->value('')->class('form-control form-control-sm')->id('to_date')->attribute('wire:model.live', 'filter.to_date') }}
                    </div>
                </div>
                <div class="col-md-5" wire:ignore>
                    <div class="form-group">
                        <label class="form-label text-muted small fw-semibold mb-2">
                            <i class="fa fa-building me-1"></i> Account
                        </label>
                        {{ html()->select('account_id', [])->value('')->class('select-account_id-list')->id('account_id')->attribute('wire:model', 'filter.account_id')->placeholder('Select Account') }}
                    </div>
                </div>
                <div class="col-md-3" wire:ignore>
                    <div class="form-group">
                        <label class="form-label text-muted small fw-semibold mb-2">
                            <i class="fa fa-building me-1"></i> Branch
                        </label>
                        {{ html()->select('branch_id', [])->value('')->class('select-branch_id-list')->id('branch_id')->attribute('wire:model', 'filter.branch_id')->placeholder('Select Branch') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border table-sm">
                <thead class="bg-light table-sm">
                    <tr class="text-capitalize">
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check mb-0">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll" id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                            </div>
                        </th>
                        @if ($general_voucher_visible_column['date'] ?? true)
                            <th class="border-bottom py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-calendar text-primary"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" />
                                </div>
                            </th>
                        @endif
                        @if ($general_voucher_visible_column['account'] ?? true)
                            <th class="border-bottom py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-arrow-up text-danger"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_id" label="Account" />
                                </div>
                            </th>
                        @endif
                        @if ($general_voucher_visible_column['debit'] ?? true)
                            <th class="border-bottom py-3 text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <i class="fa fa-money text-primary"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="debit" label="Debit" />
                                </div>
                            </th>
                        @endif
                        @if ($general_voucher_visible_column['credit'] ?? true)
                            <th class="border-bottom py-3 text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <i class="fa fa-money text-primary"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="Credit" />
                                </div>
                            </th>
                        @endif
                        @if ($general_voucher_visible_column['person_name'] ?? true)
                            <th class="border-bottom py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-user text-primary"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="person_name" label="Person Name" />
                                </div>
                            </th>
                        @endif
                        @if ($general_voucher_visible_column['reference_number'] ?? true)
                            <th class="border-bottom py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-tag text-primary"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="reference_number" label="Reference" />
                                </div>
                            </th>
                        @endif
                        @if ($general_voucher_visible_column['description'] ?? true)
                            <th class="border-bottom py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-align-left text-primary"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="Description" />
                                </div>
                            </th>
                        @endif
                        @if ($general_voucher_visible_column['remarks'] ?? true)
                            <th class="border-bottom py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-comment text-primary"></i>
                                    Remarks
                                </div>
                            </th>
                        @endif
                        @if ($general_voucher_visible_column['created_by'] ?? true)
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-align-left text-primary"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_by" label="Created By" />
                            </div>
                        </th>
                        @endif
                        <th class="border-bottom py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="align-middle">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->journal_id }}" wire:model.live="selected">
                                    </div>
                                    <span class="text-muted">#{{ $item->journal_id }}</span>
                                </div>
                            </td>
                            @if ($general_voucher_visible_column['date'] ?? true)
                                <td class="align-middle">
                                    <span class="badge bg-light text-dark">
                                        <i class="fa fa-calendar me-1"></i>
                                        {{ systemDate($item->date) }}
                                    </span>
                                </td>
                            @endif
                            @if ($general_voucher_visible_column['account'] ?? true)
                                <td class="align-middle">
                                    @if ($item->account)
                                        <a href="{{ route('account::view', $item->account_id) }}" class="text-decoration-none">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa fa-university text-danger"></i>
                                                <span class="fw-medium">{{ $item->account->name }}</span>
                                            </div>
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endif
                            @if ($general_voucher_visible_column['debit'] ?? true)
                                <td class="text-end align-middle fw-medium text-primary">
                                    {{ currency($item->debit) }}
                                </td>
                            @endif
                            @if ($general_voucher_visible_column['credit'] ?? true)
                                <td class="text-end align-middle fw-medium text-primary">
                                    {{ currency($item->credit) }}
                                </td>
                            @endif
                            @if ($general_voucher_visible_column['person_name'] ?? true)
                                <td class="align-middle">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-user text-muted"></i>
                                        <span>{{ $item->person_name ?? '-' }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($general_voucher_visible_column['reference_number'] ?? true)
                                <td class="align-middle">
                                    @if ($item->reference_number)
                                        <code class="bg-light px-2 py-1 rounded">{{ $item->reference_number }}</code>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endif
                            @if ($general_voucher_visible_column['description'] ?? true)
                                <td class="align-middle">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-file-alt text-muted"></i>
                                        <span>{{ $item->description }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($general_voucher_visible_column['remarks'] ?? true)
                                <td class="align-middle">
                                    @if ($item->journal_remarks)
                                        <span class="text-muted small">{{ Str::limit($item->journal_remarks ?? '', 30) }}</span>
                                    @elseif ($item->remarks)
                                        <span class="text-muted small">{{ Str::limit($item->remarks ?? '', 30) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endif
                            @if ($general_voucher_visible_column['created_by'] ?? true)
                                <td class="align-middle">
                                    <span class="badge bg-light text-dark">
                                        {{ $item->journal->createdBy->name ?? '-' }}
                                    </span>
                                </td>
                            @endif
                            <td class="align-middle text-center">
                                <div class="btn-group" role="group">
                                    @can('general voucher.edit')
                                        <button class="btn btn-sm btn-outline-primary edit" table_id="{{ $item->journal_id }}" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                    @endcan
                                    <a href="{{ route('account::general-voucher::print', $item->journal_id) }}" target="_blank" class="btn btn-sm btn-outline-success" title="Print Voucher">
                                        <i class="fa fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + count(array_filter($general_voucher_visible_column ?? [])) }}" class="text-center py-4 text-muted">
                                <i class="fa fa-file-invoice fs-2 mb-2"></i>
                                <p class="mb-0">No general vouchers found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($data->count() > 0)
                    <tfoot class="bg-light">
                        <tr>
                            <td class="align-middle fw-bold" colspan="1"></td>
                            @if ($general_voucher_visible_column['date'] ?? true)
                                <td class="align-middle fw-bold"></td>
                            @endif
                            @if ($general_voucher_visible_column['account'] ?? true)
                                <td class="align-middle fw-bold">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-calculator text-primary"></i>
                                        <span>Total</span>
                                    </div>
                                </td>
                            @else
                                <td class="align-middle fw-bold" colspan="{{ ($general_voucher_visible_column['date'] ?? true) ? 1 : 2 }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-calculator text-primary"></i>
                                        <span>Total</span>
                                    </div>
                                </td>
                            @endif
                            @if ($general_voucher_visible_column['debit'] ?? true)
                                <td class="text-end align-middle fw-bold text-primary">
                                    {{ currency($totalDebit) }}
                                </td>
                            @endif
                            @if ($general_voucher_visible_column['credit'] ?? true)
                                <td class="text-end align-middle fw-bold text-primary">
                                    {{ currency($totalCredit) }}
                                </td>
                            @endif
                            @php
                                $remainingCols = 0;
                                if ($general_voucher_visible_column['person_name'] ?? true) $remainingCols++;
                                if ($general_voucher_visible_column['reference_number'] ?? true) $remainingCols++;
                                if ($general_voucher_visible_column['description'] ?? true) $remainingCols++;
                                if ($general_voucher_visible_column['remarks'] ?? true) $remainingCols++;
                                if ($general_voucher_visible_column['created_by'] ?? true) $remainingCols++;
                                $remainingCols++; // Actions column
                            @endphp
                            @if ($remainingCols > 0)
                                <td colspan="{{ $remainingCols }}"></td>
                            @endif
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
                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("GeneralVoucher-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('filter.account_id', value);
                });
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('filter.branch_id', value);
                });
                $('#PageAdd').click(function() {
                    Livewire.dispatch("GeneralVoucher-Page-Create-Component");
                });
                window.addEventListener('RefreshGeneralVoucherTable', event => {
                    Livewire.dispatch("GeneralVoucher-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
