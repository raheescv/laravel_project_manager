<div>
    <div class="card-header bg-white p-4">
        <!-- Header Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="btn-group">
                    @can('income.create')
                        <button class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" id="PageAdd">
                            <i class="demo-psi-add fs-5"></i>
                            Add New
                        </button>
                    @endcan
                    @can('income.export')
                        <button class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2" wire:click="export()">
                            <i class="demo-pli-file-excel fs-5"></i>
                            Export
                        </button>
                    @endcan
                    @can('income.delete')
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
                    <input type="text" wire:model.live="filter.search" class="form-control border-start-0" placeholder="Search incomes..." autofocus>
                </div>
                @can('income.import')
                    <button class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#IncomeImportModal">
                        <i class="demo-pli-download-from-cloud fs-5"></i>
                        Import
                    </button>
                @endcan
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-light rounded-3 border p-3 mt-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label text-muted small fw-semibold mb-2">
                            <i class="fa fa-calendar me-1"></i> From Date
                        </label>
                        {{ html()->date('from_date')->value('')->class('form-control form-control-sm')->id('from_date')->attribute('wire:model.live', 'filter.from_date') }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label text-muted small fw-semibold mb-2">
                            <i class="fa fa-calendar me-1"></i> To Date
                        </label>
                        {{ html()->date('to_date')->value('')->class('form-control form-control-sm')->id('to_date')->attribute('wire:model.live', 'filter.to_date') }}
                    </div>
                </div>
                <div class="col-md-6" wire:ignore>
                    <div class="form-group">
                        <label class="form-label text-muted small fw-semibold mb-2">
                            <i class="fa fa-university me-1"></i> Account
                        </label>
                        {{ html()->select('account_id', [])->value('')->class('select-account_id')->attribute('account_type', 'income')->id('account_id')->attribute('wire:model', 'filter.account_id')->placeholder('Select Account') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border">
                <thead class="bg-light">
                    <tr class="text-capitalize">
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check mb-0">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll" id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_id" label="ID" />
                            </div>
                        </th>
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-calendar text-primary"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" />
                            </div>
                        </th>
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-university text-primary"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_name" label="Account" />
                            </div>
                        </th>
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-user text-primary"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="person_name" label="Receiver" />
                            </div>
                        </th>
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-tag text-primary"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="reference_number" label="Reference" />
                            </div>
                        </th>
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-align-left text-primary"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="Description" />
                            </div>
                        </th>
                        <th class="border-bottom py-3 text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <i class="fa fa-arrow-up text-danger"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="debit" label="Debit" />
                            </div>
                        </th>
                        <th class="border-bottom py-3 text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <i class="fa fa-arrow-down text-success"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="Credit" />
                            </div>
                        </th>
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
                            <td class="align-middle">
                                <span class="badge bg-light text-dark">
                                    <i class="fa fa-calendar me-1"></i>
                                    {{ systemDate($item->date) }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <a href="{{ route('account::view', $item->account_id) }}" class="text-decoration-none">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-university text-primary"></i>
                                        <span class="fw-medium">{{ $item->account_name }}</span>
                                    </div>
                                </a>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-user text-muted"></i>
                                    <span>{{ $item->person_name }}</span>
                                </div>
                            </td>
                            <td class="align-middle">
                                <code class="bg-light px-2 py-1 rounded">{{ $item->reference_number }}</code>
                            </td>
                            <td class="align-middle">
                                @switch($item->model)
                                    @case('Sale')
                                        <a href="{{ route('sale::view', $item->model_id) }}" class="text-decoration-none">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa fa-shopping-cart text-success"></i>
                                                <span>{{ $item->description }}</span>
                                            </div>
                                        </a>
                                    @break

                                    @case('SaleReturn')
                                        <a href="{{ route('sale_return::view', $item->model_id) }}" class="text-decoration-none">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa fa-undo text-warning"></i>
                                                <span>{{ $item->description }}</span>
                                            </div>
                                        </a>
                                    @break

                                    @default
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa fa-file-alt text-muted"></i>
                                            <span>{{ $item->description }}</span>
                                        </div>
                                @endswitch
                            </td>
                            <td class="text-end align-middle fw-medium text-danger">{{ currency($item->debit) }}</td>
                            <td class="text-end align-middle fw-medium text-success">{{ currency($item->credit) }}</td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa fa-receipt fs-2 mb-2"></i>
                                    <p class="mb-0">No expenses found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-group-divider bg-light">
                        <tr>
                            <th colspan="6" class="text-end py-3">
                                <span class="fw-bold">Total</span>
                            </th>
                            <th class="text-end py-3">
                                <span class="fw-bold text-danger">{{ currency($total['debit']) }}</span>
                            </th>
                            <th class="text-end py-3">
                                <span class="fw-bold text-success">{{ currency($total['credit']) }}</span>
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
                    $(document).on('click', '.edit', function() {
                        Livewire.dispatch("Income-Page-Update-Component", {
                            id: $(this).attr('table_id')
                        });
                    });
                    $('#account_id').on('change', function(e) {
                        const value = $(this).val() || null;
                        @this.set('filter.account_id', value);
                    });
                    $('#PageAdd').click(function() {
                        Livewire.dispatch("Income-Page-Create-Component");
                    });
                    window.addEventListener('RefreshIncomeTable', event => {
                        Livewire.dispatch("Income-Refresh-Component");
                    });
                });
            </script>
        @endpush
    </div>
