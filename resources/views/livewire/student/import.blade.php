@use('App\Support\Student\StudentImportSheet')
@php
    $fieldCount = count(StudentImportSheet::fields());
    $ready = ($summary['new'] ?? 0) + ($summary['update'] ?? 0);
    $progress = (int) ($status['progress'] ?? 0);
    $finished = $step === 4 && $progress >= 100;
    $failed = $step === 4 && $progress < 0;

    $steps = [
        1 => ['Upload', $fileName !== '' ? $fileName.' · '.number_format($rowCount).' rows' : 'Excel or CSV file', $step !== 4],
        2 => ['Match columns', $fileName !== '' ? $mappedCount.' of '.$fieldCount.' fields' : 'After the upload', $step !== 4 && $filePath],
        3 => ['Check rows', $summary ? number_format($ready).' ready · '.number_format($summary['error']).' '.($summary['error'] === 1 ? 'issue' : 'issues') : 'Nothing is saved yet', $step !== 4 && $summary],
        4 => ['Import', $finished ? 'Finished' : ($failed ? 'Stopped' : ($step === 4 ? 'Running · '.max(0, $progress).'%' : 'In the background')), false],
    ];

    $titles = [
        1 => ['Upload the student sheet', 'One row per student, with the card and up to two parents'],
        2 => ['Match your columns', count($guessed) ? count($guessed).' matched from your headers — check the rest' : 'Pick the column that holds each field'],
        3 => ['Check the rows', 'Nothing has been saved yet'],
        4 => $finished ? ['Import finished', 'The students are in the list now'] : ($failed ? ['The import stopped', 'Nothing after the failure was imported'] : ['Importing students', 'Runs in the background — you can leave this page']),
    ];

    $groupIcons = ['student' => 'fa-child', 'parent' => 'fa-male', 'second_parent' => 'fa-female'];
    $relationLabels = \App\Models\Guardian::RELATIONS;
    $statusLabels = ['new' => 'New', 'update' => 'Update', 'skip' => 'Skip', 'error' => 'Issue'];
    $filterLabels = ['all' => 'All', 'new' => 'New', 'update' => 'Update', 'skip' => 'Skip', 'error' => 'Issues'];
@endphp

<div>
    <x-student.import-premium />

    <div class="stx">
        <div class="stx-frame">

            {{-- ============================================================ rail --}}
            <ol class="stx-rail">
                <li class="stx-rail__label">Import students</li>
                @foreach ($steps as $n => [$label, $meta, $reachable])
                    <li class="{{ $step > $n ? 'is-done' : '' }}">
                        <button type="button" class="stx-step {{ $step === $n ? 'is-active' : '' }} {{ $step > $n ? 'is-done' : '' }}"
                            wire:click="goToStep({{ $n }})" @disabled(! $reachable || $step === $n) aria-current="{{ $step === $n ? 'step' : 'false' }}">
                            <span class="stx-step__n">
                                @if ($step > $n || ($n === 4 && $finished))
                                    <i class="fa fa-check"></i>
                                @else
                                    {{ $n }}
                                @endif
                            </span>
                            <span class="stx-step__t"><b>{{ $label }}</b><small>{{ $meta }}</small></span>
                        </button>
                    </li>
                @endforeach
            </ol>

            {{-- ============================================================ head --}}
            <header class="stx-head">
                <div class="stx-title">
                    <span class="stx-title__ico"><i class="fa fa-graduation-cap"></i></span>
                    <div>
                        <h2>{{ $titles[$step][0] }}</h2>
                        <p>{{ $titles[$step][1] }}</p>
                    </div>
                </div>
                <div class="stx-actions">
                    <a href="{{ route('student::index') }}" class="stx-btn stx-btn--ghost"><i class="fa fa-arrow-left"></i> Students</a>
                    <button type="button" class="stx-btn stx-btn--soft" wire:click="downloadTemplate"><i class="fa fa-download"></i> Template</button>
                </div>
            </header>

            <div class="stx-stage">

                {{-- ======================================================= step 1 --}}
                @if ($step === 1)
                    <div class="stx-split">
                        <div class="stx-col">
                            <div class="stx-card">
                                <div class="stx-card__head">
                                    <h3><i class="fa fa-refresh"></i> When the admission number already exists</h3>
                                </div>
                                <div class="stx-card__body">
                                    <div class="stx-choice">
                                        <label class="stx-opt {{ $duplicateStrategy === 'skip' ? 'is-on' : '' }}">
                                            <input type="radio" wire:model.live="duplicateStrategy" value="skip" @checked($duplicateStrategy === 'skip')>
                                            <span class="stx-opt__ico"><i class="fa fa-forward"></i></span>
                                            <span><b>Skip the row</b><small>Leave the student as it is</small></span>
                                        </label>
                                        <label class="stx-opt {{ $duplicateStrategy === 'update' ? 'is-on' : '' }}">
                                            <input type="radio" wire:model.live="duplicateStrategy" value="update" @checked($duplicateStrategy === 'update')>
                                            <span class="stx-opt__ico"><i class="fa fa-refresh"></i></span>
                                            <span><b>Update the student</b><small>Refresh details and parents — the linked card is kept</small></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            @if ($filePath)
                                <div class="stx-card stx-file">
                                    <span class="stx-file__ico"><i class="fa fa-file-excel-o"></i></span>
                                    <div class="stx-file__name">
                                        <b>{{ $fileName }}</b>
                                        <small>{{ number_format($rowCount) }} {{ $rowCount === 1 ? 'row' : 'rows' }} · {{ count($headers) }} columns</small>
                                    </div>
                                    <div class="stx-file__acts">
                                        <label class="stx-btn stx-btn--ghost">
                                            <input type="file" class="stx-drop__input" wire:model="file" accept=".xlsx,.xls,.csv">
                                            <span wire:loading.remove wire:target="file"><i class="fa fa-cloud-upload"></i> Replace</span>
                                            <span wire:loading wire:target="file"><i class="fa fa-spinner fa-spin"></i> Reading…</span>
                                        </label>
                                        <button type="button" class="stx-btn stx-btn--primary" wire:click="goToStep(2)">Continue <i class="fa fa-arrow-right"></i></button>
                                    </div>
                                </div>
                            @else
                                <label class="stx-drop">
                                    <input type="file" class="stx-drop__input" wire:model="file" accept=".xlsx,.xls,.csv">
                                    <span class="stx-drop__ico">
                                        <i class="fa fa-cloud-upload" wire:loading.remove wire:target="file"></i>
                                        <i class="fa fa-spinner fa-spin" wire:loading wire:target="file"></i>
                                    </span>
                                    <b wire:loading.remove wire:target="file">Drop the student sheet here, or <u>browse</u></b>
                                    <b wire:loading wire:target="file">Reading the sheet…</b>
                                    <small>XLSX, XLS or CSV · up to 10 MB · {{ number_format(StudentImportSheet::MAX_ROWS) }} rows</small>
                                </label>
                            @endif

                            @error('file')
                                <div class="stx-error"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                            @enderror

                            <div class="stx-card stx-tpl">
                                <i class="fa fa-file-excel-o"></i>
                                <div>
                                    <b>Start from the template</b>
                                    <small>{{ $fieldCount }} columns — student, card and two parents, with a sample row</small>
                                </div>
                                <button type="button" class="stx-btn stx-btn--ghost" wire:click="downloadTemplate"><i class="fa fa-download"></i> Download</button>
                            </div>
                        </div>

                        <aside class="stx-col">
                            <div class="stx-card">
                                <div class="stx-card__head">
                                    <h3><i class="fa fa-info-circle"></i> How it works</h3>
                                </div>
                                <ul class="stx-how">
                                    <li>
                                        <span class="stx-how__ico"><i class="fa fa-magic"></i></span>
                                        <div><b>Your headers, understood</b><small>Columns like <em>Adm No</em>, <em>Class</em> and <em>Father Mobile</em> are matched for you.</small></div>
                                    </li>
                                    <li>
                                        <span class="stx-how__ico"><i class="fa fa-shield"></i></span>
                                        <div><b>Every row checked first</b><small>Missing names, repeated admission numbers and cards already in use are shown before anything is saved.</small></div>
                                    </li>
                                    <li>
                                        <span class="stx-how__ico"><i class="fa fa-users"></i></span>
                                        <div><b>One login per family</b><small>Parents are matched by mobile, so brothers and sisters share one parent account.</small></div>
                                    </li>
                                    <li>
                                        <span class="stx-how__ico"><i class="fa fa-tasks"></i></span>
                                        <div><b>Runs in the background</b><small>Watch the progress here or leave the page. Rows that fail are sent to your notifications.</small></div>
                                    </li>
                                </ul>
                            </div>
                        </aside>
                    </div>
                @endif

                {{-- ======================================================= step 2 --}}
                @if ($step === 2)
                    <div class="stx-split">
                        <div class="stx-col">
                            @foreach ($groups as $groupKey => $group)
                                @php
                                    $groupMapped = count(array_filter(array_keys($group['fields']), fn ($field) => isset($headers[$mappings[$field] ?? ''])));
                                    $relationDefault = $relationDefaults[$groupKey] ?? null;
                                @endphp
                                <div class="stx-card" wire:key="group-{{ $groupKey }}">
                                    <div class="stx-card__head">
                                        <h3><i class="fa {{ $groupIcons[$groupKey] }}"></i> {{ $group['label'] }}</h3>
                                        @if ($relationDefault && $relationDefault !== 'guardian')
                                            <span class="stx-chip">{{ $relationLabels[$relationDefault] }} · from the headers</span>
                                        @endif
                                        <span class="stx-chip">{{ $groupMapped }} of {{ count($group['fields']) }}</span>
                                    </div>
                                    <div>
                                        @foreach ($group['fields'] as $field => $label)
                                            @php
                                                $key = (string) ($mappings[$field] ?? '');
                                                $isMapped = $key !== '' && isset($headers[$key]);
                                            @endphp
                                            <div class="stx-map__row {{ $isMapped ? 'is-mapped' : '' }}" wire:key="map-{{ $field }}">
                                                <div class="stx-map__field">
                                                    <i class="fa {{ $isMapped ? 'fa-check-circle' : 'fa-circle-o' }}"></i>
                                                    <b>{{ $label }}</b>
                                                    @if (in_array($field, StudentImportSheet::REQUIRED, true))
                                                        <span class="stx-req">Required</span>
                                                    @endif
                                                </div>
                                                <span class="stx-map__arrow"><i class="fa fa-long-arrow-left"></i></span>
                                                <select class="form-select form-select-sm" wire:model.live="mappings.{{ $field }}" aria-label="Column for {{ $group['label'] }} {{ $label }}">
                                                    <option value="">— Do not import —</option>
                                                    @foreach ($headers as $headerKey => $headerLabel)
                                                        <option value="{{ $headerKey }}" @selected($key === (string) $headerKey)>{{ $headerLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="stx-map__sample">
                                                    @if ($isMapped)
                                                        @if (($samples[$key] ?? '') !== '')
                                                            <span class="v">{{ $samples[$key] }}</span>
                                                        @else
                                                            <span class="none">blank in the first rows</span>
                                                        @endif
                                                        @if (($guessed[$field] ?? null) === $key)
                                                            <span class="stx-auto">auto</span>
                                                        @endif
                                                    @elseif ($field === 'status')
                                                        <span class="none">everyone Active</span>
                                                    @elseif (str_ends_with($field, '_relation'))
                                                        <span class="none">{{ $relationLabels[$relationDefault] ?? 'Guardian' }} for everyone</span>
                                                    @else
                                                        <span class="none">not imported</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <aside class="stx-col">
                            <div class="stx-card">
                                <div class="stx-meter">
                                    <div class="stx-ring" style="--p: {{ (int) round($mappedCount / max(1, $fieldCount) * 100) }}">
                                        <span><b>{{ $mappedCount }}</b><small>of {{ $fieldCount }}</small></span>
                                    </div>
                                    <div>
                                        <b>Fields matched</b>
                                        <small>{{ number_format($rowCount) }} {{ $rowCount === 1 ? 'row' : 'rows' }} in {{ $fileName }}</small>
                                    </div>
                                </div>
                                <ul class="stx-checks">
                                    @foreach (StudentImportSheet::REQUIRED as $field)
                                        @php $ok = isset($headers[$mappings[$field] ?? '']); @endphp
                                        <li class="{{ $ok ? 'ok' : 'bad' }}">
                                            <i class="fa {{ $ok ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                            {{ StudentImportSheet::GROUPS['student']['fields'][$field] }} {{ $ok ? 'is matched' : 'needs a column' }}
                                        </li>
                                    @endforeach
                                    @if (array_diff(array_values($relationDefaults), ['guardian']))
                                        <li class="info">
                                            <i class="fa fa-info-circle"></i>
                                            Blank relations become {{ collect($relationDefaults)->map(fn ($r) => $relationLabels[$r])->unique()->implode(' and ') }}, from the column names
                                        </li>
                                    @endif
                                </ul>
                                @error('mappings')
                                    <div class="px-3 pb-3"><div class="stx-error"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div></div>
                                @enderror
                                @if ($unusedHeaders)
                                    <div class="stx-unused">
                                        <small>Columns left out</small>
                                        @foreach ($unusedHeaders as $headerLabel)
                                            <span class="stx-chip">{{ $headerLabel }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="stx-foot">
                                    <button type="button" class="stx-btn stx-btn--ghost" wire:click="goToStep(1)" title="Back"><i class="fa fa-arrow-left"></i></button>
                                    <button type="button" class="stx-btn stx-btn--primary" wire:click="checkRows" wire:loading.attr="disabled" wire:target="checkRows">
                                        <span wire:loading.remove wire:target="checkRows">Check {{ number_format($rowCount) }} {{ $rowCount === 1 ? 'row' : 'rows' }} <i class="fa fa-arrow-right"></i></span>
                                        <span wire:loading wire:target="checkRows"><i class="fa fa-spinner fa-spin"></i> Checking…</span>
                                    </button>
                                </div>
                            </div>
                        </aside>
                    </div>
                @endif

                {{-- ======================================================= step 3 --}}
                @if ($step === 3 && $summary)
                    <div class="stx-kpis">
                        <div class="stx-kpi"><small>Rows</small><b>{{ number_format($summary['rows']) }}</b></div>
                        <div class="stx-kpi stx-kpi--new"><small>New</small><b>{{ number_format($summary['new']) }}</b></div>
                        <div class="stx-kpi stx-kpi--update"><small>Update</small><b>{{ number_format($summary['update']) }}</b></div>
                        <div class="stx-kpi"><small>Skip</small><b>{{ number_format($summary['skip']) }}</b></div>
                        <div class="stx-kpi {{ $summary['error'] ? 'stx-kpi--error' : '' }}"><small>Issues</small><b>{{ number_format($summary['error']) }}</b></div>
                        <div class="stx-kpi">
                            <small>Parent logins</small>
                            <b>{{ number_format($summary['parents_new']) }} <em>new</em></b>
                            <span>{{ number_format($summary['parents_existing']) }} already {{ $summary['parents_existing'] === 1 ? 'has' : 'have' }} one</span>
                        </div>
                    </div>

                    <div class="stx-card stx-ready">
                        <div class="stx-ready__count">
                            <b>{{ number_format($ready) }}</b>
                            <small>{{ $ready === 1 ? 'student' : 'students' }} ready to import</small>
                            <p>
                                @if ($summary['error'])
                                    {{ number_format($summary['error']) }} {{ $summary['error'] === 1 ? 'row has issues and' : 'rows with issues' }} will not be imported. Download them, fix the sheet and upload it again.
                                @elseif ($summary['skip'])
                                    {{ number_format($summary['skip']) }} existing {{ $summary['skip'] === 1 ? 'student is' : 'students are' }} skipped.
                                @else
                                    Every row passed the check.
                                @endif
                            </p>
                        </div>
                        <div class="stx-ready__acts">
                            @if ($summary['error'])
                                <button type="button" class="stx-btn stx-btn--soft" wire:click="downloadIssues"><i class="fa fa-download"></i> Rows with issues</button>
                            @endif
                            <div class="stx-seg" role="group" aria-label="Admission number already exists">
                                <button type="button" class="{{ $duplicateStrategy === 'skip' ? 'is-on' : '' }}" wire:click="$set('duplicateStrategy', 'skip')" title="Existing admission numbers are skipped">Skip existing</button>
                                <button type="button" class="{{ $duplicateStrategy === 'update' ? 'is-on' : '' }}" wire:click="$set('duplicateStrategy', 'update')" title="Existing admission numbers are updated">Update existing</button>
                            </div>
                            <button type="button" class="stx-btn stx-btn--ghost" wire:click="goToStep(2)" title="Back to columns"><i class="fa fa-arrow-left"></i></button>
                            <button type="button" class="stx-btn stx-btn--primary" wire:click="startImport" wire:loading.attr="disabled" wire:target="startImport,duplicateStrategy" @disabled($ready === 0)>
                                <i class="fa fa-check-circle"></i> Import {{ number_format($ready) }} {{ $ready === 1 ? 'student' : 'students' }}
                            </button>
                        </div>
                    </div>

                    <div class="stx-card stx-busy" wire:loading.class="is-busy" wire:target="duplicateStrategy,rowFilter,search,gotoPage,nextPage,previousPage">
                        <div class="stx-toolbar">
                            <div class="stx-seg" role="group" aria-label="Show rows">
                                @foreach ($this->filters() as $filter)
                                    <button type="button" class="{{ $rowFilter === $filter ? 'is-on' : '' }}" wire:click="$set('rowFilter', '{{ $filter }}')">
                                        {{ $filterLabels[$filter] }}<em>{{ number_format($filter === 'all' ? $summary['rows'] : $summary[$filter]) }}</em>
                                    </button>
                                @endforeach
                            </div>
                            <label class="stx-search">
                                <i class="fa fa-search"></i>
                                <input type="search" wire:model.live.debounce.300ms="search" value="{{ $search }}" placeholder="Admission no, name or parent" autocomplete="off">
                            </label>
                        </div>

                        <div class="stx-table-wrap">
                            <table class="stx-table">
                                <thead>
                                    <tr>
                                        <th>Row</th>
                                        <th>Result</th>
                                        <th>Admission</th>
                                        <th>Student</th>
                                        <th>Parents</th>
                                        <th>Check</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $entry)
                                        <tr class="{{ $entry['status'] === 'error' ? 'is-error' : '' }}" wire:key="row-{{ $entry['line'] }}">
                                            <td class="ln">{{ $entry['line'] }}</td>
                                            <td class="res"><span class="stx-pill stx-pill--{{ $entry['status'] }}">{{ $statusLabels[$entry['status']] }}</span></td>
                                            <td class="adm"><code>{{ $entry['admission_no'] !== '' ? $entry['admission_no'] : '—' }}</code></td>
                                            <td class="who">
                                                <b>{{ $entry['name'] !== '' ? $entry['name'] : '—' }}</b>
                                                @php $details = array_filter([$entry['class'], ucfirst($entry['gender'])]); @endphp
                                                @if ($details)
                                                    <small>{{ implode(' · ', $details) }}</small>
                                                @endif
                                                @if ($entry['card_uid'])
                                                    <small><i class="fa fa-credit-card"></i> <code>{{ $entry['card_uid'] }}</code></small>
                                                @endif
                                            </td>
                                            <td class="parents">
                                                @forelse ($entry['parents'] as $parent)
                                                    <div class="par">
                                                        {{ $parent['name'] !== '' ? $parent['name'] : '—' }}
                                                        <small>{{ $relationLabels[$parent['relation']] ?? ucfirst($parent['relation']) }}{{ $parent['mobile'] !== '' ? ' · '.$parent['mobile'] : '' }}</small>
                                                    </div>
                                                @empty
                                                    <small>—</small>
                                                @endforelse
                                            </td>
                                            <td class="chk">
                                                @foreach ($entry['issues'] as $issue)
                                                    <div class="stx-msg stx-msg--error"><i class="fa fa-exclamation-circle"></i> {{ $issue }}</div>
                                                @endforeach
                                                @foreach ($entry['notes'] as $note)
                                                    <div class="stx-msg stx-msg--note"><i class="fa fa-info-circle"></i> {{ $note }}</div>
                                                @endforeach
                                                @if (! $entry['issues'] && ! $entry['notes'])
                                                    @if ($entry['status'] === 'skip')
                                                        <div class="stx-msg stx-msg--mut"><i class="fa fa-forward"></i> Already in the school — skipped</div>
                                                    @elseif ($entry['status'] === 'update')
                                                        <div class="stx-msg stx-msg--ok"><i class="fa fa-refresh"></i> Will be updated</div>
                                                    @else
                                                        <div class="stx-msg stx-msg--ok"><i class="fa fa-check"></i> Ready</div>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="stx-empty">No rows match.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($rows && $rows->total() > 0)
                            @php
                                $current = $rows->currentPage();
                                $last = $rows->lastPage();
                                $window = array_values(array_unique(array_filter([1, $current - 1, $current, $current + 1, $last], fn ($p) => $p >= 1 && $p <= $last)));
                            @endphp
                            <div class="stx-pager">
                                <span>Showing {{ number_format($rows->firstItem()) }}–{{ number_format($rows->lastItem()) }} of {{ number_format($rows->total()) }}</span>
                                @if ($last > 1)
                                    <div class="stx-pages">
                                        <button type="button" wire:click="previousPage" @disabled($current === 1) aria-label="Previous page">‹</button>
                                        @foreach ($window as $i => $p)
                                            @if ($i > 0 && $p - $window[$i - 1] > 1)
                                                <span>…</span>
                                            @endif
                                            <button type="button" class="{{ $p === $current ? 'is-on' : '' }}" wire:click="gotoPage({{ $p }})">{{ $p }}</button>
                                        @endforeach
                                        <button type="button" wire:click="nextPage" @disabled($current === $last) aria-label="Next page">›</button>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ======================================================= step 4 --}}
                @if ($step === 4)
                    <div class="stx-card stx-run" @unless ($finished || $failed) wire:poll.2s @endunless>
                        @if ($finished)
                            <div class="stx-ring is-ok"><i class="fa fa-check"></i></div>
                            <h3>Import finished</h3>
                            <p>{{ $status['message'] ?? '' }}.</p>
                            <div class="stx-run__tiles">
                                <div class="stx-kpi stx-kpi--new"><small>Added</small><b>{{ number_format($status['created'] ?? 0) }}</b></div>
                                <div class="stx-kpi stx-kpi--update"><small>Updated</small><b>{{ number_format($status['updated'] ?? 0) }}</b></div>
                                <div class="stx-kpi"><small>Skipped</small><b>{{ number_format($status['skipped'] ?? 0) }}</b></div>
                                <div class="stx-kpi {{ ($status['failed'] ?? 0) ? 'stx-kpi--error' : '' }}"><small>Not imported</small><b>{{ number_format($status['failed'] ?? 0) }}</b></div>
                            </div>
                            @if (! empty($status['errors']))
                                <div class="stx-card stx-run__errors">
                                    <div class="stx-card__head">
                                        <h3><i class="fa fa-exclamation-circle"></i> Not imported</h3>
                                        @if (($status['failed'] ?? 0) > count($status['errors']))
                                            <span class="stx-chip">first {{ count($status['errors']) }} — all are in your notifications</span>
                                        @endif
                                    </div>
                                    <ul>
                                        @foreach ($status['errors'] as $error)
                                            <li>
                                                <code>{{ $error['row'] ?? '' }}</code>
                                                <span><b>{{ $error['admission_no'] ?: '—' }}</b> {{ $error['name'] }} <small>{{ $error['message'] }}</small></span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="stx-run__acts">
                                <a href="{{ route('student::index') }}" class="stx-btn stx-btn--primary"><i class="fa fa-users"></i> Go to Students</a>
                                <button type="button" class="stx-btn stx-btn--ghost" wire:click="startOver"><i class="fa fa-cloud-upload"></i> Import another file</button>
                            </div>
                        @elseif ($failed)
                            <div class="stx-ring is-bad"><i class="fa fa-times"></i></div>
                            <h3>The import stopped</h3>
                            <p>{{ $status['message'] ?? 'Something went wrong while importing.' }}</p>
                            <div class="stx-run__acts">
                                <a href="{{ route('student::index') }}" class="stx-btn stx-btn--ghost"><i class="fa fa-users"></i> Go to Students</a>
                                <button type="button" class="stx-btn stx-btn--primary" wire:click="startOver"><i class="fa fa-cloud-upload"></i> Start again</button>
                            </div>
                        @else
                            <div class="stx-ring" style="--p: {{ max(0, $progress) }}"><span><b>{{ max(0, $progress) }}%</b></span></div>
                            <h3>Importing {{ number_format($ready) }} {{ $ready === 1 ? 'student' : 'students' }}</h3>
                            <p>
                                {{ $status ? 'This runs in the background, so you can leave the page.' : 'Waiting for the background queue to pick it up…' }}
                                Rows that fail are sent to your notifications.
                            </p>
                            <div class="stx-bar"><span style="width: {{ max(2, $progress) }}%"></span></div>
                            <div class="stx-run__meta">
                                <span class="stx-pill stx-pill--new"><i class="fa fa-user-plus"></i> {{ number_format($summary['new'] ?? 0) }} new</span>
                                <span class="stx-pill stx-pill--update"><i class="fa fa-refresh"></i> {{ number_format($summary['update'] ?? 0) }} to update</span>
                                @if (($summary['error'] ?? 0) > 0)
                                    <span class="stx-pill stx-pill--skip"><i class="fa fa-ban"></i> {{ number_format($summary['error']) }} left out</span>
                                @endif
                            </div>
                            <div class="stx-run__acts">
                                <a href="{{ route('student::index') }}" class="stx-btn stx-btn--ghost"><i class="fa fa-arrow-left"></i> Go to Students</a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
