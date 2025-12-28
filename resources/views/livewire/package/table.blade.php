<div>
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-in_progress {
            background-color: #fef3c7;
            color: #d97706;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #059669;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #dc2626;
        }
    </style>
    <div class="card-header -4 mb-3">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('package.create')
                    <button class="btn btn-primary hstack gap-2 align-self-center" id="pageAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </button>
                @endcan
                <div class="btn-group">
                    @can('package.delete')
                        <button class="btn btn-icon btn-outline-light" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
            <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                <div class="form-group">
                    <select wire:model.live="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" wire:model.live="search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th width="5%">
                            <input type="checkbox" wire:model.live="selectAll" />
                        </th>
                        <th width="5%">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                        </th>
                        <th width="15%">Package Category</th>
                        <th width="20%">Account</th>
                        <th width="10%">Start Date</th>
                        <th width="10%">End Date</th>
                        <th width="10%" class="text-end">Amount</th>
                        <th width="10%" class="text-end">Paid</th>
                        <th width="10%" class="text-end">Balance</th>
                        <th width="10%">Status</th>
                        <th width="5%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" />
                            </td>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->packageCategory->name ?? '-' }}</td>
                            <td>{{ $item->account->name ?? '-' }}</td>
                            <td>{{ systemDate($item->start_date) }}</td>
                            <td>{{ systemDate($item->end_date) }}</td>
                            <td class="text-end">{{ currency($item->amount) }}</td>
                            <td class="text-end">{{ currency($item->paid) }}</td>
                            <td class="text-end">{{ currency($item->balance) }}</td>
                            <td>
                                <span class="status-badge status-{{ $item->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                </span>
                            </td>
                            <td>
                                @can('package.edit')
                                    <a href="{{ route('package::edit', $item->id) }}" class="text-primary">
                                        <i class="demo-psi-pencil fs-5 me-2"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center">No packages found</td>
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
                $('#pageAdd').click(function() {
                    window.location.href = "{{ route('package::create') }}";
                });
                window.addEventListener('RefreshPackageTable', event => {
                    Livewire.dispatch("Package-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
