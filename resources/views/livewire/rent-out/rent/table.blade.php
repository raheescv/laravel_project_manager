<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-6 d-flex gap-2">
                @can('rent out.create')
                    <a href="{{ route('property::rent::create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <i class="demo-psi-add fs-5"></i>
                        <span>Add New</span>
                    </a>
                @endcan
                @can('rent out.delete')
                    @if(count($selected) > 0)
                        <button class="btn btn-outline-danger d-inline-flex align-items-center gap-2" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                            <span>Delete</span>
                        </button>
                    @endif
                @endcan
            </div>
            <div class="col-12 col-md-6">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <div class="d-flex bg-light rounded-2 px-2">
                        <span class="d-flex align-items-center text-muted">
                            <i class="demo-psi-filter fs-6"></i>
                        </span>
                        {{ html()->select('statusFilter', rentOutStatusOptions())->value($statusFilter)->class('form-select bg-transparent border-0 fw-semibold py-2')->style('width: 120px; box-shadow: none; font-size: 0.875rem;')->attribute('wire:model.live', 'statusFilter')->placeholder('All Status') }}
                    </div>
                    <div class="d-flex bg-light rounded-2 px-2">
                        <span class="d-flex align-items-center text-muted">
                            <i class="demo-psi-list-view fs-6"></i>
                        </span>
                        <select wire:model.live="limit" class="form-select bg-transparent border-0 fw-semibold py-2" style="width: 80px; box-shadow: none; font-size: 0.875rem;">
                            <option value="10">10</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="d-flex bg-light rounded-2 px-2 flex-grow-1 flex-md-grow-0" style="min-width: 250px;">
                        <span class="d-flex align-items-center text-muted">
                            <i class="demo-psi-magnifi-glass fs-6"></i>
                        </span>
                        <input type="text" wire:model.live="search" autofocus placeholder="Search rentals..." class="form-control bg-transparent border-0 py-2" style="box-shadow: none; font-size: 0.875rem;" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" width="80">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model.live="selectAll" id="selectAll">
                            </div>
                        </th>
                        <th width="5%"> <x-sortable-header field="id" label="ID" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th>Customer</th>
                        <th>Property</th>
                        <th>Building</th>
                        <th> <x-sortable-header field="start_date" label="Start Date" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th> <x-sortable-header field="end_date" label="End Date" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th> <x-sortable-header field="rent" label="Rent" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th>Status</th>
                        <th width="10%" class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="ps-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $item->id }}" wire:model.live="selected">
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border">#{{ $item->id }}</span></td>
                            <td>{{ $item->customer?->name }}</td>
                            <td>{{ $item->property?->name }}</td>
                            <td>{{ $item->building?->name }}</td>
                            <td>{{ $item->start_date?->format('Y-m-d') }}</td>
                            <td>{{ $item->end_date?->format('Y-m-d') }}</td>
                            <td>{{ number_format($item->rent, 2) }}</td>
                            <td>
                                @if($item->status)
                                    <span class="badge bg-{{ $item->status->color() }}">{{ $item->status->label() }}</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @can('rent out.view')
                                    <a href="{{ route('property::rent::view', $item->id) }}" class="btn btn-icon btn-sm btn-hover btn-light" title="View">
                                        <i class="demo-psi-file fs-5 text-muted"></i>
                                    </a>
                                @endcan
                                @can('rent out.edit')
                                    <a href="{{ route('property::rent::create', $item->id) }}" class="btn btn-icon btn-sm btn-hover btn-light" title="Edit Rental">
                                        <i class="demo-psi-pencil fs-5 text-muted"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">No rental agreements found matching your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($data->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $data->links() }}
        </div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                window.addEventListener('RefreshRentOutRentTable', event => {
                    Livewire.dispatch("RentOutRent-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
