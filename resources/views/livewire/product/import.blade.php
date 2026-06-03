<div>
    <div class="card shadow-sm border-0 import-wizard">
        {{-- ══════════════ HEADER + STEPPER ══════════════ --}}
        <div class="card-header bg-body-tertiary border-bottom py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <a href="{{ route('product::index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center shadow-sm">
                    <i class="fa fa-arrow-left me-2"></i> Back to Products
                </a>
                <button class="btn btn-success btn-sm d-inline-flex align-items-center shadow-sm" wire:click="sample">
                    <i class="fa fa-download me-2"></i>
                    <span class="d-none d-sm-inline">Download Template</span>
                    <span class="d-sm-none">Template</span>
                </button>
            </div>

            <div class="import-stepper d-flex align-items-center justify-content-between">
                @foreach (['Upload File', 'Map Columns', 'Preview', 'Importing'] as $i => $label)
                    @php $num = $i + 1; @endphp
                    <div class="step-item text-center {{ $step == $num ? 'active' : '' }} {{ $step > $num ? 'completed' : '' }}">
                        <div class="step-icon mx-auto">
                            @if ($step > $num)
                                <i class="fa fa-check"></i>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        <span class="step-label d-none d-md-block small mt-1">{{ $label }}</span>
                    </div>
                    @if ($i < 3)
                        <div class="step-line flex-fill {{ $step > $num ? 'filled' : '' }}"></div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            {{-- ══════════════ STEP 1: Upload ══════════════ --}}
            @if ($step == 1)
                {{-- Mode tabs --}}
                <div class="d-flex justify-content-center mb-4">
                    <div class="import-tabs d-inline-flex p-1 rounded-pill shadow-sm">
                        <button type="button"
                            class="import-tab btn btn-sm rounded-pill px-3 {{ $stepOneTab === 'spreadsheet' ? 'active' : '' }}"
                            wire:click="setStepOneTab('spreadsheet')">
                            <i class="fa fa-file-excel-o me-1"></i> Spreadsheet
                        </button>
                        <button type="button"
                            class="import-tab btn btn-sm rounded-pill px-3 {{ $stepOneTab === 'images' ? 'active' : '' }}"
                            wire:click="setStepOneTab('images')">
                            <i class="fa fa-picture-o me-1"></i> Images
                        </button>
                    </div>
                </div>

                @if ($stepOneTab === 'images')
                    {{-- ───────── Dropbox image matcher ───────── --}}
                    <div class="row justify-content-center">
                        <div class="col-lg-10 col-xl-9">
                            <div class="text-center mb-4">
                                <span class="hero-badge d-inline-flex align-items-center justify-content-center rounded-4 mb-3">
                                    <i class="fa fa-dropbox"></i>
                                </span>
                                <h4 class="fw-bold mb-1">Import Product Images</h4>
                                <p class="text-muted mb-0">Match Dropbox image filenames to product codes, then import in the background.</p>
                            </div>

                            <div class="card border shadow-none">
                                <div class="card-body">
                                    <label for="dropboxFolderUrl" class="form-label fw-semibold">Shared Dropbox folder link</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-link"></i></span>
                                        <input type="url" id="dropboxFolderUrl" wire:model.defer="dropboxFolderUrl" class="form-control"
                                            placeholder="https://www.dropbox.com/scl/fo/...">
                                        <button type="button" class="btn btn-primary" wire:click="checkDropboxFolderMatches" wire:loading.attr="disabled" wire:target="checkDropboxFolderMatches">
                                            <span wire:loading.remove wire:target="checkDropboxFolderMatches"><i class="fa fa-search me-1"></i> Check Matches</span>
                                            <span wire:loading wire:target="checkDropboxFolderMatches"><i class="fa fa-spinner fa-spin me-1"></i> Checking...</span>
                                        </button>
                                    </div>
                                    @error('dropboxFolderUrl')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        We compare image filenames against existing <code>product code</code> values.
                                        Examples: <code>ABC123.jpg</code>, <code>ABC123-1.jpg</code>, and <code>ABC123_front.png</code> all match code <code>ABC123</code>.
                                    </div>

                                    @if ($dropboxMatchSummary)
                                        <div class="row g-3 mt-2">
                                            <div class="col-6 col-md-3">
                                                <div class="stat-tile h-100">
                                                    <div class="stat-label">Images Found</div>
                                                    <div class="stat-value">{{ $dropboxMatchSummary['total_image_files'] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="stat-tile h-100">
                                                    <div class="stat-label">Matched Files</div>
                                                    <div class="stat-value text-success">{{ $dropboxMatchSummary['matched_image_files'] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="stat-tile h-100">
                                                    <div class="stat-label">Matched Codes</div>
                                                    <div class="stat-value text-success">{{ $dropboxMatchSummary['matching_product_codes'] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="stat-tile h-100">
                                                    <div class="stat-label">Unmatched</div>
                                                    <div class="stat-value text-danger">{{ $dropboxMatchSummary['missing_image_files'] }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        @if (!empty($dropboxMatchSummary['matched_products']))
                                            <div class="mt-4">
                                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                                    <h6 class="fw-bold mb-0">Matched Products</h6>
                                                    <button type="button" class="btn btn-success btn-sm" wire:click="importDropboxFolderImages"
                                                        wire:loading.attr="disabled" wire:target="importDropboxFolderImages">
                                                        <span wire:loading.remove wire:target="importDropboxFolderImages">
                                                            <i class="fa fa-picture-o me-1"></i> Import Matched Images
                                                        </span>
                                                        <span wire:loading wire:target="importDropboxFolderImages"><i class="fa fa-spinner fa-spin me-1"></i> Queueing...</span>
                                                    </button>
                                                </div>
                                                <div class="table-responsive border rounded-3" style="max-height: 320px;">
                                                    <table class="table table-sm table-hover align-middle mb-0">
                                                        <thead class="table-light position-sticky top-0">
                                                            <tr>
                                                                <th style="width: 140px;">Code</th>
                                                                <th>Name</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($dropboxMatchSummary['matched_products'] as $product)
                                                                <tr>
                                                                    <td><code>{{ $product['code'] }}</code></td>
                                                                    <td>{{ $product['name'] }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($dropboxImportQueued)
                                            <div class="alert alert-info mt-4 mb-0 d-flex align-items-center">
                                                <i class="fa fa-clock-o me-2 fs-5"></i>
                                                Matched product images have been queued for background import.
                                            </div>
                                        @endif

                                        @if ($dropboxImportSummary)
                                            <div class="row g-3 mt-3">
                                                <div class="col-md-4">
                                                    <div class="stat-tile h-100">
                                                        <div class="stat-label">Imported Images</div>
                                                        <div class="stat-value text-success">{{ $dropboxImportSummary['imported_images'] }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="stat-tile h-100">
                                                        <div class="stat-label">Skipped Duplicates</div>
                                                        <div class="stat-value">{{ $dropboxImportSummary['skipped_duplicates'] }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="stat-tile h-100">
                                                        <div class="stat-label">Matched Codes</div>
                                                        <div class="stat-value">{{ $dropboxImportSummary['matched_product_codes'] }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if (!empty($dropboxMatchSummary['missing_codes']))
                                            <div class="mt-4">
                                                <h6 class="fw-bold mb-2">Missing Codes <span class="text-muted fw-normal small">(first 50)</span></h6>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach ($dropboxMatchSummary['missing_codes'] as $code)
                                                        <span class="badge bg-danger-subtle text-danger border">{{ $code }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- ───────── Spreadsheet upload ───────── --}}
                    <div class="row g-4 align-items-stretch">
                        {{-- Left: strategy + upload --}}
                        <div class="col-lg-5">
                            <label class="fw-semibold small text-uppercase text-muted mb-2 d-block">
                                <i class="fa fa-cogs me-1"></i> On Duplicate Product
                            </label>
                            <div class="d-flex gap-2 mb-2">
                                <label class="dup-card flex-fill text-center p-3 rounded-3 border {{ $duplicateStrategy === 'skip' ? 'is-skip' : '' }}">
                                    <input type="radio" wire:model.live="duplicateStrategy" value="skip" class="d-none">
                                    <i class="fa fa-forward d-block mb-1" style="font-size:1.5rem;"></i>
                                    <span class="fw-semibold small d-block">Skip</span>
                                    <span class="text-muted" style="font-size:10px;">Keep existing record</span>
                                </label>
                                <label class="dup-card flex-fill text-center p-3 rounded-3 border {{ $duplicateStrategy === 'update' ? 'is-update' : '' }}">
                                    <input type="radio" wire:model.live="duplicateStrategy" value="update" class="d-none">
                                    <i class="fa fa-refresh d-block mb-1" style="font-size:1.5rem;"></i>
                                    <span class="fw-semibold small d-block">Update</span>
                                    <span class="text-muted" style="font-size:10px;">Overwrite with new values</span>
                                </label>
                            </div>
                            <div class="text-muted mb-3" style="font-size: 11px;">
                                <i class="fa fa-info-circle me-1"></i>
                                Matched by <strong>ID</strong> &rarr; <strong>Code (SKU)</strong> &rarr; <strong>Name + Main Category</strong>.
                            </div>

                            <div class="upload-zone text-center d-flex flex-column justify-content-center">
                                <input type="file" wire:model="file" class="upload-input" accept=".xlsx,.xls,.csv">
                                <div wire:loading.remove wire:target="file" class="p-4">
                                    <i class="fa fa-cloud-upload text-primary d-block mb-2" style="font-size: 2.75rem;"></i>
                                    <p class="fw-semibold mb-1">Drop your file here, or <span class="text-primary">browse</span></p>
                                    <span class="text-muted small">XLSX, XLS, CSV &middot; Max 10 MB</span>
                                </div>
                                <div wire:loading wire:target="file" class="p-4">
                                    <div class="spinner-border text-primary mb-2" role="status"></div>
                                    <p class="fw-semibold mb-0">Uploading...</p>
                                </div>
                            </div>
                            @error('file')
                                <div class="alert alert-danger mt-2 mb-0 small">
                                    <i class="fa fa-exclamation-triangle me-1"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Right: how it works --}}
                        <div class="col-lg-7">
                            <h6 class="fw-bold text-uppercase small text-muted mb-3"><i class="fa fa-info-circle me-1"></i> How It Works</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="hiw-card d-flex gap-3 h-100">
                                        <span class="hiw-icon text-primary bg-primary bg-opacity-10"><i class="fa fa-list-alt"></i></span>
                                        <div>
                                            <div class="fw-bold small mb-1">Smart Column Mapping</div>
                                            <p class="text-muted small mb-0">Headers like <strong>Name</strong>, <strong>SKU</strong>, <strong>Brand</strong>, <strong>Category</strong> are auto-matched. Review &amp; adjust next.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="hiw-card d-flex gap-3 h-100">
                                        <span class="hiw-icon text-success bg-success bg-opacity-10"><i class="fa fa-sitemap"></i></span>
                                        <div>
                                            <div class="fw-bold small mb-1">Auto-Create Master Data</div>
                                            <p class="text-muted small mb-0">Departments, categories, brands &amp; units referenced by name are <strong>created automatically</strong>.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="hiw-card d-flex gap-3 h-100">
                                        <span class="hiw-icon text-warning bg-warning bg-opacity-10"><i class="fa fa-shield"></i></span>
                                        <div>
                                            <div class="fw-bold small mb-1">Duplicate-Safe</div>
                                            <p class="text-muted small mb-0">Existing products are <strong>skipped or updated</strong> based on your selection above.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="hiw-card d-flex gap-3 h-100">
                                        <span class="hiw-icon text-info bg-info bg-opacity-10"><i class="fa fa-tasks"></i></span>
                                        <div>
                                            <div class="fw-bold small mb-1">Background Processing</div>
                                            <p class="text-muted small mb-0">Large files run in a <strong>background queue</strong> with a live progress bar — leave this page safely.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- ══════════════ STEP 2: Map Columns ══════════════ --}}
            @if ($step == 2)
                @php $mappedCount = collect($mappings)->filter()->count(); @endphp
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0 map-table">
                                <thead class="table-light text-uppercase small fw-bold">
                                    <tr>
                                        <th style="width: 46%">Database Field</th>
                                        <th>Excel Column</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($availableFields as $field => $label)
                                        @php $isMapped = !empty($mappings[$field] ?? ''); @endphp
                                        <tr class="{{ $isMapped ? 'row-mapped' : '' }}">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa {{ $isMapped ? 'fa-check-circle text-success' : 'fa-circle-o text-muted' }}"></i>
                                                    <div>
                                                        <div class="fw-medium">{{ $label }}</div>
                                                        <small class="text-muted">{{ $field }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <select wire:model.live="mappings.{{ $field }}" class="form-select form-select-sm shadow-sm {{ $isMapped ? 'border-success' : 'border-secondary-subtle' }}">
                                                    <option value="">-- Do Not Import --</option>
                                                    @foreach ($headers as $header)
                                                        <option value="{{ $header }}">{{ $header }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card bg-body-tertiary border-0 sticky-top" style="top: 20px;">
                            <div class="card-body p-3 text-center">
                                <i class="fa fa-random fs-3 text-info mb-2"></i>
                                <h5 class="fw-bold mb-1">Column Mapping</h5>
                                <p class="text-muted small mb-2">Match your spreadsheet columns to product fields. Auto-matched headers are pre-selected.</p>
                                <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle mb-3">
                                    {{ $mappedCount }} of {{ count($availableFields) }} fields mapped
                                </span>
                                @error('mappings.name')
                                    <div class="alert alert-danger small text-start py-2">
                                        <i class="fa fa-exclamation-triangle me-1"></i> {{ $message }}
                                    </div>
                                @enderror
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary" wire:click="goToStep(3)">
                                        Next: Preview <i class="fa fa-arrow-right ms-1"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" wire:click="goToStep(1)">
                                        <i class="fa fa-arrow-left me-1"></i> Back
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ══════════════ STEP 3: Preview ══════════════ --}}
            @if ($step == 3)
                @php
                    $mappedFields = array_filter($mappings);
                    $colCount = count($mappedFields);
                @endphp
                <div class="excel-sheet-wrapper">
                    <div class="excel-formula-bar d-flex align-items-center gap-2 px-2 py-1">
                        <span class="excel-cell-ref px-2 py-1 small fw-bold">A1</span>
                        <span class="text-muted small"><i class="fa fa-table me-1"></i> Preview &middot; First {{ count($previewData) }} rows of your file</span>
                        <span class="ms-auto text-muted small">{{ $colCount }} columns mapped</span>
                    </div>
                    <div class="table-responsive excel-grid">
                        <table class="table table-bordered mb-0 excel-table">
                            <thead>
                                <tr class="excel-col-header">
                                    <th class="excel-row-num"></th>
                                    @php $colLetter = 'A'; @endphp
                                    @foreach ($mappedFields as $field => $header)
                                        <th>{{ $colLetter++ }}</th>
                                    @endforeach
                                </tr>
                                <tr class="excel-header-row">
                                    <th class="excel-row-num">1</th>
                                    @foreach ($mappedFields as $field => $header)
                                        <th>
                                            <div class="fw-semibold">{{ $availableFields[$field] }}</div>
                                            <div class="excel-field-hint">{{ $header }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($previewData as $rowIndex => $row)
                                    <tr>
                                        <td class="excel-row-num">{{ $rowIndex + 2 }}</td>
                                        @foreach ($mappedFields as $field => $header)
                                            <td class="excel-cell">{{ $row[array_search($header, $headers)] ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="excel-sheet-tabs d-flex align-items-center gap-1 px-2 py-1">
                        <span class="excel-tab active"><i class="fa fa-file-excel-o me-1"></i> Sheet1</span>
                        <span class="excel-tab-add"><i class="fa fa-plus"></i></span>
                    </div>
                </div>

                <div class="alert {{ $duplicateStrategy === 'update' ? 'alert-success' : 'alert-info' }} mt-3 mb-0 small d-flex align-items-center">
                    <i class="fa {{ $duplicateStrategy === 'update' ? 'fa-refresh' : 'fa-forward' }} me-2 fs-5"></i>
                    <span>
                        Duplicate strategy: <strong>{{ ucfirst($duplicateStrategy) }}</strong>.
                        Existing products matched by <em>ID &rarr; Code &rarr; Name + Main Category</em> will be {{ $duplicateStrategy === 'update' ? 'updated with the new values' : 'skipped' }}.
                    </span>
                </div>

                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button class="btn btn-outline-secondary px-4" wire:click="goToStep(2)">
                        <i class="fa fa-arrow-left me-1"></i> Back to Mapping
                    </button>
                    <button class="btn btn-success px-4 shadow-sm" wire:click="save">
                        <i class="fa fa-check-circle me-1"></i> Start Import
                    </button>
                </div>
            @endif

            {{-- ══════════════ STEP 4: Importing ══════════════ --}}
            @if ($step == 4)
                <div class="text-center py-5">
                    <div class="mb-3">
                        <div class="spinner-grow text-success" role="status" style="width: 4rem; height: 4rem;"></div>
                    </div>
                    <h4 class="fw-bold">Import in Progress</h4>
                    <p class="text-muted mb-4">Processing your products in the background. You can leave this page.</p>

                    <div class="progress mx-auto shadow-sm mb-2" style="height: 24px; max-width: 500px; border-radius: 12px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold" id="progress-bar" role="progressbar" style="width: 0%"
                            aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('product::index') }}" class="btn btn-outline-primary px-4">
                            Go to Product List
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
    <style>
        /* ── Stepper ── */
        .import-stepper {
            max-width: 720px;
            margin: 0 auto;
        }

        .step-item {
            flex: 0 0 auto;
        }

        .step-icon {
            width: 32px;
            height: 32px;
            line-height: 28px;
            border-radius: 50%;
            border: 2px solid var(--bs-border-color, #dee2e6);
            background: var(--bs-body-bg, #fff);
            color: var(--bs-secondary-color, #6c757d);
            font-weight: bold;
            font-size: .85rem;
            text-align: center;
            transition: all .3s ease;
        }

        .step-label {
            color: var(--bs-secondary-color, #6c757d);
            font-weight: 600;
        }

        .step-item.active .step-icon {
            border-color: var(--bs-primary);
            background: var(--bs-primary);
            color: #fff;
            box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), .15);
        }

        .step-item.active .step-label {
            color: var(--bs-primary);
        }

        .step-item.completed .step-icon {
            border-color: var(--bs-success);
            background: var(--bs-success);
            color: #fff;
        }

        .step-line {
            height: 2px;
            background: var(--bs-border-color, #dee2e6);
            margin: 0 .5rem;
            margin-bottom: 1.25rem;
            transition: background .3s ease;
        }

        .step-line.filled {
            background: var(--bs-success);
        }

        @media (max-width: 767.98px) {
            .step-line { margin-bottom: 0; }
        }

        /* ── Mode tabs ── */
        .import-tabs {
            background: var(--bs-tertiary-bg, #f1f3f5);
        }

        .import-tab {
            border: 0;
            color: var(--bs-secondary-color, #6c757d);
            background: transparent;
            font-weight: 600;
        }

        .import-tab.active {
            background: var(--bs-body-bg, #fff);
            color: var(--bs-primary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
        }

        /* ── Hero badge ── */
        .hero-badge {
            width: 64px;
            height: 64px;
            font-size: 1.9rem;
            color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), .1);
        }

        /* ── Duplicate strategy cards ── */
        .dup-card {
            cursor: pointer;
            transition: all .2s ease;
            background: var(--bs-body-bg);
        }

        .dup-card:hover {
            border-color: var(--bs-primary);
            transform: translateY(-1px);
        }

        .dup-card i,
        .dup-card span { color: var(--bs-secondary-color, #6c757d); }

        .dup-card.is-skip {
            border-color: var(--bs-primary) !important;
            background: rgba(var(--bs-primary-rgb), .1);
        }

        .dup-card.is-skip i,
        .dup-card.is-skip .fw-semibold { color: var(--bs-primary) !important; }

        .dup-card.is-update {
            border-color: var(--bs-success) !important;
            background: rgba(var(--bs-success-rgb), .1);
        }

        .dup-card.is-update i,
        .dup-card.is-update .fw-semibold { color: var(--bs-success) !important; }

        /* ── Upload zone ── */
        .upload-zone {
            border: 2px dashed var(--bs-border-color, #dee2e6);
            border-radius: 14px;
            position: relative;
            background: var(--bs-tertiary-bg, #f8f9fa);
            transition: border-color .2s, background .2s;
            min-height: 180px;
        }

        .upload-zone:hover {
            border-color: var(--bs-primary);
            background: var(--bs-body-bg, #fff);
        }

        .upload-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
            font-size: 0;
        }

        /* ── How it works ── */
        .hiw-card {
            padding: 1rem;
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: 12px;
            background: var(--bs-body-bg);
            transition: box-shadow .2s, transform .2s;
        }

        .hiw-card:hover {
            box-shadow: 0 .4rem 1rem rgba(0, 0, 0, .06);
            transform: translateY(-2px);
        }

        .hiw-icon {
            flex-shrink: 0;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }

        /* ── Stat tiles (dropbox) ── */
        .stat-tile {
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: 12px;
            padding: .85rem 1rem;
            background: var(--bs-tertiary-bg);
        }

        .stat-label {
            font-size: .7rem;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--bs-secondary-color);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
        }

        /* ── Mapping table ── */
        .map-table .row-mapped {
            background: rgba(var(--bs-success-rgb), .04);
        }

        /* ── Excel-style preview grid ── */
        .excel-sheet-wrapper {
            border: 1px solid #b4b4b4;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
        }

        [data-bs-theme="dark"] .excel-sheet-wrapper {
            border-color: var(--bs-border-color);
            background: var(--bs-body-bg);
        }

        .excel-formula-bar {
            background: #f3f3f3;
            border-bottom: 1px solid #d4d4d4;
            min-height: 28px;
        }

        [data-bs-theme="dark"] .excel-formula-bar {
            background: var(--bs-tertiary-bg);
            border-color: var(--bs-border-color);
        }

        .excel-cell-ref {
            background: #fff;
            border: 1px solid #d4d4d4;
            font-family: 'Segoe UI', Calibri, sans-serif;
            min-width: 50px;
            text-align: center;
        }

        [data-bs-theme="dark"] .excel-cell-ref {
            background: var(--bs-body-bg);
            border-color: var(--bs-border-color);
        }

        .excel-grid { margin: 0; }

        .excel-table {
            font-family: 'Segoe UI', Calibri, Arial, sans-serif;
            font-size: 12px;
            border-collapse: collapse;
        }

        .excel-table th,
        .excel-table td {
            border: 1px solid #d4d4d4 !important;
            padding: 3px 8px !important;
            vertical-align: middle;
        }

        [data-bs-theme="dark"] .excel-table th,
        [data-bs-theme="dark"] .excel-table td {
            border-color: var(--bs-border-color) !important;
        }

        .excel-col-header th {
            background: #e8e8e8;
            color: #444;
            text-align: center;
            font-weight: normal;
            font-size: 11px;
            padding: 2px 8px !important;
        }

        [data-bs-theme="dark"] .excel-col-header th {
            background: var(--bs-tertiary-bg);
            color: var(--bs-body-color);
        }

        .excel-header-row th {
            background: #e2efda;
            color: #1a3a1a;
            font-size: 11px;
            white-space: nowrap;
        }

        [data-bs-theme="dark"] .excel-header-row th {
            background: rgba(var(--bs-success-rgb), 0.15);
            color: var(--bs-body-color);
        }

        .excel-field-hint {
            font-weight: normal;
            color: #777;
            font-size: 10px;
        }

        .excel-row-num {
            background: #e8e8e8 !important;
            color: #444;
            text-align: center !important;
            font-weight: normal;
            width: 40px;
            min-width: 40px;
            font-size: 11px;
        }

        [data-bs-theme="dark"] .excel-row-num {
            background: var(--bs-tertiary-bg) !important;
            color: var(--bs-body-color);
        }

        .excel-cell {
            background: #fff;
            white-space: nowrap;
        }

        [data-bs-theme="dark"] .excel-cell {
            background: var(--bs-body-bg);
        }

        .excel-cell:hover {
            outline: 2px solid #217346;
            outline-offset: -1px;
            z-index: 1;
            position: relative;
        }

        .excel-sheet-tabs {
            background: #e8e8e8;
            border-top: 1px solid #d4d4d4;
        }

        [data-bs-theme="dark"] .excel-sheet-tabs {
            background: var(--bs-tertiary-bg);
            border-color: var(--bs-border-color);
        }

        .excel-tab {
            background: #fff;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            padding: 3px 14px;
            font-size: 11px;
            border-radius: 3px 3px 0 0;
            cursor: default;
        }

        [data-bs-theme="dark"] .excel-tab {
            background: var(--bs-body-bg);
            border-color: var(--bs-border-color);
        }

        .excel-tab.active { font-weight: 600; }

        .excel-tab-add {
            color: #777;
            font-size: 11px;
            padding: 3px 8px;
            cursor: default;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        let fileImportProgressInitialized = false;
        function initFileImportProgress() {
            if (fileImportProgressInitialized) return;
            fileImportProgressInitialized = true;
            const pusher = new Pusher("{{ config('services.pusher.pusher_app_key') }}", {
                cluster: "{{ config('services.pusher.pusher_app_cluster') }}",
                encrypted: true,
            });
            const channel = pusher.subscribe('file-import-channel-{{ auth()->id() }}');
            channel.bind('file-import-event-{{ auth()->id() }}', function(data) {
                if (data.type === 'Product') {
                    const progressBar = document.getElementById('progress-bar');
                    if (progressBar) {
                        progressBar.style.width = `${data.progress}%`;
                        progressBar.setAttribute('aria-valuenow', data.progress);
                        progressBar.textContent = `${Math.round(data.progress)}%`;

                        if (data.progress >= 100) {
                            setTimeout(() => {
                                alert('Import completed successfully!');
                                window.location.href = "{{ route('product::index') }}";
                            }, 1000);
                        }
                    }
                }
            });
        }

        document.addEventListener('livewire:initialized', initFileImportProgress);
        if (window.Livewire) {
            initFileImportProgress();
        }
    </script>
@endpush
