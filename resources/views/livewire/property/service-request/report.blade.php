<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row g-2 align-items-center mt-2">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-secondary-subtle"><i class="fa fa-search"></i></span>
                        <input type="text" wire:model.live="search" placeholder="Search unit, building, group..." class="form-control form-control-sm border-secondary-subtle shadow-sm" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="filterUtility" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Utilities</option>
                        @foreach ($utilities as $utilityId => $utilityName)
                            <option value="{{ $utilityId }}">{{ $utilityName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="basedOn" class="form-select form-select-sm border-secondary-subtle shadow-sm" title="Date range applies to">
                        <option value="connect">By Connect Date</option>
                        <option value="disconnect">By Disconnect Date</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" wire:model.live="from_date" class="form-control form-control-sm border-secondary-subtle shadow-sm" title="From Date">
                </div>
                <div class="col-md-2">
                    <input type="date" wire:model.live="to_date" class="form-control form-control-sm border-secondary-subtle shadow-sm" title="To Date">
                </div>
                <div class="col-md-1">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="onlyConnected" wire:model.live="onlyConnected">
                        <label class="form-check-label small" for="onlyConnected">Live</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold ps-3">Utility</th>
                            <th class="fw-semibold">Group</th>
                            <th class="fw-semibold">Building</th>
                            <th class="fw-semibold">Unit</th>
                            <th class="fw-semibold">Connect Date</th>
                            <th class="fw-semibold">Disconnect Date</th>
                            <th class="fw-semibold">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td class="ps-3">{{ $item->utility?->name ?? '-' }}</td>
                                <td>{{ $item->group?->name ?? '-' }}</td>
                                <td>{{ $item->building?->name ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('property::service_request::edit', $item->id) }}" class="fw-medium">{{ $item->property?->number ?? '-' }}</a>
                                </td>
                                <td>{{ $item->date?->format('d M Y') }}</td>
                                <td>
                                    @if ($item->disconnect_date)
                                        {{ \Illuminate\Support\Carbon::parse($item->disconnect_date)->format('d M Y') }}
                                    @else
                                        <span class="badge bg-success-subtle text-success-emphasis">Connected</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $item->remarks }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa fa-plug fa-3x mb-3 d-block opacity-25"></i>
                                    No connections found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                {{ $data->links() }}
            </div>
        </div>
    </div>
</div>
