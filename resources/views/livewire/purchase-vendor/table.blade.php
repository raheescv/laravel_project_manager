<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    <h5 class="mb-0 fw-bold">Vendor List</h5>
                </div>

                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 text-muted small fw-semibold">Show:</label>
                        </div>
                        <div class="col-auto">
                            <select wire:model.live="limit" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                                <option value="10">10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" wire:model.live="search" autofocus placeholder="Search vendors..." class="form-control form-control-sm border-secondary-subtle shadow-sm"
                                    autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.id" label="ID" />
                            </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="Name" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.mobile" label="Mobile" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.place" label="Place" /> </th>
                            <th class="fw-semibold text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total_amount" label="Total" /> </th>
                            <th class="fw-semibold text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total_paid" label="Paid" /> </th>
                            <th class="fw-semibold text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total_balance" label="Balance" /> </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>
                                    <a href="{{ route('purchase-vendor::view', $item->id) }}" class="text-decoration-none fw-medium text-primary">
                                        <i class="fa fa-user me-1"></i>{{ $item->name }}
                                    </a>
                                </td>
                                <td><i class="fa fa-phone me-1 text-success opacity-75"></i>{{ $item->mobile ?: '-' }}</td>
                                <td>
                                    @if ($item->place)
                                        <i class="fa fa-map-marker me-1 text-muted"></i>{{ $item->place }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-primary">{{ currency($item->total_amount) }}</td>
                                <td class="text-end text-success fw-semibold">{{ currency($item->total_paid) }}</td>
                                <td class="text-end text-danger fw-semibold">{{ currency($item->total_balance) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                {{ $data->links() }}
            </div>
        </div>
    </div>
</div>
