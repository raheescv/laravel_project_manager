<div class="content__boxed">
    <div class="content__wrap">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 rounded-4 bg-primary p-4 text-white shadow-sm">
            <div>
                <div class="small fw-semibold text-uppercase opacity-75">Database-backed migration audit</div>
                <h1 class="h3 mb-1 text-white">Live rent-out comparison</h1>
                <div class="opacity-75">
                    Site 1 and Site 2 data with server-side verification.
                    @if ($lastComparedAt)
                        Last checked {{ \Illuminate\Support\Carbon::parse($lastComparedAt)->diffForHumans() }}.
                    @endif
                </div>
            </div>
            <button
                type="button"
                class="btn btn-light fw-semibold"
                wire:click="runComparison"
                wire:loading.attr="disabled"
                wire:target="runComparison"
            >
                <span wire:loading.remove wire:target="runComparison"><i class="pli-refresh me-2"></i>Run comparison</span>
                <span wire:loading wire:target="runComparison">Comparing both sites…</span>
            </button>
        </div>

        @if ($statusMessage)
            <div class="alert alert-info mt-3 mb-0" role="status">{{ $statusMessage }}</div>
        @endif

        <div class="row g-3 py-3">
            @foreach ([
                ['Total records', $summary['total'], 'primary'],
                ['Matching', $summary['matching'], 'success'],
                ['Differences', $summary['differing'], 'danger'],
                ['Verified', $summary['verified'], 'info'],
            ] as [$label, $value, $colour])
                <div class="col-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="small text-secondary">{{ $label }}</div>
                            <div class="display-6 fw-bold text-{{ $colour }}">{{ number_format($value) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($selectedRecord)
            @php
                $payload = $selectedRecord->payload;
                $displayValue = static function (mixed $value): string {
                    if ($value === null || $value === '') {
                        return '—';
                    }

                    if (is_bool($value)) {
                        return $value ? 'Yes' : 'No';
                    }

                    if (is_array($value)) {
                        return implode(', ', array_map(static fn (mixed $item): string => (string) $item, $value));
                    }

                    return (string) $value;
                };
            @endphp
            <section class="card border-0 shadow-sm mb-3" wire:key="comparison-detail-{{ $selectedRecord->id }}">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 bg-white">
                    <div>
                        <div class="small text-secondary">{{ $selectedRecord->category }}</div>
                        <h2 class="h5 mb-0">Record #{{ $selectedRecord->rent_out_id }}</h2>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-primary btn-sm" href="{{ $selectedRecord->old_url }}" target="_blank" rel="noopener noreferrer">Site 1 ↗</a>
                        <a class="btn btn-outline-primary btn-sm" href="{{ $selectedRecord->new_url }}" target="_blank" rel="noopener noreferrer">Site 2 ↗</a>
                        <button class="btn btn-outline-secondary btn-sm" type="button" wire:click="closeRecord">Close</button>
                    </div>
                </div>
                <div class="card-body d-grid gap-3">
                    <details open>
                        <summary class="fw-bold pointer">Agreement fields</summary>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Field</th><th>Site 1</th><th>Site 2</th><th>Result</th></tr></thead>
                                <tbody>
                                    @foreach ($payload['header'] as $label => $field)
                                        <tr class="{{ $field['matches'] ? '' : 'table-danger' }}">
                                            <th>{{ $label }}</th>
                                            <td>{{ $displayValue($field['old']) }}</td>
                                            <td>{{ $displayValue($field['new']) }}</td>
                                            <td>{{ $field['matches'] ? 'Match' : 'Different' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>

                    <details>
                        <summary class="fw-bold pointer">Transactions</summary>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm">
                                <thead><tr><th>Site</th><th>Rows</th><th>Debit</th><th>Credit</th></tr></thead>
                                <tbody>
                                    <tr><th>Site 1</th><td>{{ $payload['ledger']['old']['rows'] }}</td><td>{{ number_format($payload['ledger']['old']['debit'], 2) }}</td><td>{{ number_format($payload['ledger']['old']['credit'], 2) }}</td></tr>
                                    <tr class="{{ $payload['ledger']['matches'] ? '' : 'table-danger' }}"><th>Site 2</th><td>{{ $payload['ledger']['new']['rows'] }}</td><td>{{ number_format($payload['ledger']['new']['debit'], 2) }}</td><td>{{ number_format($payload['ledger']['new']['credit'], 2) }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </details>

                    @foreach ($payload['tabs'] as $tabName => $tab)
                        <details wire:key="comparison-tab-{{ $selectedRecord->id }}-{{ \Illuminate\Support\Str::slug($tabName) }}">
                            <summary class="fw-bold pointer">
                                {{ $tabName }}
                                <span class="badge {{ $tab['difference_count'] ? 'bg-danger' : 'bg-success' }}">
                                    {{ $tab['old_count'] ?? 'N/A' }} / {{ $tab['new_count'] ?? 'N/A' }}
                                </span>
                            </summary>
                            @if (! $tab['available'])
                                <div class="alert alert-warning mt-2 mb-0">This tab is unavailable in one of the databases.</div>
                            @elseif ($tab['rows'] === [])
                                <div class="text-secondary mt-2">No records.</div>
                            @else
                                <div class="d-grid gap-3 mt-3">
                                    @foreach ($tab['rows'] as $row)
                                        <div class="border rounded-3 p-3 {{ $row['matches'] ? '' : 'border-danger' }}">
                                            <div class="fw-semibold mb-2">Row #{{ $row['id'] }}</div>
                                            <div class="table-responsive">
                                                <table class="table table-sm mb-0">
                                                    <thead><tr><th>Field</th><th>Site 1</th><th>Site 2</th><th>Result</th></tr></thead>
                                                    <tbody>
                                                        @foreach ($row['fields'] as $label => $field)
                                                            <tr class="{{ $field['matches'] ? '' : 'table-danger' }}">
                                                                <th>{{ $label }}</th>
                                                                <td>{{ $displayValue($field['old']) }}</td>
                                                                <td>{{ $displayValue($field['new']) }}</td>
                                                                <td>{{ $field['matches'] ? 'Match' : 'Different' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </details>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="card border-0 shadow-sm">
            <div class="card-body border-bottom">
                <div class="row g-2">
                    <div class="col-12 col-lg">
                        <input class="form-control" type="search" placeholder="Search ID, category or status" wire:model.live.debounce.300ms="search">
                    </div>
                    <div class="col-6 col-lg-2">
                        <select class="form-select" wire:model.live="result" aria-label="Result filter">
                            <option value="all">All results</option>
                            <option value="different">Differences</option>
                            <option value="matching">Matching</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <select class="form-select" wire:model.live="verification" aria-label="Verification filter">
                            <option value="all">All verification</option>
                            <option value="unverified">Not verified</option>
                            <option value="verified">Verified</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-3">
                        <select class="form-select" wire:model.live="category" aria-label="Category filter">
                            <option value="">All categories</option>
                            @foreach ($categories as $categoryOption)
                                <option value="{{ $categoryOption }}">{{ $categoryOption }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Record</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Result</th>
                            <th>Verification</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($comparisons as $comparison)
                            <tr wire:key="comparison-row-{{ $comparison->id }}">
                                <th>#{{ $comparison->rent_out_id }}</th>
                                <td>{{ $comparison->category }}</td>
                                <td>{{ $comparison->status ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $comparison->matches ? 'bg-success' : 'bg-danger' }}">
                                        {{ $comparison->matches ? 'Match' : $comparison->difference_count.' differences' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($comparison->verified_at)
                                        <span class="badge bg-info">Verified</span>
                                    @else
                                        <span class="badge bg-secondary">Not verified</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        <button class="btn btn-outline-primary btn-sm" type="button" wire:click="selectRecord({{ $comparison->id }})">Details</button>
                                        <button class="btn btn-outline-success btn-sm" type="button" wire:click="toggleVerified({{ $comparison->id }})">
                                            {{ $comparison->verified_at ? 'Unverify' : 'Mark verified' }}
                                        </button>
                                        <a class="btn btn-outline-secondary btn-sm" href="{{ $comparison->old_url }}" target="_blank" rel="noopener noreferrer">Site 1</a>
                                        <a class="btn btn-outline-secondary btn-sm" href="{{ $comparison->new_url }}" target="_blank" rel="noopener noreferrer">Site 2</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-5 text-center text-secondary">No comparison records match these filters. Run the comparison if the table is empty.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($comparisons->hasPages())
                <div class="card-footer bg-white">{{ $comparisons->links() }}</div>
            @endif
        </section>
    </div>
</div>
