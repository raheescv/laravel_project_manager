<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                    @can('package.create')
                        <a href="{{ route('package::create') }}" class="btn btn-sm btn-primary" id="pageAdd">
                            <i class="demo-psi-add me-1"></i> Add New
                        </a>
                    @endcan
                    @can('package.delete')
                        <button class="btn btn-sm btn-outline-danger" title="Delete selected items" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
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
                            <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search packages..." autofocus>
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
                                <label class="form-label text-muted fw-semibold small mb-2" for="package_category_id">
                                    <i class="demo-psi-tag me-1"></i> Package Category
                                </label>
                                {{ html()->select('package_category_id', [])->value('')->class('select-package_category_id-list')->id('package_category_id')->placeholder('All Categories') }}
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label text-muted fw-semibold small mb-2" for="status">
                                    <i class="fa fa-flag me-1"></i> Status
                                </label>
                                <select wire:model.live="status" class="form-select form-select-sm" id="status">
                                    <option value="">All Status</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
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
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="package_categories.name" label="Package Category" />
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="Account" />
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="start_date" label="Start Date" />
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="end_date" label="End Date" />
                        </th>
                        <th class="text-nowrap text-end">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="amount" label="Amount" />
                        </th>
                        <th class="text-nowrap text-end">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="Paid" />
                        </th>
                        <th class="text-nowrap text-end">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="Balance" />
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="Status" />
                        </th>
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
                                <a href="{{ route('package::edit', $item->id) }}" class="text-primary fw-semibold text-decoration-none">
                                    {{ $item->packageCategory->name ?? '-' }}
                                </a>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-building fs-5 text-warning"></i>
                                    <span>{{ $item->account->name ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                    <span>{{ systemDate($item->start_date) }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                    <span>{{ systemDate($item->end_date) }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-end fw-medium">{{ currency($item->amount) }}</div>
                            </td>
                            <td>
                                <div class="text-end text-success fw-semibold">{{ currency($item->paid) }}</div>
                            </td>
                            <td>
                                <div class="text-end text-danger fw-semibold">{{ $item->balance != 0 ? currency($item->balance) : '_' }}</div>
                            </td>
                            <td>
                                <div
                                    class="badge bg-{{ $item->status === 'completed' ? 'success' : ($item->status === 'in_progress' ? 'warning' : 'danger') }} bg-opacity-10 text-{{ $item->status === 'completed' ? 'success' : ($item->status === 'in_progress' ? 'warning' : 'danger') }}">
                                    {{ ucWords(str_replace('_', ' ', $item->status)) }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No packages found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                window.addEventListener('RefreshPackageTable', event => {
                    Livewire.dispatch("Package-Refresh-Component");
                });
                // Handle Package Category change
                $('#package_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('package_category_id', value);
                });

                // Handle Account change
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('account_id', value);
                });
            });
        </script>
    @endpush
</div>
