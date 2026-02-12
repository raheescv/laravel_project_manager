@php
    use Carbon\Carbon;
@endphp
<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                    @can('issue.create')
                        <a class="btn btn-sm btn-primary hstack gap-2" href="{{ route('issue::create', ['type' => 'issue']) }}">
                            <i class="demo-psi-add"></i>
                            Add Issue
                        </a>
                        <a class="btn btn-sm btn-outline-primary hstack gap-2" href="{{ route('issue::create', ['type' => 'return']) }}">
                            <i class="fa fa-undo"></i>
                            Add Return
                        </a>
                    @endcan
                    @can('issue.delete')
                        <button type="button" class="btn btn-sm btn-outline-danger" title="Delete selected" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling me-1"></i> Delete
                        </button>
                    @endcan
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
                    <div class="form-group" style="width: 250px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0">
                                <i class="demo-pli-magnifi-glass"></i>
                            </span>
                            <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search issues..." autocomplete="off">
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
                                <label class="form-label text-muted fw-semibold small mb-2" for="type">
                                    <i class="demo-psi-calendar-4 me-1"></i> To Date
                                </label>
                                <select wire:model.live="type" class="form-select form-select-sm" id="type">
                                    <option value="">All</option>
                                    <option value="issue">Issue</option>
                                    <option value="return">Return</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="from_date">
                                    <i class="demo-psi-calendar-4 me-1"></i> From Date
                                </label>
                                <input type="date" wire:model.live="from_date" class="form-control form-control-sm" id="from_date">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="to_date">
                                    <i class="demo-psi-calendar-4 me-1"></i> To Date
                                </label>
                                <input type="date" wire:model.live="to_date" class="form-control form-control-sm" id="to_date">
                            </div>
                        </div>
                        <div class="col-md-4" wire:ignore>
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="table_account_id">
                                    <i class="demo-psi-building me-1"></i> Customer
                                </label>
                                {{ html()->select('account_id', [])->value('')->class('select-customer_id-list')->id('table_account_id')->placeholder('All Customers') }}
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
                        <th class="ps-3">
                            <div class="d-flex align-items-center">
                                <div class="form-check me-2">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll" id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                            </div>
                        </th>
                        <th colspan="2"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="type" label="Type" /></th>
                        <th class="text-nowrap"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_id" label="Customer" /></th>
                        <th class="text-nowrap text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="no_of_items_out" label="Qty Out" /></th>
                        <th class="text-nowrap text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="no_of_items_in" label="Qty In" /></th>
                        <th class="text-nowrap"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_by" label="User" /></th>
                        <th class="pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
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
                                    <i class="demo-psi-clock fs-5 text-info"></i>
                                    <span>{{ Carbon::parse($item->date)->diffForHumans() }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="{{ $item->type === 'return' ? 'demo-psi-arrow-down text-success' : 'demo-psi-arrow-up text-danger' }} fs-5"></i>
                                    <span>{{ ucFirst($item->type) }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-building fs-5 text-warning"></i>
                                    <span>{{ $item->account?->name ?? '-' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-end fw-medium">{{ number_format($item->no_of_items_out, 2) }}</div>
                            </td>
                            <td>
                                <div class="text-end fw-medium text-success">{{ number_format($item->no_of_items_in, 2) }}</div>
                            </td>
                            <td>{{ $item->createdBy?->name }}</td>
                            <td class="pe-3">
                                <div class="d-flex gap-1">
                                    @can('issue.view')
                                        <a href="{{ route('issue::view', $item->id) }}" class="btn btn-sm btn-outline-info" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    @endcan
                                    @can('issue.edit')
                                        <a href="{{ route('issue::edit', $item->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="demo-psi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                No issues found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($data->isNotEmpty())
                    <tfoot class="table-group-divider">
                        <tr class="bg-light">
                            <th colspan="5" class="ps-3"><strong>TOTALS</strong></th>
                            <th class="text-end fw-bold">{{ number_format($totals['no_of_items_out'] ?? 0, 2) }}</th>
                            <th class="text-end fw-bold text-success">{{ number_format($totals['no_of_items_in'] ?? 0, 2) }}</th>
                            <th></th>
                            <th class="pe-3"></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        {{ $data->links() }}
    </div>

    @push('scripts')
        <script>
            $('#table_account_id').on('change', function(e) {
                @this.set('account_id', $(this).val());
            });
        </script>
    @endpush
</div>
