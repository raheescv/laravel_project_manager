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
            @endphp
            <div class="card shadow-sm border-0 mb-3 lead-audit-card">
                <div class="card-header lead-audit-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-semibold text-white">
                        <i class="fa fa-history me-2"></i>Audit Report
                        <span class="badge bg-white text-dark ms-2">{{ count($audits) }} {{ \Illuminate\Support\Str::plural('record', count($audits)) }}</span>
                    </h5>
                    <span class="small text-white-50 d-none d-md-inline">
                        <i class="fa fa-info-circle me-1"></i>Scroll horizontally to view all fields
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle border-bottom mb-0 lead-audit-table">
                            <thead>
                                <tr>
                                    <th class="fw-semibold text-uppercase text-muted">#</th>
                                    <th class="fw-semibold text-uppercase text-muted text-nowrap"><i class="fa fa-calendar me-1"></i>Date &amp; Time</th>
                                    <th class="fw-semibold text-uppercase text-muted text-nowrap"><i class="fa fa-user me-1"></i>User</th>
                                    @foreach($displayAuditColumns as $col)
                                        <th class="fw-semibold text-uppercase text-muted text-nowrap">{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($audits as $key => $audit)
                                    @php
                                        $values = ($audit['new_values'] ?? []) + ($audit['old_values'] ?? []);
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $key + 1 }}</td>
                                        <td class="text-nowrap">
                                            <i class="fa fa-clock-o text-muted me-1"></i>{{ \Carbon\Carbon::parse($audit['created_at'])->format('Y-m-d H:i:s') }}
                                        </td>
                                        <td class="text-nowrap">
                                            @if(! empty($audit['user']['name']))
                                                <span class="avatar-sm bg-primary-subtle text-primary me-1">{{ strtoupper(substr($audit['user']['name'], 0, 1)) }}</span><span class="fw-semibold">{{ $audit['user']['name'] }}</span>
                                            @else
                                                <span class="text-muted">System</span>
                                            @endif
                                        </td>
                                        @foreach($displayAuditColumns as $col)
                                            @php $val = $values[$col] ?? null; @endphp
                                            <td class="text-nowrap">
                                                @switch($col)
                                                    @case('country_id')
                                                        {{ $val ? optional(\App\Models\Country::find($val))->name : '' }}
                                                        @break
                                                    @case('assigned_to')
                                                        {{ $val ? optional(\App\Models\User::find($val))->name : '' }}
                                                        @break
                                                    @case('property_group_id')
                                                        {{ $val ? optional(\App\Models\PropertyGroup::find($val))->name : '' }}
                                                        @break
                                                    @case('status')
                                                        @if($val)
                                                            <span class="badge {{ leadStatusBadgeClass($val) }}">{{ $val }}</span>
                                                        @endif
                                                        @break
                                                    @case('type')
                                                        @if($val)
                                                            <span class="badge {{ $val === 'Sales' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }}">{{ $val }}</span>
                                                        @endif
                                                        @break
                                                    @default
                                                        {{ $val }}
                                                @endswitch
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
            .lead-audit-table {
                font-size: .74rem;
                margin-bottom: 0 !important;
            }
            .lead-audit-table thead th {
                background: #f8f9fb;
                border-top: 1px solid #e9ecef;
                border-bottom: 1px solid #e9ecef;
                padding: .35rem .55rem !important;
                white-space: nowrap;
                letter-spacing: .2px;
                font-size: .64rem;
                line-height: 1.1;
            }
            .lead-audit-table tbody td {
                padding: .3rem .55rem !important;
                border-bottom: 1px solid #f1f3f5;
                vertical-align: middle;
                line-height: 1.2;
            }
            .lead-audit-table tbody tr:hover { background: #fafbff; }
            .lead-audit-table .avatar-sm {
                width: 22px; height: 22px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                font-weight: 600;
                font-size: .65rem;
            }
            .lead-audit-table .badge {
                font-size: .62rem;
                padding: .22em .45em;
                font-weight: 600;
            }
            .lead-audit-table .fa { font-size: .72rem; }
            /* Override Bootstrap table-sm default */
            .lead-audit-table.table-sm > :not(caption) > * > * { padding: .3rem .55rem !important; }
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
