<div>
    <div class="card-header bg-light py-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-secondary-subtle"><i class="fa fa-search"></i></span>
                    <input type="text" wire:model.live.debounce.400ms="search" autofocus placeholder="Name, mobile, email or student name…"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm" autocomplete="off">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select class="form-select form-select-sm" wire:model.live="status" aria-label="Status">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select wire:model.live="limit" class="form-select form-select-sm" aria-label="Rows per page">
                    <option value="25">25</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                <thead class="bg-light text-muted">
                    <tr class="small">
                        <th class="fw-semibold py-2 ps-3"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="guardians.name" label="Parent" /></th>
                        <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="guardians.mobile" label="Contact" /></th>
                        <th class="fw-semibold">Students</th>
                        <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="guardians.status" label="Status" /></th>
                        <th class="fw-semibold">Portal</th>
                        <th class="fw-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $guardian)
                        <tr wire:key="guardian-{{ $guardian->id }}">
                            <td class="ps-3 text-nowrap fw-medium">{{ $guardian->name }}</td>
                            <td class="text-nowrap">
                                <div><i class="fa fa-phone me-1 text-body-secondary"></i>{{ $guardian->mobile }}</div>
                                @if ($guardian->email)
                                    <small class="text-body-secondary"><i class="fa fa-envelope-o me-1"></i>{{ $guardian->email }}</small>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @forelse ($guardian->students as $student)
                                    <a href="{{ route('student::view', $student->id) }}" class="badge bg-light text-body-emphasis border text-decoration-none mb-1">{{ $student->name }}</a>
                                @empty
                                    <span class="text-body-secondary">-</span>
                                @endforelse
                            </td>
                            <td><span class="badge {{ $guardian->status === 'active' ? 'bg-primary' : 'bg-secondary' }}">{{ ucfirst($guardian->status) }}</span></td>
                            <td class="text-nowrap">
                                @if (!$guardian->isActive())
                                    <span class="badge bg-light text-body-secondary border"><i class="fa fa-ban me-1"></i>Disabled</span>
                                @elseif ($guardian->isPendingInvite())
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        <i class="fa fa-clock-o me-1"></i>{{ $guardian->invited_at ? 'Invited, no password yet' : 'Not invited' }}
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                                        <i class="fa fa-check me-1"></i>{{ $guardian->last_login_at ? 'Signed in '.$guardian->last_login_at->diffForHumans() : 'Password set' }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @can('student guardian.invite')
                                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="invite({{ $guardian->id }})" wire:loading.attr="disabled" wire:target="invite({{ $guardian->id }})">
                                        <i class="fa fa-paper-plane me-1"></i>{{ $guardian->isPendingInvite() ? 'Send invite' : 'Reset password' }}
                                    </button>
                                @endcan
                                @if ($invite_link && $invite_guardian_id === $guardian->id)
                                    <div class="text-start mt-2" x-data="{ copied: false }">
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control form-control-sm font-monospace" value="{{ $invite_link }}" readonly x-ref="link-{{ $guardian->id }}" onclick="this.select()" aria-label="Set-password link">
                                            <button type="button" class="btn btn-outline-primary bg-body btn-sm" x-on:click="navigator.clipboard.writeText($refs['link-{{ $guardian->id }}'].value); copied = true; setTimeout(() => copied = false, 2000)">
                                                <i class="fa" :class="copied ? 'fa-check' : 'fa-files-o'"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">
                                @if ($hiddenCount)
                                    <div class="mb-2">No parents match these filters &mdash; {{ $hiddenCount }} {{ Str::plural('parent', $hiddenCount) }} {{ $hiddenCount === 1 ? 'is' : 'are' }} hidden by them.</div>
                                    <button type="button" class="btn btn-light btn-sm" wire:click="clearFilters"><i class="fa fa-filter me-1"></i>Show all parents</button>
                                @else
                                    No parents yet.
                                @endif
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
