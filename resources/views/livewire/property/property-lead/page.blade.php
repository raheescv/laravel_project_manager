<div>
    <form wire:submit.prevent="save">
        <div class="row g-3">
            {{-- Main Form Card --}}
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fa fa-id-card-o text-primary me-2"></i>Lead Information
                        </h5>
                        <span class="badge {{ leadStatusBadgeClass($formData['status'] ?? 'New Lead') }} fs-6">
                            {{ $formData['status'] ?? 'New Lead' }}
                        </span>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger d-flex align-items-start mb-3" role="alert">
                                <i class="fa fa-exclamation-triangle me-2 mt-1"></i>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    {{ html()->text('formData.name')->id('name')->class('form-control' . ($errors->has('formData.name') ? ' is-invalid' : ''))->placeholder('Name')->attribute('wire:model', 'formData.name') }}
                                    <label for="name">Name <span class="text-danger">*</span></label>
                                    @error('formData.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    {{ html()->text('formData.mobile')->id('mobile')->class('form-control' . ($errors->has('formData.mobile') ? ' is-invalid' : ''))->placeholder('Mobile')->attribute('wire:model', 'formData.mobile') }}
                                    <label for="mobile">Mobile</label>
                                    @error('formData.mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    {{ html()->email('formData.email')->id('email')->class('form-control' . ($errors->has('formData.email') ? ' is-invalid' : ''))->placeholder('Email')->attribute('wire:model', 'formData.email') }}
                                    <label for="email">Email</label>
                                    @error('formData.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6" wire:ignore>
                                <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                                <select id="lead_status_select" class="tomSelect" placeholder="Select status...">
                                    <option value=""></option>
                                    @foreach($statuses as $key => $label)
                                        <option value="{{ $key }}" @selected(($formData['status'] ?? '') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    {{ html()->text('formData.company_name')->id('company_name')->class('form-control')->placeholder('Company')->attribute('wire:model', 'formData.company_name') }}
                                    <label for="company_name">Company Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    {{ html()->text('formData.company_contact_no')->id('company_contact_no')->class('form-control')->placeholder('Company Contact')->attribute('wire:model', 'formData.company_contact_no') }}
                                    <label for="company_contact_no">Company Contact No</label>
                                </div>
                            </div>
                            <div class="col-md-6" wire:ignore>
                                <label class="form-label small fw-semibold text-muted mb-1">Project / Group</label>
                                <select id="lead_property_group_id" class="select-property_group_id-list" placeholder="Search project / group...">
                                    <option value=""></option>
                                    @if(! empty($formData['property_group_id']))
                                        <option value="{{ $formData['property_group_id'] }}" selected>
                                            {{ $groups[$formData['property_group_id']] ?? '' }}
                                        </option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6" wire:ignore>
                                <label class="form-label small fw-semibold text-muted mb-1">Nationality</label>
                                <select id="lead_country_id" class="tomSelect" placeholder="Search nationality...">
                                    <option value=""></option>
                                    @foreach($countries as $id => $name)
                                        <option value="{{ $id }}" @selected(($formData['country_id'] ?? null) == $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    {{ html()->select('formData.source', ['' => '— Select Source —'] + $sources)->id('source')->class('form-select' . ($errors->has('formData.source') ? ' is-invalid' : ''))->attribute('wire:model', 'formData.source') }}
                                    <label for="source">Source <span class="text-danger">*</span></label>
                                    @error('formData.source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    {{ html()->select('formData.type', $types)->id('type')->class('form-select' . ($errors->has('formData.type') ? ' is-invalid' : ''))->attribute('wire:model', 'formData.type') }}
                                    <label for="type">Type <span class="text-danger">*</span></label>
                                    @error('formData.type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6" wire:ignore>
                                <label class="form-label small fw-semibold text-muted mb-1">Assigned To</label>
                                <select id="lead_assigned_to" class="select-employee_id-list" placeholder="Search salesman...">
                                    <option value=""></option>
                                    @if(! empty($formData['assigned_to']))
                                        <option value="{{ $formData['assigned_to'] }}" selected>
                                            {{ $users[$formData['assigned_to']] ?? (\App\Models\User::find($formData['assigned_to'])?->name ?? '') }}
                                        </option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    {{ html()->date('formData.assign_date')->id('assign_date')->class('form-control')->placeholder('Assign Date')->attribute('wire:model', 'formData.assign_date') }}
                                    <label for="assign_date">Assign Date</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    {{ html()->date('formData.meeting_date')->id('meeting_date')->class('form-control')->placeholder('Meeting Date')->attribute('wire:model', 'formData.meeting_date') }}
                                    <label for="meeting_date">Meeting Date</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    {{ html()->time('formData.meeting_time')->id('meeting_time')->class('form-control')->placeholder('Meeting Time')->attribute('wire:model', 'formData.meeting_time') }}
                                    <label for="meeting_time">Meeting Time</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    {{ html()->select('formData.location', ['' => '— Select Location —'] + $locations)->id('location')->class('form-select')->attribute('wire:model', 'formData.location') }}
                                    <label for="location">Location</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top py-3 d-flex flex-wrap gap-2 justify-content-between">
                        <a href="{{ route('property::lead::list') }}" class="btn btn-light">
                            <i class="fa fa-times me-1"></i> Cancel
                        </a>
                        <div class="d-flex gap-2">
                            @can('property lead.edit')
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fa fa-save me-1"></i> Save Lead
                                </button>
                            @endcan
                            @if($lead_id)
                                @can('property lead.booking transfer')
                                    <button type="button" wire:click="transfer" class="btn btn-success px-4"
                                        wire:confirm="Transfer this lead to a {{ ($formData['type'] ?? 'Sales') === 'Sales' ? 'Sale' : 'Rentout' }} booking?">
                                        <i class="fa fa-exchange me-1"></i> Transfer to {{ ($formData['type'] ?? 'Sales') === 'Sales' ? 'Sale' : 'Rentout' }} Booking
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            {{-- Notes Sidebar --}}
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-3 sticky-lg-top" style="top: 80px;">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fa fa-sticky-note-o text-primary me-2"></i>Notes &amp; Activity
                        </h5>
                        <span class="badge bg-primary-subtle text-primary">{{ count($notes) }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                {{ html()->date('noteDate')->class('form-control form-control-sm')->attribute('wire:model', 'noteDate') }}
                            </div>
                            <div class="col-12">
                                {{ html()->textarea('note')->rows(2)->class('form-control form-control-sm')->placeholder('Add a note... (press Enter to save)')->attribute('wire:model', 'note')->attribute('wire:keydown.enter.prevent', 'addNote') }}
                            </div>
                            <div class="col-12 d-grid">
                                <button type="button" wire:click="addNote" class="btn btn-primary btn-sm">
                                    <i class="fa fa-plus-circle me-1"></i> Add Note
                                </button>
                            </div>
                        </div>

                        <hr class="my-2">

                        @if(count($notes))
                            <ul class="list-unstyled mb-0 lead-notes-list">
                                @foreach(array_reverse($notes, true) as $key => $item)
                                    <li class="d-flex gap-2 py-2 border-bottom">
                                        <div class="flex-shrink-0">
                                            <span class="avatar-circle bg-primary-subtle text-primary">
                                                <i class="fa fa-comment-o"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div>
                                                    <div class="fw-semibold small text-dark">{{ $item['note'] ?? '' }}</div>
                                                    <div class="small text-muted">
                                                        <i class="fa fa-clock-o me-1"></i>{{ $item['date'] ?? '' }}
                                                        @if(! empty($item['user']))
                                                            · <i class="fa fa-user me-1"></i>{{ $item['user'] }}
                                                        @endif
                                                    </div>
                                                </div>
                                                @can('property lead.delete note')
                                                    <button type="button" wire:click="removeNote({{ $key }})" class="btn btn-link btn-sm text-danger p-0">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center text-muted py-4 small">
                                <i class="fa fa-comments-o fa-2x d-block mb-2 opacity-25"></i>
                                No notes added yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Audit Report (full width) --}}
        @if($lead_id && count($audits))
            @php
                $auditHideColumns = ['id','tenant_id','branch_id','remarks','deleted_at','meeting_datetime','created_by','updated_by','created_at','updated_at'];
                $displayAuditColumns = array_values(array_diff($auditColumns, $auditHideColumns));

                $renderLeadAuditValue = function ($col, $val) {
                    if ($val === null || $val === '') {
                        return '<span class="text-muted">-</span>';
                    }
                    switch ($col) {
                        case 'country_id':
                            return e(optional(\App\Models\Country::find($val))->name ?? '-');
                        case 'assigned_to':
                            return e(optional(\App\Models\User::find($val))->name ?? '-');
                        case 'property_group_id':
                            return e(optional(\App\Models\PropertyGroup::find($val))->name ?? '-');
                        case 'status':
                            return '<span class="badge ' . e(leadStatusBadgeClass($val)) . '">' . e($val) . '</span>';
                        case 'type':
                            $cls = $val === 'Sales' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info';
                            return '<span class="badge ' . $cls . '">' . e($val) . '</span>';
                        default:
                            return e($val);
                    }
                };
            @endphp
            <div class="card shadow-sm border-0 mb-3 lead-audit-card">
                <div class="card-header lead-audit-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-semibold text-white">
                        <i class="fa fa-history me-2"></i>Audit Report
                        <span class="badge bg-white text-dark ms-2">{{ count($audits) }} {{ \Illuminate\Support\Str::plural('record', count($audits)) }}</span>
                    </h5>
                </div>
                <div class="card-body p-2">
                    <div class="audit-pivot-list">
                        <div class="audit-pivot-scroll">
                            <table class="table table-bordered table-sm align-middle mb-0 audit-pivot-table">
                                <thead>
                                    <tr>
                                        <th class="audit-pivot-field-col audit-pivot-sticky">Field</th>
                                        @foreach($audits as $key => $audit)
                                            @php
                                                $userName = $audit['user']['name'] ?? null;
                                                $userInitial = $userName ? strtoupper(substr($userName, 0, 1)) : 'S';
                                            @endphp
                                            <th class="audit-pivot-change-col">
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="audit-entry-index badge bg-light text-dark border align-self-start">#{{ $key + 1 }}</span>
                                                    <span class="text-nowrap small fw-normal"><i class="fa fa-clock-o me-1 text-primary"></i>{{ \Carbon\Carbon::parse($audit['created_at'])->format('Y-m-d H:i:s') }}</span>
                                                    <span class="text-nowrap small fw-normal">
                                                        <span class="audit-user-avatar bg-primary-subtle text-primary me-1">{{ $userInitial }}</span>
                                                        <span class="fw-semibold">{{ $userName ?: 'System' }}</span>
                                                    </span>
                                                </div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($displayAuditColumns as $col)
                                        <tr>
                                            <th class="audit-pivot-field-col audit-pivot-sticky text-muted">{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                                            @foreach($audits as $audit)
                                                @php
                                                    $newVals = $audit['new_values'] ?? [];
                                                    $oldVals = $audit['old_values'] ?? [];
                                                    $hasOld = array_key_exists($col, $oldVals);
                                                    $hasNew = array_key_exists($col, $newVals);
                                                    $oldVal = $oldVals[$col] ?? null;
                                                    $newVal = $newVals[$col] ?? null;
                                                    $changed = $hasOld && $hasNew && $oldVal !== $newVal;
                                                @endphp
                                                <td class="audit-pivot-change-col">
                                                    @if ($changed)
                                                        <span class="text-danger text-decoration-line-through">{!! $renderLeadAuditValue($col, $oldVal) !!}</span>
                                                        <i class="fa fa-arrow-right text-muted mx-1"></i>
                                                        <span class="text-success fw-semibold">{!! $renderLeadAuditValue($col, $newVal) !!}</span>
                                                    @elseif ($hasNew)
                                                        <span class="text-success">{!! $renderLeadAuditValue($col, $newVal) !!}</span>
                                                    @elseif ($hasOld)
                                                        <span class="text-danger">{!! $renderLeadAuditValue($col, $oldVal) !!}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($audits) + 1 }}" class="text-center text-muted small py-3">No field changes recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </form>

    @push('styles')
        <style>
            .avatar-circle {
                width: 32px; height: 32px;
                display: inline-flex; align-items: center; justify-content: center;
                border-radius: 50%; font-size: 0.85rem;
            }
            .lead-notes-list li:last-child { border-bottom: 0 !important; }

            /* === Audit Report (compact) === */
            .lead-audit-card { overflow: hidden; }
            .lead-audit-header {
                background: linear-gradient(135deg, #38b2ac 0%, #319795 100%) !important;
                border: 0;
                padding: .5rem .85rem !important;
            }
            .lead-audit-header h5 { font-size: .9rem; }
            .lead-audit-header .badge { font-size: .68rem; padding: .22em .48em; }

            /* === Pivot audit table (shared with x-audit.table component) === */
            .audit-pivot-list .audit-pivot-scroll {
                overflow-x: auto;
                overflow-y: visible;
                position: relative;
                max-width: 100%;
            }
            .audit-pivot-list .audit-pivot-table {
                font-size: 0.82rem;
                border-collapse: separate;
                border-spacing: 0;
                margin-bottom: 0;
                width: auto;
                min-width: 100%;
            }
            .audit-pivot-list .audit-pivot-table thead th {
                background: #f4f6fa;
                vertical-align: top;
                font-weight: 600;
                color: #2d3a4b;
                padding: 0.5rem 0.6rem;
                border-bottom: 2px solid #dee2e6;
                white-space: nowrap;
            }
            .audit-pivot-list .audit-pivot-table tbody th {
                background: #fafbfc;
                font-weight: 600;
                color: #6c757d;
                padding: 0.4rem 0.6rem;
                white-space: nowrap;
                text-transform: capitalize;
            }
            .audit-pivot-list .audit-pivot-table tbody td {
                padding: 0.4rem 0.6rem;
                vertical-align: middle;
                word-break: break-word;
            }
            .audit-pivot-list .audit-pivot-sticky {
                position: sticky; left: 0; z-index: 2;
                box-shadow: 1px 0 0 #dee2e6;
            }
            .audit-pivot-list thead .audit-pivot-sticky { z-index: 4; background: #f4f6fa !important; }
            .audit-pivot-list tbody .audit-pivot-sticky { background: #fafbfc !important; }
            .audit-pivot-list .audit-pivot-field-col { min-width: 160px; max-width: 220px; }
            .audit-pivot-list .audit-pivot-change-col { min-width: 180px; }
            .audit-pivot-list .audit-user-avatar {
                width: 20px; height: 20px;
                display: inline-flex; align-items: center; justify-content: center;
                border-radius: 50%; font-weight: 600; font-size: 0.65rem;
            }
            .audit-pivot-list .audit-entry-index { font-size: 0.7rem; padding: 0.2em 0.5em; }
            @media (max-width: 575.98px) {
                .audit-pivot-list .audit-pivot-table { font-size: 0.76rem; }
                .audit-pivot-list .audit-pivot-field-col { min-width: 130px; max-width: 160px; }
                .audit-pivot-list .audit-pivot-change-col { min-width: 150px; }
            }
            /* TomSelect look inside lead form */
            .lead-ts-status + .ts-wrapper .ts-control,
            .lead-ts-country + .ts-wrapper .ts-control,
            .select-employee_id-list + .ts-wrapper .ts-control,
            .select-property_group_id-list + .ts-wrapper .ts-control {
                min-height: 48px;
                border-radius: .5rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                $('#lead_country_id').change(function(){
                    @this.set('formData.country_id', $(this).val());
                });
                $('#lead_status_select').change(function(){
                    @this.set('formData.status', $(this).val());
                });
                $('#lead_property_group_id').change(function(){
                    @this.set('formData.property_group_id', $(this).val());
                });
                $('#lead_assigned_to').change(function(){
                    @this.set('formData.assigned_to', $(this).val());
                });
            })();
        </script>
    @endpush
</div>
