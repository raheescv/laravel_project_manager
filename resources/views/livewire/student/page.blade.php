@php
    use App\Models\Guardian;
    use App\Models\StudentDetail;

    $editing = (bool) $account_id;
    $saveLabel = $editing ? 'Save changes' : 'Save student';
    $cancelUrl = $editing ? route('student::view', $account_id) : route('student::index');
    $nationalities = $countries;
    if (filled($student['nationality']) && !array_key_exists($student['nationality'], $nationalities)) {
        $nationalities = [$student['nationality'] => $student['nationality']] + $nationalities;
    }
    $statusHelp = [
        'active' => ['fa-check-circle', 'Studying here. The card works at the canteen.', ''],
        'inactive' => ['fa-pause', 'Away for now. The till refuses the card.', 'bad'],
        'graduated' => ['fa-graduation-cap', 'Left the school. The till refuses the card.', 'bad'],
    ];
    $relationIcons = ['father' => 'fa-male', 'mother' => 'fa-female', 'guardian' => 'fa-user'];
@endphp

{{-- Add / Edit student. Design system: <x-student.form-premium /> (scope .sfx). The live preview,
     steps and checklist read the form straight from $wire, so they update without a request. --}}
<div class="sfx" x-data="{
    filled(value) { return String(value ?? '').trim() !== '' },
    initials(name) {
        const parts = String(name ?? '').trim().split(/\s+/).filter(Boolean);
        return parts.length ? (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase() : '';
    },
    age(dob) {
        if (!dob) return '';
        const born = new Date(dob + 'T00:00:00'), today = new Date();
        let years = today.getFullYear() - born.getFullYear();
        if (today < new Date(today.getFullYear(), born.getMonth(), born.getDate())) years--;
        return years >= 0 && years < 60 ? years + (years === 1 ? ' year old' : ' years old') : '';
    },
    parentReady(parent) { return this.filled(parent.name) && this.filled(parent.mobile) },
    get s() { return this.$wire.student },
    get parents() { return this.$wire.guardians || [] },
    get klass() { return [this.s.grade, this.s.section].filter((value) => this.filled(value)).join(' - ') },
    get halfParents() { return this.parents.map((p, i) => (this.filled(p.name) || this.filled(p.mobile)) && !this.parentReady(p) ? i + 1 : 0).filter(Boolean) },
    get readyParents() { return this.parents.filter((p) => this.parentReady(p)).length },
    get requiredLeft() { return (this.filled(this.s.name) ? 0 : 1) + (this.filled(this.s.admission_no) ? 0 : 1) },
    get hasPhoto() { return !!(this.$wire.photo || this.$wire.image) },
    pick(field, value) { this.$wire.student[field] = this.$wire.student[field] === value ? '' : value },
    go(id) { document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' }) },
}">
    <x-student.form-premium />

    <form wire:submit="save">
        {{-- Title + steps --}}
        <div class="sheet top mb-3 rise" style="--i:0">
            <div>
                <a href="{{ route('student::index') }}" class="eyebrow" title="Back to students"><i class="fa fa-graduation-cap"></i>Students</a>
                <h4>{{ $editing ? 'Edit ' . $student['name'] : 'Add a new student' }}</h4>
                <p>{{ $editing ? 'Change what you need, then press Save changes.' : 'Three short steps. Only the name and admission number are required.' }}</p>
            </div>
            <div class="steps">
                <button type="button" x-on:click="go('s-student')" :class="{ done: requiredLeft === 0 }"><span class="n"><i class="fa fa-check" x-show="requiredLeft === 0"></i><span x-show="requiredLeft !== 0">1</span></span>Student</button>
                <button type="button" x-on:click="go('s-class')" :class="{ done: filled(s.grade) }"><span class="n"><i class="fa fa-check" x-show="filled(s.grade)"></i><span x-show="!filled(s.grade)">2</span></span>Class</button>
                <button type="button" x-on:click="go('s-parents')" :class="{ done: readyParents > 0 && !halfParents.length }"><span class="n"><i class="fa fa-check" x-show="readyParents > 0 && !halfParents.length"></i><span x-show="!(readyParents > 0 && !halfParents.length)">3</span></span>Parents</button>
                <button type="button" x-on:click="go('s-extras')" :class="{ done: {{ $editing ? 'hasPhoto' : 'filled(s.card_uid) || hasPhoto' }} }"><span class="n"><i class="fa fa-{{ $editing ? 'camera' : 'credit-card' }}"></i></span>{{ $editing ? 'Photo' : 'Card & photo' }} <span class="o">optional</span></button>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                {{-- 1 · Student --}}
                <section class="sheet fsec rise" id="s-student" style="--i:1">
                    <div class="sec-h">
                        <span class="num">1</span>
                        <div>
                            <h5>Who is the student?</h5>
                            <p>Name, admission number and personal details</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="sp_name">Full name <span class="req">Required</span></label>
                            <input type="text" id="sp_name" class="form-control" wire:model="student.name" maxlength="100" required placeholder="e.g. Aisha Rahman" autocomplete="off">
                            <div class="hint"><i class="fa fa-info-circle"></i><span>As on the school register. The cashier sees this name when the card is tapped.</span></div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="sp_admission_no">Admission number <span class="req">Required</span></label>
                            <input type="text" id="sp_admission_no" class="form-control" wire:model="student.admission_no" maxlength="30" required placeholder="e.g. ADM-2024-0187" autocomplete="off">
                            <div class="hint"><i class="fa fa-info-circle"></i><span>From the school register. Two students cannot share one.</span></div>
                        </div>

                        <div class="col-12">
                            <div class="form-label" id="sp_gender_label">Gender <span class="opt">Optional</span></div>
                            <div class="row g-2" role="radiogroup" aria-labelledby="sp_gender_label">
                                @foreach (StudentDetail::GENDERS as $value => $label)
                                    <div class="col-6 col-md-4">
                                        <input type="radio" class="btn-check" name="sp_gender" id="sp_gender_{{ $value }}" value="{{ $value }}" wire:model="student.gender">
                                        <label class="tapc" for="sp_gender_{{ $value }}">
                                            <span class="ti"><i class="fa {{ $value === 'male' ? 'fa-mars' : 'fa-venus' }}"></i></span>
                                            <span class="tt"><b>{{ $label }}</b></span>
                                            <span class="tk"><i class="fa fa-check"></i></span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="sp_dob">Date of birth <span class="opt">Optional</span></label>
                            <div class="input-group">
                                <input type="date" id="sp_dob" class="form-control" wire:model="student.dob" max="{{ now()->toDateString() }}">
                                <span class="input-group-text" x-text="age(s.dob) || 'Age'">Age</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sp_nationality">Nationality <span class="opt">Optional</span></label>
                            <div wire:ignore>
                                <select id="sp_nationality" data-student-nationality>
                                    <option value="">Search a country…</option>
                                    @foreach ($nationalities as $value => $label)
                                        <option value="{{ $value }}" @selected($student['nationality'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="hint"><i class="fa fa-info-circle"></i><span>Start typing to search, e.g. "Qat".</span></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sp_id_no">QID number <span class="opt">Optional</span></label>
                            <input type="text" id="sp_id_no" class="form-control" wire:model="student.id_no" maxlength="30" inputmode="numeric" placeholder="e.g. 28463401234" autocomplete="off">
                            <div class="hint"><i class="fa fa-info-circle"></i><span>The number on the student's Qatar ID card.</span></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sp_description">Notes for staff <span class="opt">Optional</span></label>
                            <textarea id="sp_description" class="form-control" rows="2" wire:model="student.description" maxlength="255" placeholder="e.g. Allergic to peanuts"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="subbox">
                                <div class="form-label mb-0">Student's own phone and email <span class="opt">Optional</span></div>
                                <div class="hint mt-1 mb-2"><i class="fa fa-info-circle"></i><span>Only if the student has their own. Leave these empty for most students; parents are added in step 3.</span></div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" wire:model="student.mobile" maxlength="15" inputmode="tel" placeholder="Mobile" aria-label="Student mobile">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="email" class="form-control" wire:model="student.email" maxlength="50" placeholder="Email" aria-label="Student email">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 2 · Class --}}
                <section class="sheet fsec rise" id="s-class" style="--i:2">
                    <div class="sec-h">
                        <span class="num">2</span>
                        <div>
                            <h5>Which class are they in?</h5>
                            <p>Grade, section and whether they are at school now</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="sp_grade">Grade <span class="opt">Optional</span></label>
                            <input type="text" id="sp_grade" class="form-control" wire:model="student.grade" list="sp_grades" maxlength="30" placeholder="e.g. Grade 5" autocomplete="off">
                            <datalist id="sp_grades">
                                @foreach ($grades as $value)
                                    <option value="{{ $value }}"></option>
                                @endforeach
                            </datalist>
                            @if ($grades->isNotEmpty())
                                <div class="qch">
                                    <span>Tap to pick:</span>
                                    @foreach ($grades->take(16) as $value)
                                        <button type="button" x-on:click="pick('grade', @js($value))" :class="{ on: s.grade === @js($value) }">{{ $value }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sp_section">Section <span class="opt">Optional</span></label>
                            <input type="text" id="sp_section" class="form-control" wire:model="student.section" list="sp_sections" maxlength="30" placeholder="e.g. B" autocomplete="off">
                            <datalist id="sp_sections">
                                @foreach ($sections as $value)
                                    <option value="{{ $value }}"></option>
                                @endforeach
                            </datalist>
                            @if ($sections->isNotEmpty())
                                <div class="qch">
                                    <span>Tap to pick:</span>
                                    @foreach ($sections->take(16) as $value)
                                        <button type="button" x-on:click="pick('section', @js($value))" :class="{ on: s.section === @js($value) }">{{ $value }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="col-12">
                            <div class="form-label" id="sp_status_label">Is the student at school now?</div>
                            <div class="row g-2" role="radiogroup" aria-labelledby="sp_status_label">
                                @foreach (StudentDetail::STATUSES as $value => $label)
                                    @php
                                        [$icon, $help, $tone] = $statusHelp[$value] ?? ['fa-circle-o', '', ''];
                                    @endphp
                                    <div class="col-md-4">
                                        <input type="radio" class="btn-check" name="sp_status" id="sp_status_{{ $value }}" value="{{ $value }}" wire:model="student.status">
                                        <label class="tapc {{ $tone }}" for="sp_status_{{ $value }}">
                                            <span class="ti"><i class="fa {{ $icon }}"></i></span>
                                            <span class="tt"><b>{{ $label }}</b>@if ($help)<small>{{ $help }}</small>@endif</span>
                                            <span class="tk"><i class="fa fa-check"></i></span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 3 · Parents --}}
                <section class="sheet fsec rise" id="s-parents" style="--i:3">
                    <div class="sec-h">
                        <span class="num">3</span>
                        <div>
                            <h5>Parents</h5>
                            <p>Who can see the card and top it up</p>
                        </div>
                    </div>
                    <div class="explain">
                        <i class="fa fa-lightbulb-o"></i>
                        <div>
                            Each parent logs in to the <b>Parent Portal</b> with their mobile number to see the card, bills and statement, and to top up.
                            A parent who already has a child at the school is recognised by the same number and sees all their children in one login.
                        </div>
                    </div>

                    @forelse ($guardians as $index => $guardian)
                        <div @class(['pcard', 'main' => $guardian['is_primary']]) wire:key="guardian-{{ $index }}">
                            <div class="pc-h">
                                <span class="pn">Parent {{ $index + 1 }}</span>
                                @if ($guardian['is_primary'])
                                    <span class="mainb"><i class="fa fa-star"></i>Main contact</span>
                                @else
                                    <button type="button" class="linkb" wire:click="makePrimary({{ $index }})"><i class="fa fa-star-o"></i>Make main contact</button>
                                @endif
                                <button type="button" class="linkb danger ms-auto" wire:click="removeGuardian({{ $index }})"><i class="fa fa-trash-o"></i>Remove</button>
                            </div>
                            <div class="pc-b">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="seg" role="radiogroup" aria-label="Relation to the student">
                                            @foreach (Guardian::RELATIONS as $value => $label)
                                                <input type="radio" class="btn-check" name="g_relation_{{ $index }}" id="g_relation_{{ $index }}_{{ $value }}" value="{{ $value }}" wire:model="guardians.{{ $index }}.relation">
                                                <label for="g_relation_{{ $index }}_{{ $value }}"><i class="fa {{ $relationIcons[$value] ?? 'fa-user' }}"></i>{{ $label }}</label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="g_name_{{ $index }}">Name <span class="req">Required</span></label>
                                        <input type="text" id="g_name_{{ $index }}" class="form-control" wire:model="guardians.{{ $index }}.name" maxlength="100" placeholder="e.g. Mohammed Rahman" autocomplete="off">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="g_mobile_{{ $index }}">Mobile number <span class="req">Required</span></label>
                                        <input type="text" id="g_mobile_{{ $index }}" class="form-control" wire:model="guardians.{{ $index }}.mobile" maxlength="20" inputmode="tel" placeholder="e.g. 55123344" autocomplete="off">
                                        <div class="hint"><i class="fa fa-info-circle"></i><span>They log in to the Parent Portal with this number.</span></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="g_email_{{ $index }}">Email <span class="opt">Optional</span></label>
                                        <input type="email" id="g_email_{{ $index }}" class="form-control" wire:model="guardians.{{ $index }}.email" maxlength="150" placeholder="e.g. parent@example.com">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty"><i class="fa fa-exclamation-triangle me-1"></i>No parents added. The card still works at the canteen, but nobody can top it up online.</div>
                    @endforelse

                    <button type="button" class="addp" wire:click="addGuardian"><i class="fa fa-plus-circle me-2"></i>{{ count($guardians) ? 'Add another parent' : 'Add a parent' }}</button>
                </section>
            </div>

            <div class="col-lg-4">
                <div class="side" id="s-extras">
                    {{-- Live preview + photo --}}
                    <div class="sheet live rise" style="--i:1">
                        <div class="lbl"><i class="fa fa-eye"></i>What staff will see</div>
                        <div class="lv">
                            <div class="lv-av">
                                @if ($photo && !$errors->has('photo'))
                                    <img src="{{ $photo->temporaryUrl() }}" alt="">
                                @elseif ($image)
                                    <img src="{{ asset('storage/' . $image) }}" alt="">
                                @else
                                    <span x-text="initials(s.name)"></span>
                                    <i class="fa fa-user opacity-50" x-show="!initials(s.name)"></i>
                                @endif
                                <label for="sp_photo" class="cam" title="Add a photo"><i class="fa fa-camera"></i></label>
                            </div>
                            <div class="min-w-0">
                                <div class="nm" :class="{ ph: !filled(s.name) }" x-text="filled(s.name) ? s.name : 'Student name'">{{ $student['name'] ?: 'Student name' }}</div>
                                <div class="sm" :class="{ ph: !klass }" x-text="klass || 'Class not set'"></div>
                                <div class="sm" x-text="s.admission_no"></div>
                                <div class="mt-1">
                                    @if ($editing)
                                        @if (!$detail?->card_uid)
                                            <span class="badge bg-body-tertiary text-body-secondary border">No card</span>
                                        @elseif ($detail->isCardBlocked())
                                            <span class="badge text-bg-danger"><i class="fa fa-lock me-1"></i>Card blocked</span>
                                        @else
                                            <span class="badge text-bg-success"><i class="fa fa-credit-card me-1"></i>Card active</span>
                                        @endif
                                    @else
                                        <span class="badge text-bg-success" x-show="filled(s.card_uid)"><i class="fa fa-credit-card me-1"></i>Card linked</span>
                                        <span class="badge bg-body-tertiary text-body-secondary border" x-show="!filled(s.card_uid)">No card yet</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <input type="file" id="sp_photo" class="d-none" wire:model="photo" accept="image/*">
                        <div class="photo-act">
                            <label for="sp_photo" class="btn btn-sm btn-outline-primary mb-0" wire:loading.class="disabled" wire:target="photo">
                                <span wire:loading.remove wire:target="photo"><i class="fa fa-camera me-1"></i>{{ $photo || $image ? 'Change photo' : 'Add photo' }}</span>
                                <span wire:loading wire:target="photo"><i class="fa fa-refresh fa-spin me-1"></i>Uploading…</span>
                            </label>
                            @if ($photo || $image)
                                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removePhoto">Remove</button>
                            @endif
                        </div>
                        @error('photo')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                        <div class="hint"><i class="fa fa-info-circle"></i><span>The photo shows on the till when the card is tapped, so the cashier can check it is the right child.</span></div>
                    </div>

                    {{-- Card --}}
                    @if ($editing)
                        <div class="sheet tapzone">
                            <div class="lbl"><i class="fa fa-credit-card"></i>Canteen card</div>
                            @if ($detail?->card_uid)
                                <div class="tz solid">
                                    <div class="ic"><i class="fa fa-wifi"></i></div>
                                    <div class="min-w-0">
                                        <b class="uidf">{{ trim(chunk_split($detail->card_uid, 4, ' ')) }}</b>
                                        <small>{{ $detail->isCardBlocked() ? 'Blocked.' : 'Active.' }} To replace or block it, open the student's Card tab.</small>
                                    </div>
                                </div>
                            @else
                                <div class="tz">
                                    <div class="ic"><i class="fa fa-wifi"></i></div>
                                    <div><b>No card linked</b><small>Link one from the student's Card tab.</small></div>
                                </div>
                            @endif
                            <a href="{{ route('student::view', $account_id) }}" class="btn btn-sm btn-outline-secondary w-100"><i class="fa fa-credit-card me-1"></i>Open the student's page</a>
                        </div>
                    @else
                        <div class="sheet tapzone">
                            <div class="lbl"><i class="fa fa-credit-card"></i>Canteen card <span class="opt">Optional</span></div>
                            <div class="tz">
                                <div class="ic"><i class="fa fa-wifi"></i></div>
                                <div><b>Tap the card on the USB reader</b><small>Click the box below first. The number fills in by itself.</small></div>
                            </div>
                            <input type="text" id="sp_card_uid" class="form-control uidf" wire:model="student.card_uid" maxlength="40" autocomplete="off" placeholder="Card number" aria-label="Card number">
                            <div class="hint"><i class="fa fa-info-circle"></i><span>No card yet? Leave it empty. You can link it later from the student's page or QLOUD POS.</span></div>
                        </div>
                    @endif

                    {{-- Checklist + save --}}
                    <div class="sheet chkbox d-none d-lg-block">
                        <div class="lbl"><i class="fa fa-check-square-o"></i>Before you save</div>
                        <ul class="chk">
                            <li :class="{ ok: filled(s.name) }">
                                <span class="ck"><i class="fa fa-check"></i></span>
                                <div>Full name<small x-show="!filled(s.name)">Required</small></div>
                            </li>
                            <li :class="{ ok: filled(s.admission_no) }">
                                <span class="ck"><i class="fa fa-check"></i></span>
                                <div>Admission number<small x-show="!filled(s.admission_no)">Required</small></div>
                            </li>
                            <li :class="halfParents.length ? 'bad' : (readyParents ? 'ok' : 'warn')">
                                <span class="ck"><i class="fa" :class="halfParents.length ? 'fa-times' : (readyParents ? 'fa-check' : 'fa-exclamation')"></i></span>
                                <div>
                                    <span x-show="halfParents.length" x-text="'Parent ' + halfParents.join(', ') + ' needs a name and a mobile number'"></span>
                                    <span x-show="!halfParents.length && readyParents" x-text="readyParents + (readyParents === 1 ? ' parent can' : ' parents can') + ' top up online'"></span>
                                    <span x-show="!halfParents.length && !readyParents">No parent yet</span>
                                    <small x-show="halfParents.length">Fill in both, or remove the parent</small>
                                    <small x-show="!halfParents.length && !readyParents">Recommended, so the card can be topped up online</small>
                                </div>
                            </li>
                            @unless ($editing)
                                <li :class="filled(s.card_uid) ? 'ok' : 'warn'">
                                    <span class="ck"><i class="fa" :class="filled(s.card_uid) ? 'fa-check' : 'fa-exclamation'"></i></span>
                                    <div>
                                        <span x-text="filled(s.card_uid) ? 'Card linked' : 'No card yet'"></span>
                                        <small x-show="!filled(s.card_uid)">That's fine, you can link it later</small>
                                    </div>
                                </li>
                            @endunless
                            <li :class="hasPhoto ? 'ok' : 'warn'">
                                <span class="ck"><i class="fa" :class="hasPhoto ? 'fa-check' : 'fa-exclamation'"></i></span>
                                <div>
                                    <span x-text="hasPhoto ? 'Photo added' : 'No photo'"></span>
                                    <small x-show="!hasPhoto">Helps the cashier check who is using the card</small>
                                </div>
                            </li>
                        </ul>
                        <button type="submit" class="btn btn-primary w-100 save" wire:loading.attr="disabled" wire:target="save,photo">
                            <span wire:loading.remove wire:target="save"><i class="fa fa-check me-2"></i>{{ $saveLabel }}</span>
                            <span wire:loading wire:target="save"><i class="fa fa-refresh fa-spin me-2"></i>Saving…</span>
                        </button>
                        <a href="{{ $cancelUrl }}" class="btn btn-link w-100 text-body-secondary text-decoration-none mt-1">Cancel</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Phone and tablet: save bar pinned to the bottom --}}
        <div class="savebar d-lg-none">
            <div class="st">
                <span x-show="requiredLeft > 0"><b x-text="requiredLeft + ' required'"></b> <span x-text="requiredLeft === 1 ? 'field left' : 'fields left'"></span></span>
                <span x-show="requiredLeft === 0"><b><i class="fa fa-check text-success me-1"></i>Ready to save</b></span>
            </div>
            <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-3" wire:loading.attr="disabled" wire:target="save,photo">
                <i class="fa fa-check me-1"></i>{{ $saveLabel }}
            </button>
        </div>
    </form>

    @script
        <script>
            (() => {
                const el = $wire.$el.querySelector('[data-student-nationality]');
                if (!el || el.tomselect || typeof TomSelect === 'undefined') return;
                new TomSelect(el, {
                    allowEmptyOption: false,
                    placeholder: 'Search a country…',
                    maxOptions: null,
                    plugins: ['clear_button'],
                    onChange(value) {
                        $wire.$set('student.nationality', value || '', false);
                    },
                });
            })()
        </script>
    @endscript
</div>
