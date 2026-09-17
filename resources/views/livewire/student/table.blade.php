<div>
    <div class="card-header bg-light py-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center">
                @can('student.create')
                    <a href="{{ route('student::create') }}" class="btn btn-primary d-flex align-items-center shadow-sm">
                        <i class="fa fa-user-plus me-2"></i> Add Student
                    </a>
                @endcan
                <div class="btn-group shadow-sm">
                    @can('student.export')
                        <button class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" wire:click="export()">
                            <i class="fa fa-file-excel-o me-md-1 fs-5"></i><span class="d-none d-md-inline">Export</span>
                        </button>
                    @endcan
                    @can('student.import')
                        <a href="{{ route('student::import') }}" class="btn btn-info btn-sm d-flex align-items-center text-white" title="Import Students">
                            <i class="fa fa-cloud-upload me-md-1 fs-5"></i><span class="d-none d-md-inline">Import</span>
                        </a>
                    @endcan
                    @can('student.delete')
                        <button class="btn btn-danger btn-sm d-flex align-items-center" title="Delete Selected" wire:click="delete()"
                            wire:confirm="Delete the selected students? Students with card history cannot be deleted.">
                            <i class="fa fa-trash me-md-1 fs-5"></i><span class="d-none d-md-inline">Delete</span>
                        </button>
                    @endcan
                </div>
            </div>
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-secondary-subtle"><i class="fa fa-search"></i></span>
                    <input type="text" wire:model.live.debounce.400ms="search" autofocus placeholder="Name, admission no, card, parent name or mobile…"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm" autocomplete="off">
                    <select wire:model.live="limit" class="form-select form-select-sm border-secondary-subtle" style="max-width: 7rem" aria-label="Rows per page">
                        <option value="25">25</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
            </div>
        </div>
        <hr class="my-3">
        <div class="row g-2">
            <div class="col-6 col-md-3">
                <label class="form-label small fw-medium mb-1" for="st_grade">Grade</label>
                <select id="st_grade" class="form-select form-select-sm" wire:model.live="grade">
                    <option value="">All grades</option>
                    @foreach ($grades as $value)
                        <option value="{{ $value }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-medium mb-1" for="st_section">Section</label>
                <select id="st_section" class="form-select form-select-sm" wire:model.live="section">
                    <option value="">All sections</option>
                    @foreach ($sections as $value)
                        <option value="{{ $value }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-medium mb-1" for="st_status">Status</label>
                <select id="st_status" class="form-select form-select-sm" wire:model.live="status">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\StudentDetail::STATUSES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-medium mb-1" for="st_card_status">Card</label>
                <select id="st_card_status" class="form-select form-select-sm" wire:model.live="card_status">
                    <option value="">Any card</option>
                    <option value="active">Active</option>
                    <option value="blocked">Blocked</option>
                    <option value="none">No card linked</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                <thead class="bg-light text-muted">
                    <tr class="small">
                        <th class="fw-semibold py-2 ps-3" style="width: 2.5rem">
                            <input type="checkbox" wire:model.live="selectAll" class="form-check-input" aria-label="Select all">
                        </th>
                        <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="Student" /></th>
                        <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="student_details.admission_no" label="Admission No" /></th>
                        <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="student_details.grade" label="Class" /></th>
                        <th class="fw-semibold">Parent</th>
                        <th class="fw-semibold">Card</th>
                        <th class="fw-semibold text-end">Balance</th>
                        <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="student_details.status" label="Status" /></th>
                        <th class="fw-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        @php($parent = $item->guardians->first())
                        @php($balance = $balances[$item->id] ?? 0)
                        <tr wire:key="student-{{ $item->id }}">
                            <td class="ps-3"><input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="form-check-input" aria-label="Select {{ $item->name }}"></td>
                            <td class="text-nowrap">
                                <a href="{{ route('student::view', $item->id) }}" class="d-flex align-items-center gap-2 text-decoration-none">
                                    <img src="{{ $item->image_url }}" alt="" class="rounded-circle border" width="32" height="32" style="object-fit: cover">
                                    <span class="fw-medium">{{ $item->name }}</span>
                                </a>
                            </td>
                            <td class="text-nowrap">{{ $item->admission_no }}</td>
                            <td class="text-nowrap">{{ trim(implode(' - ', array_filter([$item->grade, $item->section]))) ?: '-' }}</td>
                            <td class="text-nowrap">
                                @if ($parent)
                                    <div>{{ $parent->name }}</div>
                                    <small class="text-body-secondary"><i class="fa fa-phone me-1"></i>{{ $parent->mobile }}</small>
                                @else
                                    <span class="text-body-secondary">-</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if (!$item->card_uid)
                                    <span class="badge bg-light text-body-secondary border">No card</span>
                                @elseif ($item->card_status === 'blocked')
                                    <span class="badge bg-danger"><i class="fa fa-lock me-1"></i>Blocked</span>
                                @else
                                    <span class="badge bg-success"><i class="fa fa-credit-card me-1"></i>Active</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap fw-semibold {{ $balance < 0 ? 'text-danger' : '' }}">{{ currency($balance) }}</td>
                            <td><span class="badge {{ $item->student_status === 'active' ? 'bg-primary' : 'bg-secondary' }}">{{ \App\Models\StudentDetail::STATUSES[$item->student_status] ?? $item->student_status }}</span></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('student::view', $item->id) }}" class="btn btn-light btn-sm" title="View"><i class="fa fa-eye"></i></a>
                                    @can('student.edit')
                                        <a href="{{ route('student::edit', $item->id) }}" class="btn btn-light btn-sm" title="Edit"><i class="fa fa-pencil"></i></a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-body-secondary py-4">
                                @if ($hiddenCount)
                                    <div class="mb-2">No students match these filters &mdash; {{ $hiddenCount }} {{ Str::plural('student', $hiddenCount) }} {{ $hiddenCount === 1 ? 'is' : 'are' }} hidden by them.</div>
                                    <button type="button" class="btn btn-light btn-sm" wire:click="clearFilters"><i class="fa fa-filter me-1"></i>Show all students</button>
                                @else
                                    No students yet.
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
