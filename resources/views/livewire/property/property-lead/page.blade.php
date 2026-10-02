@use('App\Support\LeadPipeline')
@php
    $type = $formData['type'] ?? 'Sales';
    $isSaleType = in_array($type, ['Sales', 'Corporate'], true);
    $statusNow = $formData['status'] ?? 'New Lead';
    $statusTone = LeadPipeline::tone(LeadPipeline::canonical($statusNow));
    $stageKey = LeadPipeline::stageOf(LeadPipeline::canonical($statusNow));
    $stageName = $stageKey ? LeadPipeline::stages()[$stageKey]['name'] : 'Needs fixing';
    $typeIcons = ['Sales' => 'fa-tag', 'Rentout' => 'fa-key', 'Corporate' => 'fa-building'];
    $typeLabels = ['Rentout' => 'Rent out'];
    $budgetMin = $formData['budget_min'] ?? null;
    $budgetMax = $formData['budget_max'] ?? null;
    $budgetText = match (true) {
        filled($budgetMin) && filled($budgetMax) => currency($budgetMin).' – '.currency($budgetMax),
        filled($budgetMin) => 'From '.currency($budgetMin),
        filled($budgetMax) => 'Up to '.currency($budgetMax),
        default => null,
    };
    $meetingAt = filled($formData['meeting_date'] ?? null)
        ? \Carbon\Carbon::parse($formData['meeting_date'].' '.($formData['meeting_time'] ?: '00:00'))
        : null;
    $assigneeName = filled($formData['assigned_to'] ?? null) ? \App\Models\User::find($formData['assigned_to'])?->name : null;
    $locationRequired = LeadPipeline::canonical($statusNow) === 'Visit Scheduled';
    $field = fn (string $key) => 'ctl'.($errors->has("formData.$key") ? ' is-invalid' : '');
@endphp
<div class="lfx">
    <x-property.lead-form.premium />

    <form wire:submit.prevent="save"
        x-data="{ edited: false }"
        x-on:input="if (! $event.target.closest('[data-dirty-ignore]')) edited = true"
        x-on:change="if (! $event.target.closest('[data-dirty-ignore]')) edited = true"
        x-on:lead-saved.window="edited = false">
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="lfx-card">
                    {{-- Identity --}}
                    <div class="hero">
                        <div class="avatar" style="--hue: {{ LeadPipeline::hue($formData['name'] ?? '') }}">{{ LeadPipeline::initials($formData['name'] ?? '') ?: '?' }}</div>
                        <div class="hero-main">
                            <input type="text" wire:model.blur="formData.name" class="hero-name {{ $errors->has('formData.name') ? 'is-invalid' : '' }}" placeholder="Lead name" aria-label="Lead name">
                            <div class="hero-meta">
                                @if($lead_id)
                                    <span><i class="fa fa-bookmark-o"></i>Lead #{{ $lead_id }}</span>
                                @else
                                    <span><i class="fa fa-magic"></i>New lead</span>
                                @endif
                                <span><i class="fa fa-flag-o"></i>{{ $stageName }} stage</span>
                                @if($assigneeName)
                                    <span><i class="fa fa-user"></i>{{ $assigneeName }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="seg" role="radiogroup" aria-label="Lead type">
                            @foreach($types as $key => $label)
                                <input type="radio" id="lead_type_{{ $key }}" value="{{ $key }}" wire:model.live="formData.type">
                                <label for="lead_type_{{ $key }}"><i class="fa {{ $typeIcons[$key] ?? 'fa-circle-o' }}"></i>{{ $typeLabels[$key] ?? $label }}</label>
                            @endforeach
                        </div>
                        <span class="pill tn tn-{{ $statusTone }}"><span class="dot"></span>{{ $statusNow }}</span>
                    </div>

                    @if ($errors->any())
                        <div class="alert-x" role="alert">
                            <i class="fa fa-exclamation-triangle mt-1"></i>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Contact --}}
                    <div class="sec">
                        <div class="sec-h">
                            <span class="sec-ic"><i class="fa fa-user"></i></span>
                            <div>
                                <p class="sec-t">Contact</p>
                                <p class="sec-s">Mobile or email is required</p>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4 fld">
                                <label for="mobile">Mobile <span class="req" title="Mobile or email is required">*</span></label>
                                <div class="adorn">
                                    <i class="fa fa-phone"></i>
                                    <input type="tel" inputmode="numeric" id="mobile" wire:model="formData.mobile" class="{{ $field('mobile') }}" placeholder="Digits only, e.g. 97455551234">
                                </div>
                                @error('formData.mobile') <div class="err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4 fld">
                                <label for="email">Email <span class="req" title="Mobile or email is required">*</span></label>
                                <div class="adorn">
                                    <i class="fa fa-envelope-o"></i>
                                    <input type="email" id="email" wire:model="formData.email" class="{{ $field('email') }}" placeholder="name@example.com">
                                </div>
                                @error('formData.email') <div class="err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4 fld">
                                <div wire:ignore>
                                    <label for="lead_country_id">Nationality <span class="req">*</span></label>
                                    <select id="lead_country_id" class="tomSelect" placeholder="Search nationality...">
                                        <option value=""></option>
                                        @foreach($countries as $id => $name)
                                            <option value="{{ $id }}" @selected(($formData['country_id'] ?? null) == $id)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('formData.country_id') <div class="err">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Company --}}
                    <div class="sec">
                        <div class="sec-h">
                            <span class="sec-ic"><i class="fa fa-building-o"></i></span>
                            <div>
                                <p class="sec-t">Company</p>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4 fld">
                                <label for="company_name">Company name</label>
                                <input type="text" id="company_name" wire:model="formData.company_name" class="ctl" placeholder="Company">
                            </div>
                            <div class="col-md-4 fld">
                                <label for="company_contact_person">Contact person</label>
                                <div class="adorn">
                                    <i class="fa fa-user"></i>
                                    <input type="text" id="company_contact_person" wire:model="formData.company_contact_person" class="ctl" placeholder="Full name">
                                </div>
                            </div>
                            <div class="col-md-4 fld">
                                <label for="company_contact_no">Contact no</label>
                                <div class="adorn">
                                    <i class="fa fa-phone"></i>
                                    <input type="tel" id="company_contact_no" wire:model="formData.company_contact_no" class="ctl" placeholder="Office number">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Source & pipeline --}}
                    <div class="sec">
                        <div class="sec-h">
                            <span class="sec-ic"><i class="fa fa-filter"></i></span>
                            <div>
                                <p class="sec-t">Source &amp; pipeline</p>
                            </div>
                        </div>
                        <div class="row g-2">
                            @foreach([
                                ['field' => 'source', 'label' => 'Source', 'options' => $sources, 'required' => true, 'sub' => 'sub_source', 'subLabel' => 'Sub source', 'subOptions' => $subSources, 'noun' => 'source'],
                                ['field' => 'status', 'label' => 'Status', 'options' => $statuses, 'required' => false, 'sub' => 'sub_status', 'subLabel' => 'Sub status', 'subOptions' => $subStatuses, 'noun' => 'status'],
                            ] as $pick)
                                @php $parent = $formData[$pick['field']] ?? null; @endphp
                                <div class="col-sm-6 col-lg-3 fld">
                                    <label for="{{ $pick['field'] }}">{{ $pick['label'] }} @if($pick['required'])<span class="req">*</span>@endif</label>
                                    <select id="{{ $pick['field'] }}" wire:model.live="formData.{{ $pick['field'] }}" class="{{ $field($pick['field']) }}">
                                        <option value="">Select {{ $pick['noun'] }}</option>
                                        @foreach($pick['options'] as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('formData.'.$pick['field']) <div class="err">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-sm-6 col-lg-3 fld">
                                    <label for="{{ $pick['sub'] }}">{{ $pick['subLabel'] }}</label>
                                    <select id="{{ $pick['sub'] }}" wire:model="formData.{{ $pick['sub'] }}" wire:key="{{ $pick['sub'] }}-{{ md5((string) $parent) }}" class="{{ $field($pick['sub']) }}" @disabled(! count($pick['subOptions']))>
                                        @if(blank($parent))
                                            <option value="">Pick a {{ $pick['noun'] }} first</option>
                                        @elseif(! count($pick['subOptions']))
                                            <option value="">None for {{ $parent }}</option>
                                        @else
                                            <option value="">Select {{ strtolower($pick['subLabel']) }}</option>
                                            @foreach($pick['subOptions'] as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @if(filled($parent) && ! count($pick['subOptions']))
                                        @can('configuration.settings')
                                            <div class="hint"><a href="{{ route('settings::index', ['tab' => 'lead-settings']) }}" class="text-decoration-none" target="_blank"><i class="fa fa-plus-circle me-1"></i>Add in Lead Settings</a></div>
                                        @endcan
                                    @endif
                                    @error('formData.'.$pick['sub']) <div class="err">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Property requirements --}}
                    <div class="sec">
                        <div class="sec-h">
                            <span class="sec-ic"><i class="fa fa-home"></i></span>
                            <div>
                                <p class="sec-t">Property requirements</p>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-sm-6 col-lg-6 fld" wire:ignore>
                                <label for="lead_property_group_id">Project / group</label>
                                <select id="lead_property_group_id" class="select-property_group_id-list" placeholder="Search project / group...">
                                    <option value=""></option>
                                    @if(! empty($formData['property_group_id']))
                                        <option value="{{ $formData['property_group_id'] }}" selected>{{ $groups[$formData['property_group_id']] ?? '' }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-6 fld" wire:ignore>
                                <label for="lead_property_type_id">Property type</label>
                                <select id="lead_property_type_id" class="select-property_type_id-list" placeholder="Search property type...">
                                    <option value=""></option>
                                    @if(! empty($formData['property_type_id']))
                                        <option value="{{ $formData['property_type_id'] }}" selected>{{ $propertyTypes[$formData['property_type_id']] ?? '' }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6 fld">
                                <span class="fld-l">Budget range</span>
                                <div class="budget">
                                    <div class="adorn">
                                        <i class="fa fa-money"></i>
                                        <input type="number" min="0" step="any" inputmode="decimal" wire:model.blur="formData.budget_min" class="{{ $field('budget_min') }}" placeholder="Min" aria-label="Budget min">
                                    </div>
                                    <span class="dash">–</span>
                                    <div class="adorn">
                                        <i class="fa fa-money"></i>
                                        <input type="number" min="0" step="any" inputmode="decimal" wire:model.blur="formData.budget_max" class="{{ $field('budget_max') }}" placeholder="Max" aria-label="Budget max">
                                    </div>
                                </div>
                                @error('formData.budget_min') <div class="err">{{ $message }}</div> @enderror
                                @error('formData.budget_max') <div class="err">{{ $message }}</div> @enderror
                            </div>
                            @if($type === 'Rentout')
                            <div class="col-md-6 fld">
                                <span class="fld-l">Rental type</span>
                                <div class="seg block" role="radiogroup" aria-label="Rental type">
                                    <input type="radio" id="rental_type_any" value="" wire:model.live="formData.rental_type">
                                    <label for="rental_type_any">Any</label>
                                    @foreach($rentalTypes as $key => $label)
                                        <input type="radio" id="rental_type_{{ $key }}" value="{{ $key }}" wire:model.live="formData.rental_type">
                                        <label for="rental_type_{{ $key }}">{{ $label }}</label>
                                    @endforeach
                                </div>
                                @error('formData.rental_type') <div class="err">{{ $message }}</div> @enderror
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Assignment & meeting --}}
                    <div class="sec">
                        <div class="sec-h">
                            <span class="sec-ic"><i class="fa fa-calendar"></i></span>
                            <div>
                                <p class="sec-t">Assignment &amp; meeting</p>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-sm-6 col-lg-3 fld" wire:ignore>
                                <label for="lead_assigned_to">Assigned to</label>
                                <select id="lead_assigned_to" class="select-employee_id-list" data-designations="{{ implode(',', \App\Support\LeadOptions::assigneeDesignationIds()) }}" placeholder="Search salesman...">
                                    <option value=""></option>
                                    @if(! empty($formData['assigned_to']))
                                        <option value="{{ $formData['assigned_to'] }}" selected>{{ $assigneeName }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3 fld">
                                <label for="assign_date">Assign date</label>
                                <input type="date" id="assign_date" wire:model="formData.assign_date" class="ctl">
                            </div>
                            <div class="col-md-6 col-lg-3 fld">
                                <label for="meeting_date">Meeting date</label>
                                <input type="date" id="meeting_date" wire:model.blur="formData.meeting_date" class="ctl">
                            </div>
                            <div class="col-md-6 col-lg-3 fld">
                                <label for="meeting_time">Meeting time</label>
                                <input type="time" id="meeting_time" wire:model.blur="formData.meeting_time" class="ctl">
                            </div>
                            <div class="col-lg-6 fld">
                                <span class="fld-l">Location @if($locationRequired)<span class="req">*</span>@endif</span>
                                <div class="seg block" role="radiogroup" aria-label="Meeting location">
                                    @foreach($locations as $key => $label)
                                        <input type="radio" id="location_{{ \Illuminate\Support\Str::slug($key) }}" value="{{ $key }}" wire:model.live="formData.location">
                                        <label for="location_{{ \Illuminate\Support\Str::slug($key) }}">{{ $label }}</label>
                                    @endforeach
                                </div>
                                @error('formData.location') <div class="err">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="foot">
                        <a href="{{ route('property::lead::list') }}" class="btn-x"><i class="fa fa-times"></i> Cancel</a>
                        <div class="d-flex flex-wrap gap-2">
                            @can($lead_id ? 'property lead.edit' : 'property lead.create')
                                <button type="submit" class="btn-x pri" wire:loading.attr="disabled" wire:target="save">
                                    <i class="fa fa-check" wire:loading.remove wire:target="save"></i>
                                    <i class="fa fa-spinner fa-spin" wire:loading wire:target="save"></i>
                                    {{ $lead_id ? 'Save changes' : 'Create lead' }}
                                </button>
                            @endcan
                            @if($lead_id)
                                @can('property lead.booking transfer')
                                    <button type="button" wire:click="transfer" class="btn-x ok" @disabled($hasUnsavedChanges) x-bind:disabled="edited || $wire.hasUnsavedChanges" x-bind:title="(edited || $wire.hasUnsavedChanges) ? 'Save your changes before transferring' : ''" wire:confirm="Transfer this lead to a {{ $isSaleType ? 'Sale' : 'Rentout' }} booking?">
                                        <i class="fa fa-exchange"></i> Transfer to {{ $isSaleType ? 'Sale' : 'Rentout' }} booking
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Side rail --}}
            <div class="col-xl-4">
                <div class="sticky-xl-top" style="top: 80px;">
                    <div class="lfx-card">
                        <div class="rail-h">
                            <h6><i class="fa fa-bolt me-2 text-primary"></i>Snapshot</h6>
                        </div>
                        <div class="rail-b">
                            <div class="snap">
                                <div class="snap-i">
                                    <div class="snap-k">Type</div>
                                    <div class="snap-v"><i class="fa {{ $typeIcons[$type] ?? 'fa-circle-o' }} me-1 text-primary"></i>{{ $typeLabels[$type] ?? $type }}</div>
                                </div>
                                <div class="snap-i">
                                    <div class="snap-k">Stage</div>
                                    <div class="snap-v">{{ $stageName }}</div>
                                </div>
                                <div class="snap-i wide">
                                    <div class="snap-k">Budget{{ $type === 'Rentout' && filled($formData['rental_type'] ?? null) ? ' · '.$formData['rental_type'] : '' }}</div>
                                    <div class="snap-v {{ $budgetText ? '' : 'empty' }}">{{ $budgetText ?? 'Not captured' }}</div>
                                </div>
                                <div class="snap-i wide">
                                    <div class="snap-k">Next meeting</div>
                                    @if($meetingAt)
                                        <div class="snap-v">{{ filled($formData['meeting_time'] ?? null) ? systemDateTime($meetingAt) : systemDate($meetingAt) }}</div>
                                        <div class="small text-muted">{{ $meetingAt->diffForHumans() }}{{ filled($formData['location'] ?? null) ? ' · '.$formData['location'] : '' }}</div>
                                    @else
                                        <div class="snap-v empty">Not scheduled</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lfx-card">
                        <div class="rail-h">
                            <h6><i class="fa fa-pencil-square-o me-2 text-primary"></i>Notes &amp; activity</h6>
                            <span class="count">{{ count($notes) }}</span>
                        </div>
                        <div class="rail-b">
                            <div class="note-in" data-dirty-ignore>
                                <input type="date" wire:model="noteDate" class="ctl" aria-label="Note date">
                                <button type="button" wire:click="addNote" class="btn-x pri justify-content-center" style="grid-column: auto"><i class="fa fa-plus"></i> Add note</button>
                                <textarea rows="2" wire:model="note" wire:keydown.enter.prevent="addNote" class="ctl" placeholder="Add a note… (Enter to add)"></textarea>
                            </div>

                            @if(count($notes))
                                <ul class="timeline">
                                    @foreach(array_reverse($notes, true) as $key => $item)
                                        <li>
                                            <span class="tdot"><i class="fa fa-comment-o"></i></span>
                                            <div class="tbody">
                                                <div class="tnote">{{ $item['note'] ?? '' }}</div>
                                                <div class="tmeta">
                                                    @php
                                                        // Older notes carry only a date; newer ones also record when they were added.
                                                        $notedAt = ! empty($item['created_at']) ? \Carbon\Carbon::parse($item['created_at']) : (! empty($item['date']) ? \Carbon\Carbon::parse($item['date']) : null);
                                                    @endphp
                                                    @if($notedAt)
                                                        <span title="{{ ! empty($item['created_at']) ? systemDateTime($notedAt) : systemDate($notedAt) }}">
                                                            <i class="fa fa-clock-o me-1"></i>{{ ! empty($item['created_at']) ? $notedAt->diffForHumans() : ($notedAt->isToday() ? 'Today' : $notedAt->diffForHumans(['parts' => 1])) }}
                                                            · {{ ! empty($item['created_at']) ? systemDateTime($notedAt) : systemDate($notedAt) }}
                                                        </span>
                                                    @endif
                                                    @if(! empty($item['user']))
                                                        <span><i class="fa fa-user me-1"></i>{{ $item['user'] }}</span>
                                                    @endif
                                                    @can('property lead.delete note')
                                                        <button type="button" wire:click="removeNote({{ $key }})" class="tdel" aria-label="Delete note"><i class="fa fa-trash-o"></i></button>
                                                    @endcan
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="empty-s"><i class="fa fa-comments-o"></i>No notes added yet.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

        {{-- Change history: one row per change, one column per field --}}
        @if($auditTrail)
            @php
                $auditRows = $auditTrail['rows'];
                $auditColumnsShown = $auditTrail['columns'];
                $auditPreview = 15;
                $eventChip = ['created' => ['Created', 'success', 'fa-plus'], 'updated' => ['Updated', 'primary', 'fa-pencil'], 'deleted' => ['Deleted', 'danger', 'fa-trash-o'], 'restored' => ['Restored', 'info', 'fa-undo']];
            @endphp
            <div class="lfx-card atx" x-data="{ all: false }">
                <div class="rail-h">
                    <h6><i class="fa fa-history me-2 text-primary"></i>Change history</h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted d-none d-sm-inline">Newest first</span>
                        <span class="count">{{ count($auditRows) }}</span>
                    </div>
                </div>

                @if(count($auditRows))
                    <div class="atx-scroll">
                        <table class="atx-table">
                            <thead>
                                <tr>
                                    <th class="atx-sticky">Changed</th>
                                    @foreach($auditColumnsShown as $label)
                                        <th>{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($auditRows as $index => $row)
                                    @php [$chipLabel, $chipTone, $chipIcon] = $eventChip[$row['event']] ?? [ucfirst($row['event']), 'secondary', 'fa-circle-o']; @endphp
                                    <tr @if($index >= $auditPreview) x-show="all" x-cloak @endif>
                                        <th class="atx-sticky" scope="row">
                                            <div class="atx-who">
                                                <span class="atx-av" style="--hue: {{ LeadPipeline::hue($row['user'] ?? 'System') }}">{{ $row['user'] ? LeadPipeline::initials($row['user']) : 'S' }}</span>
                                                <div style="min-width: 0">
                                                    <div class="atx-name">{{ $row['user'] ?? 'System' }}</div>
                                                    @if($row['at'])
                                                        <div class="atx-when" title="{{ systemDateTime($row['at']) }}">{{ $row['at']->diffForHumans() }} · {{ systemDateTime($row['at']) }}</div>
                                                    @endif
                                                </div>
                                                <span class="atx-chip tn tn-{{ $chipTone }}"><i class="fa {{ $chipIcon }}"></i>{{ $chipLabel }}</span>
                                            </div>
                                        </th>
                                        @foreach($auditColumnsShown as $field => $label)
                                            @php $cell = $row['cells'][$field] ?? null; @endphp
                                            <td class="{{ $cell ? 'is-changed' : '' }}">
                                                @if(! $cell)
                                                    <span class="atx-none">·</span>
                                                @elseif($field === 'remarks')
                                                    @foreach(array_filter(explode("\n", (string) $cell['new'])) as $note)
                                                        <div class="atx-note"><i class="fa fa-plus"></i>{{ $note }}</div>
                                                    @endforeach
                                                    @foreach(array_filter(explode("\n", (string) $cell['old'])) as $note)
                                                        <div class="atx-note is-removed"><i class="fa fa-minus"></i>{{ $note }}</div>
                                                    @endforeach
                                                @else
                                                    @if($row['event'] !== 'created')
                                                        <div class="atx-old">{{ $cell['old'] ?? 'empty' }}</div>
                                                    @endif
                                                    <div class="atx-new">
                                                        @if($field === 'status' && $cell['new'])
                                                            <span class="atx-dot tn tn-{{ LeadPipeline::tone(LeadPipeline::canonical($cell['new'])) }}"></span>
                                                        @endif
                                                        {{ $cell['new'] ?? 'cleared' }}
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(count($auditRows) > $auditPreview)
                        <div class="atx-more">
                            <button type="button" class="btn-x" x-on:click="all = ! all">
                                <i class="fa" x-bind:class="all ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                <span x-text="all ? 'Show latest {{ $auditPreview }}' : 'Show all {{ count($auditRows) }} changes'"></span>
                            </button>
                        </div>
                    @endif
                @else
                    <div class="empty-s"><i class="fa fa-history"></i>No changes recorded yet. Edits from now on will show here, field by field.</div>
                @endif
            </div>
        @endif

    @push('scripts')
        <script>
            (function () {
                $('#lead_country_id').change(function(){
                    @this.set('formData.country_id', $(this).val());
                });
                $('#lead_property_group_id').change(function(){
                    @this.set('formData.property_group_id', $(this).val());
                });
                $('#lead_property_type_id').change(function(){
                    @this.set('formData.property_type_id', $(this).val());
                });
                $('#lead_assigned_to').change(function(){
                    @this.set('formData.assigned_to', $(this).val());
                });
            })();
        </script>
    @endpush
</div>
