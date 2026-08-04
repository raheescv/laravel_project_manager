@php
    $logoSlots = [
        $existing_rental_reservation_logo,
        $existing_lease_reservation_logo,
        $existing_lease_residential_logo,
        $existing_rental_residential_logo,
        $existing_rent_out_agreement_footer,
    ];
    $logosSet = count(array_filter($logoSlots));
    $logosTotal = count($logoSlots);
    $mandatoryCount = count($mandatory_document_types);
    $agreementImageCount = count($existing_rent_out_agreement_images);
@endphp

<div class="roc" x-data="{ tab: 'docs' }">
    <style>
        /* ============================================================
           .roc — Rent Out Configuration premium tabs
           Accent follows the app theme colour (--bs-primary).
           ============================================================ */
        .roc [x-cloak] {
            display: none !important;
        }

        .roc {
            --roc-brand: var(--bs-primary);
            --roc-brand-rgb: var(--bs-primary-rgb);
            --roc-radius: 14px;
            --roc-radius-sm: 10px;
            --roc-line: color-mix(in srgb, var(--roc-brand), transparent 78%);
            --roc-shadow: 0 10px 30px -18px rgba(var(--roc-brand-rgb), .45);
            min-width: 0;
        }

        .roc-shell {
            border: 1px solid var(--bs-border-color);
            border-radius: var(--roc-radius);
            background: var(--bs-body-bg);
            box-shadow: 0 2px 10px -4px rgba(var(--bs-emphasis-color-rgb), .12);
            overflow: hidden;
        }

        .roc-head {
            position: relative;
            isolation: isolate;
            padding: .95rem 1.15rem;
            border-bottom: 1px solid var(--bs-border-color-translucent);
            background:
                radial-gradient(70% 160% at 100% 0%, color-mix(in srgb, var(--roc-brand), transparent 86%), transparent 62%),
                linear-gradient(135deg, color-mix(in srgb, var(--roc-brand), transparent 93%), transparent 70%);
        }

        .roc-head::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            opacity: .55;
            background-image: radial-gradient(circle at 1px 1px, rgba(var(--roc-brand-rgb), .07) 1px, transparent 0);
            background-size: 20px 20px;
            -webkit-mask-image: linear-gradient(180deg, #000, transparent 85%);
            mask-image: linear-gradient(180deg, #000, transparent 85%);
        }

        .roc-head-ic {
            width: 42px;
            height: 42px;
            flex: 0 0 auto;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            color: var(--roc-brand);
            background: color-mix(in srgb, var(--roc-brand), transparent 86%);
            border: 1px solid var(--roc-line);
        }

        .roc-eyebrow {
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--roc-brand);
        }

        .roc-head h5 {
            font-size: 1.02rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -.01em;
        }

        .roc-head p {
            margin: .1rem 0 0;
            font-size: .8rem;
            color: var(--bs-secondary-color);
        }

        /* ---- Pill rail ---- */
        .roc-rail-wrap {
            padding: .7rem 1.15rem 0;
            border-bottom: 1px solid var(--bs-border-color-translucent);
            background: linear-gradient(180deg, var(--bs-tertiary-bg), transparent);
        }

        .roc-rail {
            display: flex;
            gap: .4rem;
            overflow-x: auto;
            scrollbar-width: thin;
            padding-bottom: .7rem;
        }

        .roc-rail::-webkit-scrollbar {
            height: 4px;
        }

        .roc-rail::-webkit-scrollbar-thumb {
            background: var(--bs-border-color);
            border-radius: 4px;
        }

        .roc-tab {
            all: unset;
            box-sizing: border-box;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .45rem .8rem;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 600;
            color: var(--bs-secondary-color);
            border: 1px solid transparent;
            white-space: nowrap;
            transition: all .16s ease;
        }

        .roc-tab i {
            font-size: .9rem;
            opacity: .85;
        }

        .roc-tab:hover {
            color: var(--bs-body-color);
            background: var(--bs-secondary-bg);
        }

        .roc-tab.is-active {
            color: #fff;
            background: linear-gradient(135deg, var(--roc-brand), color-mix(in srgb, var(--roc-brand), #000 22%));
            border-color: color-mix(in srgb, var(--roc-brand), #000 12%);
            box-shadow: var(--roc-shadow);
        }

        .roc-badge {
            font-size: .62rem;
            font-weight: 700;
            padding: .1rem .38rem;
            border-radius: 20px;
            background: color-mix(in srgb, var(--roc-brand), transparent 86%);
            color: var(--roc-brand);
            line-height: 1.35;
        }

        .roc-tab.is-active .roc-badge {
            background: rgba(255, 255, 255, .22);
            color: #fff;
        }

        /* ---- Panes ---- */
        .roc-body {
            padding: 1.05rem 1.15rem 1.15rem;
        }

        .roc-pane-head h6 {
            font-size: .95rem;
            font-weight: 700;
            margin: 0;
        }

        .roc-pane-head p {
            margin: .12rem 0 0;
            font-size: .8rem;
            color: var(--bs-secondary-color);
            max-width: 70ch;
        }

        .roc-pane-head {
            margin-bottom: .9rem;
        }

        .roc-panel {
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: var(--roc-radius-sm);
            background: var(--bs-body-bg);
            padding: .85rem .9rem;
        }

        .roc-panel+.roc-panel {
            margin-top: .75rem;
        }

        .roc-panel-t {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
            margin-bottom: .6rem;
        }

        /* ---- Upload card ---- */
        .roc-up {
            display: flex;
            gap: .75rem;
            align-items: center;
            border: 1px dashed var(--bs-border-color);
            border-radius: var(--roc-radius-sm);
            padding: .6rem .7rem;
            background: var(--bs-tertiary-bg);
            transition: all .16s ease;
            height: 100%;
        }

        .roc-up:hover {
            border-color: var(--roc-brand);
            background: color-mix(in srgb, var(--roc-brand), transparent 95%);
        }

        .roc-up-thumb {
            width: 74px;
            height: 52px;
            flex: 0 0 auto;
            border-radius: 8px;
            border: 1px solid var(--bs-border-color);
            background: var(--bs-body-bg);
            display: grid;
            place-items: center;
            overflow: hidden;
            color: var(--bs-secondary-color);
        }

        .roc-up-thumb img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .roc-up-name {
            font-size: .82rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .roc-up-meta {
            font-size: .68rem;
            color: var(--bs-secondary-color);
        }

        .roc-chip-ok {
            font-size: .62rem;
            font-weight: 700;
            color: var(--bs-success);
            background: color-mix(in srgb, var(--bs-success), transparent 88%);
            border-radius: 20px;
            padding: .05rem .4rem;
        }

        .roc-chip-none {
            font-size: .62rem;
            font-weight: 700;
            color: var(--bs-secondary-color);
            background: var(--bs-secondary-bg);
            border-radius: 20px;
            padding: .05rem .4rem;
        }

        /* ---- Bond paper mini page preview ---- */
        .roc-page {
            width: 118px;
            aspect-ratio: 1 / 1.414;
            border: 1px solid var(--bs-border-color);
            border-radius: 6px;
            background: var(--bs-body-bg);
            display: flex;
            flex-direction: column;
            padding: 6px;
            gap: 5px;
            box-shadow: 0 6px 18px -12px rgba(var(--bs-emphasis-color-rgb), .5);
        }

        .roc-page .zone {
            border-radius: 3px;
            background: repeating-linear-gradient(45deg, color-mix(in srgb, var(--roc-brand), transparent 88%) 0 4px, transparent 4px 8px);
            border: 1px dashed var(--roc-line);
            font-size: .5rem;
            color: var(--roc-brand);
            display: grid;
            place-items: center;
            font-weight: 700;
        }

        .roc-page .lines {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 2px 0;
        }

        .roc-page .lines span {
            height: 3px;
            border-radius: 2px;
            background: var(--bs-secondary-bg);
        }

        .roc-page .lines span:nth-child(3n) {
            width: 70%;
        }

        /* ---- Gallery ---- */
        .roc-gal {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .roc-gal figure {
            position: relative;
            width: 92px;
            height: 66px;
            margin: 0;
            border-radius: 8px;
            border: 1px solid var(--bs-border-color);
            background: var(--bs-tertiary-bg);
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .roc-gal figure img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .roc-gal figure figcaption {
            position: absolute;
            left: 4px;
            top: 4px;
            font-size: .58rem;
            font-weight: 700;
            background: rgba(0, 0, 0, .55);
            color: #fff;
            border-radius: 20px;
            padding: 0 .3rem;
        }

        /* ---- Sticky footer ---- */
        .roc-foot {
            position: sticky;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            padding: .7rem 1.15rem;
            border-top: 1px solid var(--bs-border-color-translucent);
            background: color-mix(in srgb, var(--bs-body-bg), transparent 8%);
            backdrop-filter: blur(8px);
        }

        .roc-foot .hint {
            font-size: .74rem;
            color: var(--bs-secondary-color);
        }

        .roc-save {
            border-radius: 999px;
            padding: .42rem 1.15rem;
            font-size: .82rem;
            font-weight: 600;
            box-shadow: var(--roc-shadow);
        }
    </style>

    <form wire:submit="save">
        <div class="roc-shell">
            {{-- Header --}}
            <div class="roc-head d-flex align-items-center gap-3">
                <span class="roc-head-ic"><i class="fa fa-key"></i></span>
                <div class="flex-grow-1" style="min-width:0">
                    <div class="roc-eyebrow">Module settings</div>
                    <h5>Rent Out Configuration</h5>
                    <p>Defaults for bookings, agreement print layout and PDF branding.</p>
                </div>
                <span class="badge rounded-pill text-bg-light border d-none d-md-inline">5 sections</span>
            </div>

            {{-- Tab rail --}}
            <div class="roc-rail-wrap">
                <div class="roc-rail">
                    <button type="button" class="roc-tab" :class="{ 'is-active': tab === 'docs' }" x-on:click="tab = 'docs'">
                        <i class="fa fa-check-square-o"></i>Mandatory Documents
                        @if ($mandatoryCount)
                            <span class="roc-badge">{{ $mandatoryCount }}</span>
                        @endif
                    </button>
                    <button type="button" class="roc-tab" :class="{ 'is-active': tab === 'print' }" x-on:click="tab = 'print'">
                        <i class="fa fa-print"></i>Print Layout
                    </button>
                    <button type="button" class="roc-tab" :class="{ 'is-active': tab === 'logos' }" x-on:click="tab = 'logos'">
                        <i class="fa fa-picture-o"></i>Agreement Logos
                        <span class="roc-badge">{{ $logosSet }}/{{ $logosTotal }}</span>
                    </button>
                    <button type="button" class="roc-tab" :class="{ 'is-active': tab === 'images' }" x-on:click="tab = 'images'">
                        <i class="fa fa-clone"></i>Agreement Images
                        @if ($agreementImageCount)
                            <span class="roc-badge">{{ $agreementImageCount }}</span>
                        @endif
                    </button>
                    <button type="button" class="roc-tab" :class="{ 'is-active': tab === 'lpo' }" x-on:click="tab = 'lpo'">
                        <i class="fa fa-file-image-o"></i>LPO Header
                    </button>
                </div>
            </div>

            <div class="roc-body">
                {{-- ============ MANDATORY DOCUMENTS ============ --}}
                <section x-show="tab === 'docs'" x-cloak>
                    <div class="roc-pane-head">
                        <h6>Mandatory Documents</h6>
                        <p>
                            Document types selected here become the default required checklist on every new rent-out / lease booking.
                            Each booking can still fine-tune its own list from the Documents tab.
                        </p>
                    </div>

                    @if ($documentTypes->isEmpty())
                        <div class="alert alert-warning py-2 px-3 small mb-0">
                            <i class="fa fa-exclamation-triangle me-1"></i>
                            No document types created yet.
                            <a href="{{ route('settings::document_type::index') }}" class="alert-link">Add document types</a>
                            first, then mark the ones required for bookings.
                        </div>
                    @else
                        <div class="roc-panel">
                            <div class="roc-panel-t">Required on every booking</div>
                            <div wire:ignore>
                                <label class="form-label fw-medium small mb-1" for="mandatory_document_types">Select Document Types</label>
                                {{ html()->select('mandatory_document_types', $documentTypes)->value($mandatory_document_types)->class('select-document_type_id-list')->id('mandatory_document_types')->multiple()->placeholder('Select Document Types')->attribute('wire:model', 'mandatory_document_types') }}
                            </div>
                            <div class="form-text mt-2">
                                <i class="fa fa-info-circle me-1"></i>Bookings missing these documents show as incomplete on their Documents tab.
                            </div>
                        </div>
                    @endif
                </section>

                {{-- ============ PRINT LAYOUT ============ --}}
                <section x-show="tab === 'print'" x-cloak>
                    <div class="roc-pane-head">
                        <h6>Print Layout &middot; Bond Paper / Letterhead</h6>
                        <p>
                            When enabled, logos and footer images are hidden during PDF generation but their space is preserved (blank area).
                            Use this when printing on pre-printed bond paper / letterhead stationery.
                        </p>
                    </div>

                    <div class="roc-panel">
                        <div class="row g-3 align-items-start">
                            <div class="col-lg-8">
                                <div class="roc-panel-t">Mode</div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium small mb-1">Bond Paper Mode</label>
                                        <select wire:model="reservation_bond_paper_mode" class="form-select form-select-sm">
                                            <option value="no">Disabled (Show Logos)</option>
                                            <option value="yes">Enabled (Hide Logos, Reserve Space)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium small mb-1">Header Logo Height (px)</label>
                                        <input type="number" wire:model="reservation_logo_height" class="form-control form-control-sm" min="0"
                                            placeholder="80">
                                        <small class="form-text text-muted">Reserved blank space for the header area.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium small mb-1">Footer Reserved Height (px)</label>
                                        <input type="number" wire:model="reservation_footer_height" class="form-control form-control-sm" min="0"
                                            placeholder="30">
                                        <small class="form-text text-muted">Blank space for footer image / signature area.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="roc-panel-t">Page preview</div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="roc-page">
                                        <div class="zone" style="height:26px">HEADER {{ $reservation_logo_height ?: 80 }}px</div>
                                        <div class="lines">
                                            @for ($i = 0; $i < 8; $i++)
                                                <span></span>
                                            @endfor
                                        </div>
                                        <div class="zone" style="height:12px">FOOTER {{ $reservation_footer_height ?: 30 }}px</div>
                                    </div>
                                    <p class="small text-secondary mb-0">Hatched areas are left blank so your pre-printed stationery shows through.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ============ AGREEMENT LOGOS ============ --}}
                <section x-show="tab === 'logos'" x-cloak>
                    <div class="roc-pane-head">
                        <h6>Reservation / Agreement Logos</h6>
                        <p>Logos printed on the reservation and lease agreement PDFs. Max 2&nbsp;MB each, max 800&times;400&nbsp;px. JPG, PNG or GIF.</p>
                    </div>

                    <div class="roc-panel">
                        <div class="roc-panel-t">Reservation forms</div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="roc-up">
                                    <div class="roc-up-thumb">
                                        @if ($existing_rental_reservation_logo)
                                            <img src="{{ asset('storage/' . $existing_rental_reservation_logo) }}" alt="Rental reservation logo">
                                        @else
                                            <i class="fa fa-picture-o"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1" style="min-width:0">
                                        <div class="roc-up-name">
                                            Rental Reservation Logo
                                            <span class="{{ $existing_rental_reservation_logo ? 'roc-chip-ok' : 'roc-chip-none' }} ms-1">
                                                {{ $existing_rental_reservation_logo ? 'Set' : 'Not set' }}
                                            </span>
                                        </div>
                                        <div class="roc-up-meta mb-1">Max 2 MB &middot; replaces on save</div>
                                        <input type="file" wire:model="rental_reservation_logo_file" class="form-control form-control-sm" accept="image/*">
                                        @error('rental_reservation_logo_file')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="roc-up">
                                    <div class="roc-up-thumb">
                                        @if ($existing_lease_reservation_logo)
                                            <img src="{{ asset('storage/' . $existing_lease_reservation_logo) }}" alt="Lease reservation logo">
                                        @else
                                            <i class="fa fa-picture-o"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1" style="min-width:0">
                                        <div class="roc-up-name">
                                            Lease Reservation Logo
                                            <span class="{{ $existing_lease_reservation_logo ? 'roc-chip-ok' : 'roc-chip-none' }} ms-1">
                                                {{ $existing_lease_reservation_logo ? 'Set' : 'Not set' }}
                                            </span>
                                        </div>
                                        <div class="roc-up-meta mb-1">Max 2 MB &middot; replaces on save</div>
                                        <input type="file" wire:model="lease_reservation_logo_file" class="form-control form-control-sm" accept="image/*">
                                        @error('lease_reservation_logo_file')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="roc-panel">
                        <div class="roc-panel-t">Residential lease</div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="roc-up">
                                    <div class="roc-up-thumb">
                                        @if ($existing_lease_residential_logo)
                                            <img src="{{ asset('storage/' . $existing_lease_residential_logo) }}" alt="Lease residential logo">
                                        @else
                                            <i class="fa fa-picture-o"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1" style="min-width:0">
                                        <div class="roc-up-name">
                                            Lease Residential Logo
                                            <span class="{{ $existing_lease_residential_logo ? 'roc-chip-ok' : 'roc-chip-none' }} ms-1">
                                                {{ $existing_lease_residential_logo ? 'Set' : 'Not set' }}
                                            </span>
                                        </div>
                                        <div class="roc-up-meta mb-1">Max 2 MB &middot; replaces on save</div>
                                        <input type="file" wire:model="lease_residential_logo_file" class="form-control form-control-sm" accept="image/*">
                                        @error('lease_residential_logo_file')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="roc-up">
                                    <div class="roc-up-thumb">
                                        @if ($existing_rental_residential_logo)
                                            <img src="{{ asset('storage/' . $existing_rental_residential_logo) }}" alt="Rental residential logo">
                                        @else
                                            <i class="fa fa-picture-o"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1" style="min-width:0">
                                        <div class="roc-up-name">
                                            Rental Residential Logo
                                            <span class="{{ $existing_rental_residential_logo ? 'roc-chip-ok' : 'roc-chip-none' }} ms-1">
                                                {{ $existing_rental_residential_logo ? 'Set' : 'Not set' }}
                                            </span>
                                        </div>
                                        <div class="roc-up-meta mb-1">Max 2 MB &middot; replaces on save</div>
                                        <input type="file" wire:model="rental_residential_logo_file" class="form-control form-control-sm" accept="image/*">
                                        @error('rental_residential_logo_file')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="roc-up">
                                    <div class="roc-up-thumb">
                                        @if ($existing_rent_out_agreement_footer)
                                            <img src="{{ asset('storage/' . $existing_rent_out_agreement_footer) }}" alt="Agreement footer">
                                        @else
                                            <i class="fa fa-minus-square-o"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1" style="min-width:0">
                                        <div class="roc-up-name">
                                            Rentout Agreement Footer Image
                                            <span class="{{ $existing_rent_out_agreement_footer ? 'roc-chip-ok' : 'roc-chip-none' }} ms-1">
                                                {{ $existing_rent_out_agreement_footer ? 'Set' : 'Not set' }}
                                            </span>
                                        </div>
                                        <div class="roc-up-meta mb-1">Bottom band of the agreement PDF</div>
                                        <input type="file" wire:model="rent_out_agreement_footer_file" class="form-control form-control-sm" accept="image/*">
                                        @error('rent_out_agreement_footer_file')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ============ AGREEMENT IMAGES ============ --}}
                <section x-show="tab === 'images'" x-cloak>
                    <div class="roc-pane-head">
                        <h6>Agreement Images <span class="text-secondary fw-normal">&middot; Rental Residential Lease</span></h6>
                        <p>Extra pages appended at the end of the rental residential lease PDF — terms, annexures, stamps. Order follows upload order.</p>
                    </div>

                    <div class="roc-panel">
                        <div class="roc-panel-t">Current images ({{ $agreementImageCount }})</div>

                        @if ($agreementImageCount > 0)
                            <div class="roc-gal mb-3">
                                @foreach ($existing_rent_out_agreement_images as $index => $img)
                                    <figure>
                                        <figcaption>{{ $index + 1 }}</figcaption>
                                        <img src="{{ asset('storage/' . $img) }}" alt="Agreement image {{ $index + 1 }}">
                                    </figure>
                                @endforeach
                            </div>
                        @else
                            <p class="small text-secondary mb-3"><i class="fa fa-info-circle me-1"></i>No agreement images uploaded yet.</p>
                        @endif

                        <label class="form-label fw-medium small mb-1">Upload replacement set</label>
                        <input type="file" wire:model="rent_out_agreement_images_files" class="form-control form-control-sm" accept="image/*" multiple>
                        <small class="form-text text-muted">Max 2 MB each. A new upload replaces the whole set.</small>
                        @error('rent_out_agreement_images_files.*')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror

                        <div class="form-check mt-2">
                            <input type="checkbox" wire:model="clear_agreement_images" class="form-check-input" id="clearAgreementImages">
                            <label class="form-check-label small" for="clearAgreementImages">Clear existing agreement images on save</label>
                        </div>
                    </div>
                </section>

                {{-- ============ LPO HEADER ============ --}}
                <section x-show="tab === 'lpo'" x-cloak>
                    <div class="roc-pane-head">
                        <h6>LPO Header Image</h6>
                        <p>Header artwork for the Local Purchase Order (LPO) PDF. Replaces the default logo. Max 2&nbsp;MB. JPG or PNG.</p>
                    </div>

                    <div class="roc-panel">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="roc-up">
                                    <div class="roc-up-thumb" style="width:104px;height:60px">
                                        @if ($existing_lpo_header_image)
                                            <img src="{{ asset('storage/' . $existing_lpo_header_image) }}" alt="LPO header image">
                                        @else
                                            <i class="fa fa-file-image-o"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1" style="min-width:0">
                                        <div class="roc-up-name">
                                            LPO Header Image
                                            <span class="{{ $existing_lpo_header_image ? 'roc-chip-ok' : 'roc-chip-none' }} ms-1">
                                                {{ $existing_lpo_header_image ? 'Set' : 'Not set' }}
                                            </span>
                                        </div>
                                        <div class="roc-up-meta mb-1">Replaces existing image on save</div>
                                        <input type="file" wire:model="lpo_header_image_file" class="form-control form-control-sm" accept="image/*">
                                        @error('lpo_header_image_file')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Footer --}}
            <div class="roc-foot">
                <span class="hint"><i class="fa fa-info-circle me-1"></i>One Save applies changes across all sections.</span>
                <button type="submit" class="btn btn-primary roc-save" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i>Save Changes</span>
                    <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i>Saving...</span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
    <x-select.documentTypeSelect />
    <script>
        $(document).ready(function() {
            $('#mandatory_document_types').on('change', function() {
                @this.set('mandatory_document_types', $(this).val() || []);
            });
        });
    </script>
@endpush
