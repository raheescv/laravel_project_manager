<div class="rgx">
    @php
        $canApprove = auth()->user()?->can('student registration.approve');
        $initialsOf = function (?string $name): string {
            $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [''];

            return mb_strtoupper(mb_substr($parts[0], 0, 1).(count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
        };
        $gradeLabel = fn (?string $grade): string => is_numeric($grade) ? "Grade {$grade}" : (string) $grade;
        $approveTextFor = function ($guardian): string {
            $count = $guardian->pendingStudents->count();

            return ($count === 1 ? 'This adds 1 student.' : "This adds {$count} students.").($guardian->isPendingApproval() ? ' The parent is emailed a portal link.' : '');
        };
        $rejectTextFor = fn ($guardian): string => $guardian->isPendingApproval() ? 'The students and this parent are removed.' : 'The students are removed. The existing parent stays as they are.';
    @endphp

    <header class="rgx-head">
        <div class="rgx-stats">
            <div class="rgx-stat"><b>{{ $stats['families'] }}</b><span>Families waiting</span></div>
            <div class="rgx-stat"><b>{{ $stats['students'] }}</b><span>Students</span></div>
            <div class="rgx-stat"><b>{{ $stats['existing'] }}</b><span>Existing parents</span></div>
        </div>
    </header>

    <div class="rgx-filters">
        <label class="rgx-search">
            <i class="fa fa-search"></i>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Parent, mobile, QID, email or student…" aria-label="Search" autocomplete="off">
        </label>
        <div class="rgx-chips" role="group" aria-label="School">
            <button type="button" @class(['rgx-chip', 'is-on' => $school === '']) wire:click="$set('school', '')">All schools<i>{{ $stats['families'] }}</i></button>
            @foreach ($schools as $schoolName => $familyCount)
                <button type="button" @class(['rgx-chip', 'is-on' => $school === $schoolName]) wire:click="$set('school', @js($schoolName))" wire:key="school-{{ $loop->index }}">{{ $schoolName }}<i>{{ $familyCount }}</i></button>
            @endforeach
        </div>
        <div class="rgx-chips" role="group" aria-label="Parent">
            <button type="button" @class(['rgx-chip', 'is-on' => $parentType === '']) wire:click="$set('parentType', '')">All parents<i>{{ $stats['families'] }}</i></button>
            <button type="button" @class(['rgx-chip', 'is-on' => $parentType === 'new']) wire:click="$set('parentType', 'new')">New<i>{{ $stats['families'] - $stats['existing'] }}</i></button>
            <button type="button" @class(['rgx-chip', 'is-on' => $parentType === 'existing']) wire:click="$set('parentType', 'existing')">Existing<i>{{ $stats['existing'] }}</i></button>
        </div>
    </div>

    <div class="rgx-list">
        <div @class(['rgx-row-head', 'rgx-no-check' => ! $canApprove])>
            @if ($canApprove)
                <span class="rgx-cell-check"><input type="checkbox" wire:model.live="selectAll" class="rgx-check" aria-label="Select all"></span>
            @endif
            <span>Parent</span>
            <span>Students</span>
            <span>Received</span>
            <span class="text-end pe-3">Actions</span>
        </div>

        @forelse ($data as $guardian)
            <div @class(['rgx-row', 'rgx-no-check' => ! $canApprove, 'is-selected' => in_array((string) $guardian->id, $selected, true)]) wire:key="registration-{{ $guardian->id }}">
                @if ($canApprove)
                    <div class="rgx-cell-check"><input type="checkbox" value="{{ $guardian->id }}" wire:model.live="selected" class="rgx-check" aria-label="Select {{ $guardian->name }}"></div>
                @endif
                <div class="rgx-parent">
                    <span class="rgx-avatar">{{ $initialsOf($guardian->name) }}</span>
                    <div>
                        <b>{{ $guardian->name }}</b>
                        <small>{{ $guardian->mobile }}@if ($guardian->id_no) · QID {{ $guardian->id_no }}@endif</small>
                        @if ($guardian->email)
                            <small>{{ $guardian->email }}</small>
                        @endif
                        @if (! $guardian->isPendingApproval())
                            <em class="rgx-flag"><i class="fa fa-user"></i>Existing parent · check note</em>
                        @endif
                    </div>
                </div>
                <div class="rgx-kids">
                    @foreach ($guardian->pendingStudents as $student)
                        <div class="rgx-kid" wire:key="registration-{{ $guardian->id }}-student-{{ $student->id }}">
                            <b>{{ $student->name }}</b>
                            <span class="rgx-tag">{{ $student->studentDetail->school }}</span>
                            <span class="rgx-tag">{{ collect([$gradeLabel($student->studentDetail->grade), $student->studentDetail->section])->filter()->join(' · ') }}</span>
                            <span class="rgx-tag">{{ $student->studentDetail->admission_no }}</span>
                            <span class="rgx-tag">QID {{ $student->id_no }}</span>
                            @if ($student->description)
                                <span class="rgx-kid-note"><i class="fa fa-info-circle"></i> {{ $student->description }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="rgx-when">{{ $guardian->pendingStudents->max('created_at')?->diffForHumans() }}</div>
                <div class="rgx-actions" x-data>
                    <button type="button" class="rgx-btn rgx-btn-view" wire:click="viewFamily({{ $guardian->id }})" wire:loading.attr="disabled" wire:target="viewFamily({{ $guardian->id }})"><i class="fa fa-eye"></i>View</button>
                    @if ($canApprove)
                        <button type="button" class="rgx-btn rgx-btn-go" wire:loading.attr="disabled" wire:target="approve({{ $guardian->id }})"
                            x-on:click="Swal.fire({ title: 'Approve this registration?', text: @js($approveTextFor($guardian)), icon: 'question', showCancelButton: true, confirmButtonText: 'Approve' }).then((result) => { if (result.isConfirmed) { $wire.approve({{ $guardian->id }}) } })">
                            <i class="fa fa-check"></i>Approve
                        </button>
                        <button type="button" class="rgx-btn rgx-btn-bad"
                            x-on:click="Swal.fire({ title: 'Reject this registration?', text: @js($rejectTextFor($guardian)), icon: 'warning', showCancelButton: true, confirmButtonText: 'Reject' }).then((result) => { if (result.isConfirmed) { $wire.reject({{ $guardian->id }}) } })">
                            Reject
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="rgx-empty"><i class="fa fa-inbox"></i>No registrations waiting. New ones from the parent portal appear here.</div>
        @endforelse
    </div>

    <div @class(['rgx-foot', 'rgx-foot-clear' => $selection['families'] > 0])>
        <div class="rgx-pages">{{ $data->links() }}</div>
        <select wire:model.live="limit" class="rgx-limit" aria-label="Rows per page">
            <option value="25">25</option>
            <option value="100">100</option>
            <option value="500">500</option>
        </select>
    </div>

    @if ($canApprove && $selection['families'] > 0)
        @php
            $bulkConfirmText = ($selection['students'] === 1 ? 'This adds 1 student' : "This adds {$selection['students']} students")
                .($selection['families'] === 1 ? ' from 1 family.' : " from {$selection['families']} families.")
                .' New parents are emailed a portal link.';
            if ($selection['existing'] > 0) {
                $bulkConfirmText .= ' '.($selection['existing'] === 1 ? '1 is an existing parent' : "{$selection['existing']} are existing parents").'. Please check the note under each of their students first.';
            }
        @endphp
        <div class="rgx-float" role="region" aria-label="Bulk approval" wire:key="registration-bulk">
            <span class="rgx-float-count"><b>{{ $selection['families'] === 1 ? '1 family' : $selection['families'].' families' }} selected</b> <em>· {{ $selection['students'] === 1 ? '1 student' : $selection['students'].' students' }}</em></span>
            <div class="rgx-float-acts">
                <button type="button" class="rgx-float-clear" wire:click="clearSelection">Clear</button>
                <button type="button" class="rgx-float-do" x-data data-confirm="{{ $bulkConfirmText }}" wire:loading.attr="disabled" wire:target="approveSelected"
                    x-on:click="Swal.fire({ title: 'Approve the selected registrations?', text: $el.dataset.confirm, icon: 'question', showCancelButton: true, confirmButtonText: 'Approve selected' }).then((result) => { if (result.isConfirmed) { $wire.approveSelected() } })">
                    <i class="fa fa-check"></i> Approve selected
                </button>
            </div>
        </div>
    @endif

    <div class="modal fade" id="registrationDetails" tabindex="-1" aria-labelledby="registrationDetailsTitle" aria-hidden="true" wire:ignore.self
        x-data
        x-on:show-registration-modal.window="bootstrap.Modal.getOrCreateInstance($el).show()"
        x-on:hide-registration-modal.window="bootstrap.Modal.getOrCreateInstance($el).hide()">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable rgx-dialog">
            <div class="modal-content rgx-sheet">
                <h5 class="visually-hidden" id="registrationDetailsTitle">Registration details</h5>
                @if ($viewing && $viewing->pendingStudents->isNotEmpty())
                    <div class="rgx-hero">
                        <span class="rgx-avatar">{{ $initialsOf($viewing->name) }}</span>
                        <div>
                            <h2>{{ $viewing->name }}</h2>
                            <p>{{ $viewing->isPendingApproval() ? 'New parent' : 'Existing parent. Check the note under each student.' }} · Received {{ $viewing->pendingStudents->max('created_at')?->format('d M Y, h:i A') }}</p>
                        </div>
                        <button type="button" class="rgx-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
                    </div>
                    <div class="modal-body rgx-sheet-body">
                        <section>
                            <p class="rgx-section">Parent</p>
                            <div class="rgx-grid">
                                <div><small>Mobile</small><span>{{ $viewing->mobile ?: '—' }}</span></div>
                                <div><small>QID</small><span>{{ $viewing->id_no ?: '—' }}</span></div>
                                <div class="rgx-span"><small>Email</small><span>{{ $viewing->email ?: '—' }}</span></div>
                            </div>
                        </section>
                        <section>
                            <p class="rgx-section">{{ $viewing->pendingStudents->count() === 1 ? '1 student' : $viewing->pendingStudents->count().' students' }}</p>
                            @foreach ($viewing->pendingStudents as $student)
                                <div class="rgx-detail" wire:key="registration-detail-{{ $student->id }}">
                                    <div class="rgx-detail-name"><span class="rgx-avatar">{{ $initialsOf($student->name) }}</span>{{ $student->name }}</div>
                                    <div class="rgx-grid rgx-grid-3">
                                        @foreach ([
                                            'School' => $student->studentDetail->school,
                                            'Class' => $gradeLabel($student->studentDetail->grade),
                                            'Division' => $student->studentDetail->section,
                                            'Registration no.' => $student->studentDetail->admission_no,
                                            'QID' => $student->id_no,
                                        ] as $label => $value)
                                            <div><small>{{ $label }}</small><span>{{ $value ?: '—' }}</span></div>
                                        @endforeach
                                    </div>
                                    @if ($student->description)
                                        <div class="rgx-note"><i class="fa fa-info-circle"></i><span>{{ $student->description }}</span></div>
                                    @endif
                                </div>
                            @endforeach
                        </section>
                    </div>
                    <div class="rgx-sheet-foot" x-data>
                        <button type="button" class="rgx-btn" data-bs-dismiss="modal">Close</button>
                        @if ($canApprove)
                            <button type="button" class="rgx-btn rgx-btn-bad" data-reject-text="{{ $rejectTextFor($viewing) }}"
                                x-on:click="Swal.fire({ title: 'Reject this registration?', text: $el.dataset.rejectText, icon: 'warning', showCancelButton: true, confirmButtonText: 'Reject' }).then((result) => { if (result.isConfirmed) { $wire.reject({{ $viewing->id }}) } })">
                                Reject
                            </button>
                            <button type="button" class="rgx-btn rgx-btn-go" data-approve-text="{{ $approveTextFor($viewing) }}"
                                x-on:click="Swal.fire({ title: 'Approve this registration?', text: $el.dataset.approveText, icon: 'question', showCancelButton: true, confirmButtonText: 'Approve' }).then((result) => { if (result.isConfirmed) { $wire.approve({{ $viewing->id }}) } })">
                                <i class="fa fa-check"></i>Approve
                            </button>
                        @endif
                    </div>
                @else
                    <div class="rgx-empty">
                        <i class="fa fa-inbox"></i>This registration has already been handled.
                        <div class="mt-3"><button type="button" class="rgx-btn" data-bs-dismiss="modal">Close</button></div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
