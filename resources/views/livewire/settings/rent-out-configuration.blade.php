@php
    $uploadSlots = [
        'rental_reservation_logo_file' => ['existing' => $existing_rental_reservation_logo, 'label' => 'Rental Reservation', 'meta' => 'Rental reservation PDF', 'group' => 'reservation', 'kind' => 'logo'],
        'lease_reservation_logo_file' => ['existing' => $existing_lease_reservation_logo, 'label' => 'Sale Reservation', 'meta' => 'Sale reservation PDF', 'group' => 'reservation', 'kind' => 'logo'],
        'rental_residential_logo_file' => ['existing' => $existing_rental_residential_logo, 'label' => 'Rental Residential', 'meta' => 'Rental residential lease PDF', 'group' => 'residential', 'kind' => 'logo'],
        'lease_residential_logo_file' => ['existing' => $existing_lease_residential_logo, 'label' => 'Sale Residential', 'meta' => 'Sale residential lease PDF', 'group' => 'residential', 'kind' => 'logo'],
        'rent_out_agreement_footer_file' => ['existing' => $existing_rent_out_agreement_footer, 'label' => 'Agreement Footer', 'meta' => 'Bottom band of the agreement PDF', 'group' => 'residential', 'kind' => 'footer'],
        'lpo_header_image_file' => ['existing' => $existing_lpo_header_image, 'label' => 'LPO Header', 'meta' => 'Full-width header of the LPO PDF', 'group' => 'lpo', 'kind' => 'logo'],
    ];
    $logoSlots = collect($uploadSlots)->filter(fn ($slot) => $slot['group'] !== 'lpo');
    $logosSet = $logoSlots->filter(fn ($slot) => $slot['existing'])->count();
    $logosTotal = $logoSlots->count();
    $mandatoryCount = count($mandatory_document_types);
    $serviceHeadCount = count($service_categories);
    $agreementImageCount = count($existing_rent_out_agreement_images);
    $bondPaperOn = $reservation_bond_paper_mode === 'yes';
    $lessorSet = collect($lessorFields)->keys()->filter(fn ($key) => filled($print_settings[$key] ?? null))->count();
    $agreementPrimary = $print_settings[\App\Support\RentOutPrintSettings::PRIMARY_COLOR_KEY] ?? '';
    $agreementSecondary = $print_settings[\App\Support\RentOutPrintSettings::SECONDARY_COLOR_KEY] ?? '';
    $customColors = filled($agreementPrimary) || filled($agreementSecondary);

    $sections = [
        'docs' => [
            'icon' => 'fa-check-square-o',
            'title' => 'Mandatory Documents',
            'status' => $mandatoryCount ? $mandatoryCount . ' required' : 'None required',
            'ok' => $mandatoryCount > 0,
            'sub' => 'Document types selected here become the default required checklist on every new rent-out / lease booking. Each booking can still fine-tune its own list from the Documents tab.',
            'fields' => ['mandatory_document_types'],
        ],
        'services' => [
            'icon' => 'fa-cogs',
            'title' => 'Service Heads',
            'status' => $serviceHeadCount ? $serviceHeadCount . ' mapped' : 'Not mapped',
            'ok' => $serviceHeadCount > 0,
            'sub' => 'Income accounts offered as the Category when adding a service on a rent-out. Only the heads mapped here appear in that dropdown, and the service charge is credited to the head picked.',
            'fields' => ['service_categories'],
        ],
        'checklist' => [
            'icon' => 'fa-list-alt',
            'title' => 'Checklist Notes',
            'status' => 'Rental · Lease',
            'ok' => true,
            'sub' => 'Heading and declaration printed above the signature block of the Unit Handover & Snagging checklist — the Move-In block on a rental, the Handover block on a sale.',
            'fields' => ['checklist_notes.*'],
        ],
        'wording' => [
            'icon' => 'fa-file-text-o',
            'title' => 'Agreement Text',
            'status' => 'Titles · Clauses',
            'ok' => true,
            'sub' => 'Company name, titles, project names and contract clauses printed on the reservation form and tenancy agreement PDFs. Leave a field blank to print the default shown in grey.',
            'fields' => collect($printTextDefaults)->keys()->map(fn ($key) => 'print_settings.' . $key)->all(),
        ],
        'lessor' => [
            'icon' => 'fa-building-o',
            'title' => 'Lessor Details',
            'status' => $lessorSet . ' of ' . count($lessorFields) . ' set',
            'ok' => $lessorSet > 0,
            'sub' => 'The landlord company printed as the First Party - Lessor on the tenancy agreement PDF.',
            'fields' => collect($lessorFields)->keys()->map(fn ($key) => 'print_settings.' . $key)->all(),
        ],
        'colors' => [
            'icon' => 'fa-paint-brush',
            'title' => 'Agreement Colors',
            'status' => $customColors ? 'Custom' : 'Default blue',
            'ok' => true,
            'sub' => 'Replaces the blue used for section headers, table headers and shaded rows on the reservation form and tenancy agreement PDFs.',
            'fields' => ['print_settings.' . \App\Support\RentOutPrintSettings::PRIMARY_COLOR_KEY, 'print_settings.' . \App\Support\RentOutPrintSettings::SECONDARY_COLOR_KEY],
        ],
        'print' => [
            'icon' => 'fa-print',
            'title' => 'Print Layout',
            'status' => $bondPaperOn ? 'Bond paper on' : 'Logos shown',
            'ok' => true,
            'sub' => 'Printing on pre-printed letterhead? Turn on bond paper mode — logos and the footer image are hidden but their space is kept blank.',
            'fields' => ['reservation_logo_height', 'reservation_footer_height'],
        ],
        'logos' => [
            'icon' => 'fa-picture-o',
            'title' => 'Agreement Logos',
            'status' => $logosSet . ' of ' . $logosTotal . ' set',
            'ok' => $logosSet === $logosTotal,
            'sub' => 'Printed on the reservation and agreement PDFs. JPG, PNG or GIF · max 2 MB · up to 800×400 px.',
            'fields' => $logoSlots->keys()->all(),
        ],
        'images' => [
            'icon' => 'fa-files-o',
            'title' => 'Annex Pages',
            'status' => $agreementImageCount ? $agreementImageCount . ' ' . str('page')->plural($agreementImageCount) : 'None',
            'ok' => true,
            'sub' => 'Extra pages appended to the end of the rental residential lease PDF — terms, annexures, stamps. Order follows upload order.',
            'fields' => ['rent_out_agreement_images_files.*'],
        ],
        'lpo' => [
            'icon' => 'fa-file-image-o',
            'title' => 'LPO Header',
            'status' => $existing_lpo_header_image ? 'Set' : 'Not set',
            'ok' => (bool) $existing_lpo_header_image,
            'sub' => 'Header artwork for the Local Purchase Order PDF. Replaces the default logo. JPG or PNG · max 2 MB.',
            'fields' => ['lpo_header_image_file'],
        ],
    ];

    foreach ($sections as $key => $section) {
        $sections[$key]['hasError'] = collect($section['fields'])->contains(fn ($field) => $errors->has($field));
    }

    $sectionsDone = collect($sections)->filter(fn ($section) => $section['ok'] && ! $section['hasError'])->count();
    $sectionsTotal = count($sections);
    $sectionsPercent = (int) round($sectionsDone / $sectionsTotal * 100);
@endphp


<div class="rtx" x-data="{ tab: 'docs' }">
    @include('livewire.settings.partials.rent-out-styles')

    <form wire:submit="save">
        <div class="card shadow-none border rounded-4">
            {{-- ============ SECTION TABS ============ --}}
            <div class="rtx-head">
                <div class="rtx-rail" role="tablist" aria-label="Rent out settings sections">
                    @foreach ($sections as $key => $section)
                        <button type="button" class="rtx-tab" role="tab" :class="{ 'active': tab === '{{ $key }}' }" :aria-selected="tab === '{{ $key }}'"
                            x-on:click="tab = '{{ $key }}'">
                            <span class="rtx-tab-ic"><i class="fa fa-fw {{ $section['icon'] }}"></i></span>
                            <span>{{ $section['title'] }}</span>
                            @if ($section['hasError'])
                                <span class="rtx-dot is-danger" title="Needs attention"></span>
                            @elseif (! $section['ok'])
                                <span class="rtx-dot is-warning" title="{{ $section['status'] }}"></span>
                            @endif
                        </button>
                    @endforeach
                </div>
                <div class="rtx-progress" role="progressbar" aria-label="Setup progress" aria-valuenow="{{ $sectionsPercent }}" aria-valuemin="0" aria-valuemax="100">
                    <span class="rtx-ring" style="--p: {{ $sectionsPercent }}"><b>{{ $sectionsPercent }}%</b></span>
                    <span>
                        <strong>Setup {{ $sectionsDone }} / {{ $sectionsTotal }}</strong>
                        sections configured
                    </span>
                </div>
            </div>

            <div class="p-3 p-lg-4">
                {{-- ============ MANDATORY DOCUMENTS ============ --}}
                <section x-show="tab === 'docs'" x-cloak>
                    @include('livewire.settings.partials.rent-out-pane-head', ['section' => $sections['docs']])

                    @if ($documentTypes->isEmpty())
                        <div class="alert alert-warning d-flex align-items-start gap-2 small mb-0">
                            <i class="fa fa-exclamation-triangle mt-1"></i>
                            <div>
                                No document types created yet.
                                <a href="{{ route('settings::document_type::index') }}" class="alert-link">Add document types</a>
                                first, then mark the ones required for bookings.
                            </div>
                        </div>
                    @else
                        <div class="card shadow-none border rounded-3 p-3">
                            <div wire:ignore>
                                <label class="form-label fw-semibold small" for="mandatory_document_types">Required on every booking</label>
                                {{ html()->select('mandatory_document_types', $documentTypes)->value($mandatory_document_types)->class('select-document_type_id-list')->id('mandatory_document_types')->multiple()->placeholder('Search document types…')->attribute('wire:model', 'mandatory_document_types') }}
                            </div>
                            <div class="form-text mb-0">
                                <i class="fa fa-info-circle me-1"></i>Bookings missing these documents show as incomplete on their Documents tab.
                                <a href="{{ route('settings::document_type::index') }}" class="link-primary">Manage document types</a>
                            </div>
                        </div>
                    @endif
                </section>

                {{-- ============ SERVICE HEADS ============ --}}
                <section x-show="tab === 'services'" x-cloak>
                    @include('livewire.settings.partials.rent-out-pane-head', ['section' => $sections['services']])

                    @if ($serviceHeadOptions->isEmpty())
                        <div class="alert alert-warning d-flex align-items-start gap-2 small mb-0">
                            <i class="fa fa-exclamation-triangle mt-1"></i>
                            <div>
                                No income accounts yet.
                                <a href="{{ route('account::index') }}" class="alert-link">Add income accounts</a>
                                for your services first, then map them here.
                            </div>
                        </div>
                    @else
                        <div class="card shadow-none border rounded-3 p-3">
                            <div wire:ignore>
                                <label class="form-label fw-semibold small" for="service_categories">Service categories on the Services tab</label>
                                {{ html()->select('service_categories', $serviceHeadOptions)->value($service_categories)->id('service_categories')->multiple()->placeholder('Search income accounts…')->attribute('wire:model', 'service_categories') }}
                            </div>
                            <div class="form-text mb-0">
                                <i class="fa fa-info-circle me-1"></i>Leave empty to keep offering every account.
                                <a href="{{ route('account::index') }}" class="link-primary">Manage accounts</a>
                            </div>
                        </div>
                    @endif
                </section>

                {{-- ============ CHECKLIST NOTES ============ --}}
                <section x-show="tab === 'checklist'" x-cloak>
                    @include('livewire.settings.partials.rent-out-pane-head', ['section' => $sections['checklist']])

                    @php
                        $checklistGroups = [
                            'rental' => ['label' => 'Rental agreements', 'icon' => 'fa-refresh', 'phase' => 'Move-In block'],
                            'lease' => ['label' => 'Sale agreements', 'icon' => 'fa-home', 'phase' => 'Handover block'],
                        ];
                    @endphp

                    <div class="vstack gap-3">
                        @foreach ($checklistGroups as $typeKey => $group)
                            @php $declModel = "checklist_notes.{$typeKey}.declaration"; @endphp
                            <div class="card shadow-none border rounded-3" x-data="{ txt: @js($checklist_notes[$typeKey]['declaration'] ?? '') }"
                                x-on:rich-text-input="if ($event.detail.model === @js($declModel)) txt = $event.detail.value">
                                <div class="rtx-group-head">
                                    <span class="rtx-group-ic"><i class="fa fa-fw {{ $group['icon'] }}"></i></span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="rtx-group-title">
                                            {{ $group['label'] }}
                                            <span class="rtx-pill is-primary">{{ $group['phase'] }}</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" wire:click="resetChecklistNote('{{ $typeKey }}')">
                                        <i class="fa fa-undo me-1"></i>Reset
                                    </button>
                                </div>
                                <div class="p-3">
                                    <label class="form-label fw-semibold small">Heading</label>
                                    <input type="text" class="form-control form-control-sm" wire:model="checklist_notes.{{ $typeKey }}.title"
                                        placeholder="{{ $checklistDefaults[$typeKey]['title'] }}">

                                    <x-rich-text-editor class="mt-3" wire:model="{{ $declModel }}" label="Declaration" :tokens="$checklistTokens"
                                        :height="$typeKey === 'lease' ? 320 : 200" placeholder="I, {tenant_name}, hereby confirm ..." />

                                    <div class="border-start border-4 border-primary rounded-end bg-primary-subtle px-3 py-2 mt-3">
                                        <div class="text-uppercase text-primary-emphasis small fw-bold mb-1">Prints as</div>
                                        <div class="small text-break" x-html="window.rocChecklistPreview(txt)"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="form-text">
                        <i class="fa fa-info-circle me-1"></i>Clear a field to fall back to the built-in default text.
                        Use <strong>RTL</strong> on a paragraph for Arabic clauses, and <strong>HTML</strong> to edit the markup directly.
                    </div>
                </section>

                {{-- ============ AGREEMENT TEXT ============ --}}
                <section x-show="tab === 'wording'" x-cloak>
                    @include('livewire.settings.partials.rent-out-pane-head', ['section' => $sections['wording']])

                    @php
                        $wordingGroups = [
                            [
                                'title' => 'Company details',
                                'icon' => 'fa-building',
                                'hint' => 'Printed in the reservation form authorisation box and as the agency name. English falls back to the company profile name.',
                                'rows' => [['company_name_english', 'company_name_arabic', 'Company name', false]],
                            ],
                            [
                                'title' => 'Tenancy agreement header',
                                'icon' => 'fa-header',
                                'hint' => "Title and project name at the top of the rental tenancy agreement. The project name prints only when the property's building has no Group / Project.",
                                'rows' => [
                                    ['tenancy_agreement_title_english', 'tenancy_agreement_title_arabic', 'Agreement title', false],
                                    ['extended_tenancy_agreement_title_english', 'extended_tenancy_agreement_title_arabic', 'Extended agreement title', false],
                                    ['tenancy_project_name_english', 'tenancy_project_name_arabic', 'Project / tower name', false],
                                ],
                            ],
                            [
                                'title' => 'Tenancy agreement contract clauses',
                                'icon' => 'fa-gavel',
                                'hint' => 'Printed below the contract details. Use {date} where the contract start date should appear.',
                                'rows' => [
                                    ['tenancy_contract_made_on_english', 'tenancy_contract_made_on_arabic', 'Contract made on clause', true],
                                    ['tenancy_contract_terms_english', 'tenancy_contract_terms_arabic', 'Terms & conditions clause', true],
                                ],
                            ],
                            [
                                'title' => 'Reservation form header',
                                'icon' => 'fa-bookmark-o',
                                'hint' => "Title and project name at the top of the reservation form. The project name prints only when the property's building has no Group / Project.",
                                'rows' => [
                                    ['reservation_form_title_english', 'reservation_form_title_arabic', 'Reservation form title', false],
                                    ['reservation_project_name_english', 'reservation_project_name_arabic', 'Project / complex name', false],
                                ],
                            ],
                        ];
                    @endphp

                    <div class="vstack gap-3">
                        @foreach ($wordingGroups as $group)
                            <div class="card shadow-none border rounded-3">
                                <div class="rtx-group-head">
                                    <span class="rtx-group-ic"><i class="fa fa-fw {{ $group['icon'] }}"></i></span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="rtx-group-title">{{ $group['title'] }}</div>
                                        <div class="rtx-group-sub">{{ $group['hint'] }}</div>
                                    </div>
                                </div>
                                <div class="p-3">
                                    <div class="row g-3">
                                        @foreach ($group['rows'] as [$englishKey, $arabicKey, $label, $multiline])
                                            @foreach ([$englishKey => 'English', $arabicKey => 'Arabic'] as $key => $language)
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label fw-semibold small" for="print_{{ $key }}">{{ $label }} ({{ $language }})</label>
                                                    @if ($multiline)
                                                        <textarea id="print_{{ $key }}" rows="3" class="form-control form-control-sm" wire:model="print_settings.{{ $key }}"
                                                            placeholder="{{ $printTextDefaults[$key] }}" @if ($language === 'Arabic') dir="rtl" @endif></textarea>
                                                    @else
                                                        <input type="text" id="print_{{ $key }}" class="form-control form-control-sm" wire:model="print_settings.{{ $key }}"
                                                            placeholder="{{ $printTextDefaults[$key] }}" @if ($language === 'Arabic') dir="rtl" @endif>
                                                    @endif
                                                    @error("print_settings.{$key}")
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- ============ LESSOR DETAILS ============ --}}
                <section x-show="tab === 'lessor'" x-cloak>
                    @include('livewire.settings.partials.rent-out-pane-head', ['section' => $sections['lessor']])

                    <div class="card shadow-none border rounded-3 p-3">
                        <div class="row g-3">
                            @foreach ($lessorFields as $key => $label)
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold small" for="print_{{ $key }}">{{ $label }}</label>
                                    <input type="text" @if ($key === 'lessor_email') inputmode="email" @endif id="print_{{ $key }}" class="form-control form-control-sm"
                                        wire:model="print_settings.{{ $key }}" @if (str_ends_with($key, '_ar') || str_ends_with($key, '_arabic')) dir="rtl" @endif>
                                    @error("print_settings.{$key}")
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- ============ AGREEMENT COLORS ============ --}}
                <section x-show="tab === 'colors'" x-cloak>
                    @include('livewire.settings.partials.rent-out-pane-head', ['section' => $sections['colors']])

                    @php
                        $primaryKey = \App\Support\RentOutPrintSettings::PRIMARY_COLOR_KEY;
                        $secondaryKey = \App\Support\RentOutPrintSettings::SECONDARY_COLOR_KEY;
                        $primaryDefault = \App\Support\RentOutPrintSettings::DEFAULT_PRIMARY_COLOR;
                        $secondaryDefault = \App\Support\RentOutPrintSettings::DEFAULT_SECONDARY_COLOR;
                    @endphp

                    <div class="row g-4" x-data="{
                        primary: () => /^#[0-9A-Fa-f]{6}$/.test($wire.print_settings['{{ $primaryKey }}'] || '') ? $wire.print_settings['{{ $primaryKey }}'] : '{{ $primaryDefault }}',
                        secondary: () => /^#[0-9A-Fa-f]{6}$/.test($wire.print_settings['{{ $secondaryKey }}'] || '') ? $wire.print_settings['{{ $secondaryKey }}'] : '{{ $secondaryDefault }}',
                        ink(hex) {
                            const lum = (0.299 * parseInt(hex.substr(1, 2), 16) + 0.587 * parseInt(hex.substr(3, 2), 16) + 0.114 * parseInt(hex.substr(5, 2), 16)) / 255;
                            return lum > 0.6 ? '#333333' : '#ffffff';
                        },
                    }">
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold small">Presets</label>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach ($colorPresets as $index => $preset)
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-2"
                                        wire:click="applyColorPreset({{ $index }})">
                                        <span class="rounded-circle border" style="width: 14px; height: 14px; background: {{ $preset['primary'] }}"></span>
                                        {{ $preset['name'] }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="row g-3">
                                @foreach ([$primaryKey => ['Primary color', $primaryDefault, 'Section headers, table headers and the document title band.'], $secondaryKey => ['Row shade color', $secondaryDefault, 'Alternate row background in the tenancy agreement.']] as $key => [$label, $default, $hint])
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fw-semibold small" for="print_{{ $key }}">{{ $label }}</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text p-1">
                                                <input type="color" class="form-control form-control-color border-0 p-0" style="width: 28px; height: 24px"
                                                    :value="{{ $key === $primaryKey ? 'primary()' : 'secondary()' }}"
                                                    x-on:input="$wire.set('print_settings.{{ $key }}', $event.target.value, false)" title="Pick {{ strtolower($label) }}">
                                            </span>
                                            <input type="text" id="print_{{ $key }}" class="form-control" wire:model.live.debounce.400ms="print_settings.{{ $key }}"
                                                maxlength="7" placeholder="{{ $default }}" autocomplete="off">
                                        </div>
                                        @error("print_settings.{$key}")
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">{{ $hint }}</div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-link btn-sm px-0 mt-2"
                                x-on:click="$wire.set('print_settings.{{ $primaryKey }}', ''); $wire.set('print_settings.{{ $secondaryKey }}', '')">
                                <i class="fa fa-undo me-1"></i>Reset to default blue
                            </button>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="bg-body-tertiary rounded-4 p-3">
                                <div class="text-uppercase text-body-secondary small fw-bold text-center mb-2">Preview</div>
                                <div class="bg-white border rounded-3 overflow-hidden small">
                                    <div class="fw-bold px-2 py-1" :style="`background: ${primary()}; color: ${ink(primary())}`">Section Header</div>
                                    <div class="px-2 py-1 bg-white text-dark">Row one</div>
                                    <div class="px-2 py-1 text-dark" :style="`background: ${secondary()}`">Row two</div>
                                    <div class="px-2 py-1 bg-white text-dark">Row three</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ============ PRINT LAYOUT ============ --}}
                <section x-show="tab === 'print'" x-cloak>
                    @include('livewire.settings.partials.rent-out-pane-head', ['section' => $sections['print']])

                    <div class="row g-4">
                        <div class="col-12 col-md-8">
                            <label class="d-flex align-items-center justify-content-between gap-3 border rounded-3 px-3 py-2 mb-3" for="reservation_bond_paper_mode"
                                role="button">
                                <span class="d-flex align-items-center gap-2 min-w-0">
                                    <span class="rtx-group-ic"><i class="fa fa-fw fa-file-o"></i></span>
                                    <span class="min-w-0">
                                        <span class="d-block fw-semibold small">Bond paper mode</span>
                                        <span class="d-block small text-body-secondary">Hide logos &amp; footer image during PDF generation, keep their space blank.</span>
                                    </span>
                                </span>
                                <span class="form-check form-switch m-0 flex-shrink-0">
                                    <input type="checkbox" class="form-check-input m-0" role="switch" id="reservation_bond_paper_mode"
                                        :checked="$wire.reservation_bond_paper_mode === 'yes'"
                                        x-on:change="$wire.reservation_bond_paper_mode = $event.target.checked ? 'yes' : 'no'">
                                </span>
                            </label>

                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold small" for="reservation_logo_height">Header reserved height</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-arrows-v"></i></span>
                                        <input type="number" id="reservation_logo_height" wire:model="reservation_logo_height" class="form-control" min="0"
                                            placeholder="80">
                                        <span class="input-group-text">px</span>
                                    </div>
                                    <div class="form-text">Blank space for the header area.</div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold small" for="reservation_footer_height">Footer reserved height</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-arrows-v"></i></span>
                                        <input type="number" id="reservation_footer_height" wire:model="reservation_footer_height" class="form-control" min="0"
                                            placeholder="30">
                                        <span class="input-group-text">px</span>
                                    </div>
                                    <div class="form-text">Blank space for the footer image / signature area.</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="bg-primary-subtle rounded-4 p-3">
                                <div class="text-uppercase text-primary-emphasis small fw-bold text-center mb-2">Page preview</div>
                                <div class="bg-white border rounded-3 shadow-sm p-2 d-flex flex-column gap-2 mx-auto col-8 col-md-12">
                                    <div class="rounded-2 small fw-bold text-center py-3"
                                        :class="$wire.reservation_bond_paper_mode === 'yes' ? 'border border-primary text-primary' : 'bg-primary text-white'"
                                        x-text="($wire.reservation_bond_paper_mode === 'yes' ? 'Blank · ' : 'Logo · ') + ($wire.reservation_logo_height || 80) + 'px'"></div>
                                    <div class="d-flex flex-column gap-1 py-2">
                                        <span class="placeholder placeholder-xs col-12 bg-secondary"></span>
                                        <span class="placeholder placeholder-xs col-10 bg-secondary"></span>
                                        <span class="placeholder placeholder-xs col-12 bg-secondary"></span>
                                        <span class="placeholder placeholder-xs col-8 bg-secondary"></span>
                                        <span class="placeholder placeholder-xs col-12 bg-secondary"></span>
                                        <span class="placeholder placeholder-xs col-11 bg-secondary"></span>
                                        <span class="placeholder placeholder-xs col-6 bg-secondary"></span>
                                    </div>
                                    <div class="rounded-2 small fw-bold text-center py-1"
                                        :class="$wire.reservation_bond_paper_mode === 'yes' ? 'border border-primary text-primary' : 'bg-primary text-white'"
                                        x-text="($wire.reservation_bond_paper_mode === 'yes' ? 'Blank · ' : 'Footer · ') + ($wire.reservation_footer_height || 30) + 'px'"></div>
                                </div>
                                <div class="small text-primary-emphasis text-center mt-2"
                                    x-text="$wire.reservation_bond_paper_mode === 'yes' ? 'Outlined areas stay blank so your stationery shows through.' : 'Logos print in the header and footer bands.'">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ============ AGREEMENT LOGOS ============ --}}
                <section x-show="tab === 'logos'" x-cloak>
                    @include('livewire.settings.partials.rent-out-pane-head', ['section' => $sections['logos']])

                    @foreach (['reservation' => 'Reservation forms', 'residential' => 'Residential lease'] as $groupKey => $groupLabel)
                        @php $groupSlots = $logoSlots->where('group', $groupKey); @endphp
                        <div class="d-flex align-items-center gap-2 {{ $loop->first ? '' : 'mt-4' }} mb-3">
                            <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">{{ $groupLabel }}</h6>
                            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">
                                {{ $groupSlots->filter(fn ($slot) => $slot['existing'])->count() }}/{{ $groupSlots->count() }}
                            </span>
                            <hr class="flex-grow-1 my-0">
                        </div>
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
                            @foreach ($groupSlots as $model => $slot)
                                <div class="col">
                                    @include('livewire.settings.partials.rent-out-upload-tile', ['model' => $model, 'slot' => $slot, 'upload' => $this->{$model}])
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </section>

                {{-- ============ ANNEX PAGES ============ --}}
                <section x-show="tab === 'images'" x-cloak>
                    @include('livewire.settings.partials.rent-out-pane-head', ['section' => $sections['images']])

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <h6 class="text-uppercase text-body-secondary small fw-bold mb-0">Current pages</h6>
                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">{{ $agreementImageCount }}</span>
                        <hr class="flex-grow-1 my-0">
                    </div>
                    @if ($agreementImageCount > 0)
                        <div class="row row-cols-3 row-cols-sm-4 row-cols-lg-6 g-3 mb-4">
                            @foreach ($existing_rent_out_agreement_images as $index => $img)
                                <div class="col">
                                    <div class="position-relative">
                                        <img src="{{ asset('storage/' . $img) }}" class="img-thumbnail rounded-3 shadow-sm w-100" alt="Annex page {{ $index + 1 }}" loading="lazy">
                                        <span class="badge rounded-pill text-bg-dark position-absolute bottom-0 start-0 m-2">{{ $index + 1 }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="small text-body-secondary mb-4"><i class="fa fa-info-circle me-1"></i>No annex pages uploaded yet.</p>
                    @endif

                    @if (!empty($rent_out_agreement_images_files))
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <h6 class="text-uppercase text-primary small fw-bold mb-0">New set · applies on save</h6>
                            <hr class="flex-grow-1 my-0">
                        </div>
                        <div class="row row-cols-3 row-cols-sm-4 row-cols-lg-6 g-3 mb-4">
                            @foreach ($rent_out_agreement_images_files as $index => $file)
                                <div class="col">
                                    <div class="position-relative">
                                        @if (method_exists($file, 'isPreviewable') && $file->isPreviewable())
                                            <img src="{{ $file->temporaryUrl() }}" class="img-thumbnail rounded-3 border-primary w-100" alt="New annex page {{ $index + 1 }}">
                                        @else
                                            <div class="img-thumbnail rounded-3 border-primary text-center py-4"><i class="fa fa-file-image-o text-body-secondary"></i></div>
                                        @endif
                                        <span class="badge rounded-pill text-bg-primary position-absolute bottom-0 start-0 m-2">{{ $index + 1 }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <label class="d-block border border-2 border-primary-subtle bg-primary-subtle rounded-4 text-center p-4" role="button"
                        for="rent_out_agreement_images_files">
                        <span class="d-inline-flex align-items-center justify-content-center bg-white text-primary rounded-circle shadow-sm p-3 lh-1 mb-2">
                            <i class="fa fa-fw fa-cloud-upload fs-4" wire:loading.remove wire:target="rent_out_agreement_images_files"></i>
                            <i class="fa fa-fw fa-spinner fa-spin fs-4" wire:loading wire:target="rent_out_agreement_images_files"></i>
                        </span>
                        <span class="d-block fw-semibold text-primary-emphasis">Upload a replacement set</span>
                        <span class="d-block small text-body-secondary">Choose one or more images · max 2 MB each · replaces all pages</span>
                    </label>
                    <input type="file" id="rent_out_agreement_images_files" class="d-none" wire:model="rent_out_agreement_images_files" accept="image/*" multiple>
                    @error('rent_out_agreement_images_files.*')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    <div class="form-check mt-3">
                        <input type="checkbox" wire:model="clear_agreement_images" class="form-check-input" id="clearAgreementImages">
                        <label class="form-check-label small" for="clearAgreementImages">Remove all existing annex pages on save</label>
                    </div>
                </section>

                {{-- ============ LPO HEADER ============ --}}
                <section x-show="tab === 'lpo'" x-cloak>
                    @include('livewire.settings.partials.rent-out-pane-head', ['section' => $sections['lpo']])

                    <div class="row">
                        <div class="col-12 col-sm-8 col-xl-6">
                            @include('livewire.settings.partials.rent-out-upload-tile', [
                                'model' => 'lpo_header_image_file',
                                'slot' => $uploadSlots['lpo_header_image_file'],
                                'upload' => $lpo_header_image_file,
                            ])
                        </div>
                    </div>
                </section>
            </div>

            {{-- ============ SAVE BAR ============ --}}
            <div class="card flex-row shadow-none border-0 border-top rounded-0 rounded-bottom-4 sticky-bottom z-1 px-3 py-2 flex-wrap align-items-center justify-content-between gap-2">
                <div class="small">
                    <span wire:dirty class="text-warning-emphasis">
                        <i class="fa fa-circle text-warning me-1"></i>Unsaved changes &middot; one save applies every section
                    </span>
                    <span wire:dirty.remove class="text-body-secondary">
                        <i class="fa fa-check-circle text-success me-1"></i>All sections saved
                    </span>
                </div>
                <div class="d-grid col-12 col-sm-auto">
                    <button type="submit" class="btn btn-primary rounded-pill px-4" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save"><i class="fa fa-check me-1"></i>Save changes</span>
                        <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i>Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
    <x-select.documentTypeSelect />
    <script>
        // Fills the checklist-note placeholders with sample data so the settings
        // screen shows the sentence the way the PDF will print it.
        window.rocChecklistPreview = function(text) {
            const sample = {
                '{tenant_name}': 'Owner Unit / Ibrahim Abdalla Ibrahim Ahmed 709m',
                '{property}': '709',
                '{building}': 'Al Muntazah Tower',
                '{group}': 'Marina Project',
                '{type}': 'Apartment',
                '{company}': @js(\App\Models\Configuration::where('key', 'company_name')->value('value') ?: 'Your Company'),
                '{today}': @js(now()->format('d M Y')),
            };

            const filled = Object.entries(sample).reduce((t, [token, value]) => t.split(token).join(value), text || '');

            // Notes saved before the declaration became rich text are plain strings —
            // show them escaped with their line breaks kept, the way they print.
            if (!/<\/?[a-z][^>]*>/i.test(filled)) {
                const holder = document.createElement('div');
                holder.textContent = filled;
                return holder.innerHTML.split('\n').join('<br>');
            }

            return filled;
        };

        $(document).ready(function() {
            $('#mandatory_document_types').on('change', function() {
                @this.set('mandatory_document_types', $(this).val() || []);
            });

            const serviceCategories = document.getElementById('service_categories');
            if (serviceCategories && !serviceCategories.tomselect) {
                new TomSelect(serviceCategories, {
                    plugins: ['remove_button'],
                    persist: false,
                    onChange: (value) => @this.set('service_categories', value || []),
                });
            }
        });
    </script>
@endpush
