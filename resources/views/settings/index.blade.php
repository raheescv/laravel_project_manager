<x-app-layout>
    <style>
        .settings-page {
            --settings-radius: 14px;
            --settings-radius-sm: 10px;
            --settings-accent-soft: rgba(var(--bs-primary-rgb), 0.08);
            --settings-accent-border: rgba(var(--bs-primary-rgb), 0.2);
            --settings-shadow: 0 6px 24px -10px rgba(var(--bs-emphasis-color-rgb), 0.18);
            --settings-shadow-sm: 0 2px 8px -2px rgba(var(--bs-emphasis-color-rgb), 0.1);
            min-width: 0;
        }

        .settings-page .content__wrap {
            padding-block: 1rem;
        }

        /* === HERO HEADER === */
        .settings-hero {
            position: relative;
            border-radius: var(--settings-radius);
            padding: 1.1rem 1.25rem;
            background: var(--bs-primary);
            color: var(--bs-white, #fff);
            overflow: hidden;
            box-shadow: var(--settings-shadow);
            margin-bottom: 1rem;
        }

        .settings-hero::before {
            content: "";
            position: absolute;
            inset: -40% -10% auto auto;
            width: 60%;
            height: 200%;
            background: radial-gradient(ellipse at center, rgba(255, 255, 255, 0.15), transparent 60%);
            pointer-events: none;
        }

        .settings-hero .breadcrumb {
            margin-bottom: 0.35rem;
            font-size: 0.78rem;
        }

        .settings-hero .breadcrumb,
        .settings-hero .breadcrumb a,
        .settings-hero .breadcrumb-item.active,
        .settings-hero .breadcrumb-item + .breadcrumb-item::before {
            color: rgba(255, 255, 255, 0.85) !important;
        }

        .settings-hero .breadcrumb a:hover {
            color: #fff !important;
        }

        .settings-hero h1 {
            font-size: clamp(1.25rem, 2vw + 0.5rem, 1.6rem);
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.01em;
            color: inherit;
        }

        .settings-hero p {
            margin: 0.15rem 0 0;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.85);
            max-width: 56ch;
        }

        /* === SHELL === */
        .settings-shell {
            border: 1px solid var(--bs-border-color);
            border-radius: var(--settings-radius);
            overflow: visible;
            box-shadow: var(--settings-shadow-sm);
            background: var(--bs-body-bg);
        }

        .settings-shell > .row {
            align-items: flex-start;
        }

        .settings-shell,
        .settings-shell .card-body,
        .settings-shell .tab-content,
        .settings-shell .tab-pane {
            min-width: 0;
        }

        /* === TABS COLUMN (sidebar) === */
        .settings-tabs-column {
            min-width: 0;
            background: var(--bs-tertiary-bg);
            padding: 0.75rem !important;
        }

        .settings-tabs-heading {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--bs-secondary-color);
            padding: 0.35rem 0.5rem 0.5rem;
            margin: 0;
        }

        .settings-tabs {
            min-width: 0;
            gap: 2px;
        }

        .settings-tabs .nav-item {
            margin: 0;
        }

        .settings-tabs .nav-link {
            display: flex;
            align-items: center;
            width: 100%;
            min-width: 0;
            gap: 0.55rem;
            white-space: normal;
            padding: 0.45rem 0.65rem;
            border-radius: var(--settings-radius-sm);
            color: var(--bs-body-color);
            font-size: 0.85rem;
            font-weight: 500;
            background: transparent;
            border: 0;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .settings-tabs .nav-link > i,
        .settings-tabs .nav-link > i::before {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            line-height: 1;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            background-color: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            color: var(--bs-secondary-color);
            width: auto;
            height: auto;
            min-width: 1rem;
            transition: color 0.15s ease;
        }

        .settings-tabs .nav-link:hover {
            background: var(--settings-accent-soft);
            color: var(--bs-primary);
        }

        .settings-tabs .nav-link:hover > i {
            color: var(--bs-primary) !important;
        }

        .settings-tabs .nav-link.active {
            background: var(--bs-primary);
            color: var(--bs-white, #fff);
        }

        .settings-tabs .nav-link.active > i {
            color: #fff !important;
        }

        /* === CONTENT COLUMN === */
        .settings-content-column {
            min-width: 0;
        }

        .settings-content {
            padding: 1rem !important;
        }

        .settings-content-column .card,
        .settings-content-column .card-body,
        .settings-content-column form,
        .settings-content-column .row,
        .settings-content-column [class*="col-"] {
            min-width: 0;
        }

        .settings-content-column .card {
            border-radius: var(--settings-radius-sm);
            border-color: var(--bs-border-color);
        }

        .settings-content-column .card-header {
            padding: 0.75rem 1rem;
            background: transparent;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .settings-content-column .card-body {
            padding: 1rem;
        }

        .settings-content-column .card-footer {
            padding: 0.75rem 1rem;
            background: var(--bs-tertiary-bg);
            border-top: 1px solid var(--bs-border-color);
        }

        .settings-content-column input,
        .settings-content-column select,
        .settings-content-column textarea,
        .settings-content-column .form-control,
        .settings-content-column .form-select,
        .settings-content-column .ts-wrapper {
            max-width: 100%;
        }

        .settings-content-column pre {
            max-width: 100%;
            overflow-x: auto;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        /* === THEME TAB CARDS === */
        .theme-action-card {
            border: 1px solid var(--bs-border-color);
            border-radius: var(--settings-radius-sm);
            padding: 1rem;
            height: 100%;
            transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
            background: var(--bs-body-bg);
        }

        .theme-action-card:hover {
            border-color: var(--settings-accent-border);
            transform: translateY(-2px);
            box-shadow: var(--settings-shadow-sm);
        }

        .theme-action-card .icon-pill {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            margin-bottom: 0.6rem;
        }

        .theme-action-card .icon-pill.primary {
            background: rgba(var(--bs-primary-rgb), 0.12);
            color: var(--bs-primary);
        }

        .theme-action-card .icon-pill.success {
            background: rgba(var(--bs-success-rgb), 0.12);
            color: var(--bs-success);
        }

        .theme-action-card .icon-pill.danger {
            background: rgba(var(--bs-danger-rgb), 0.12);
            color: var(--bs-danger);
        }

        .theme-action-card h6 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .theme-action-card p {
            font-size: 0.8rem;
            color: var(--bs-secondary-color);
            margin-bottom: 0.75rem;
        }

        .theme-status-card {
            background: linear-gradient(135deg, var(--settings-accent-soft), transparent);
            border: 1px solid var(--settings-accent-border);
            border-radius: var(--settings-radius-sm);
            padding: 1rem;
            height: 100%;
        }

        .theme-status-card h6 {
            font-weight: 700;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .theme-status-card .status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px dashed var(--bs-border-color);
            font-size: 0.85rem;
        }

        .theme-status-card .status-row:last-child {
            border-bottom: 0;
        }

        .theme-status-card .status-row strong {
            font-weight: 500;
            color: var(--bs-secondary-color);
        }

        /* === SETTINGS OFFCANVAS (theme panel) === */
        .settings-page ._dm-settings-container {
            max-width: 100vw;
        }

        .settings-page ._dm-settings-container__content,
        .settings-page ._dm-settings-container__content [class*="col-"] {
            min-width: 0;
        }

        .settings-page ._dm-colorShcemesMode img {
            max-width: 100%;
            height: auto;
        }

        /* === MOBILE === */
        @media (max-width: 767.98px) {
            .settings-page .content__wrap {
                padding-inline: 0.5rem;
                padding-block: 0.65rem;
            }

            .settings-hero {
                padding: 0.85rem 0.95rem;
                border-radius: 12px;
            }

            .settings-hero h1 {
                font-size: 1.15rem;
            }

            .settings-hero p {
                font-size: 0.78rem;
            }

            .settings-shell {
                border-radius: 12px;
                overflow: hidden;
            }

            .settings-tabs-column {
                border-right: 0 !important;
                border-bottom: 1px solid var(--bs-border-color);
                padding: 0.5rem !important;
                position: sticky;
                top: 0;
                z-index: 5;
                background: var(--bs-tertiary-bg);
            }

            .settings-tabs-heading {
                display: none;
            }

            .settings-tabs {
                flex-wrap: nowrap !important;
                overflow-x: auto;
                overflow-y: hidden;
                padding-bottom: 0.15rem;
                scroll-snap-type: x proximity;
                -webkit-overflow-scrolling: touch;
                gap: 0.35rem;
                scrollbar-width: none;
            }

            .settings-tabs::-webkit-scrollbar {
                display: none;
            }

            .settings-tabs .nav-item {
                flex: 0 0 auto;
                scroll-snap-align: start;
            }

            .settings-tabs .nav-link {
                width: auto;
                min-height: 2.2rem;
                padding: 0.4rem 0.65rem;
                white-space: nowrap;
                font-size: 0.8rem;
                gap: 0.4rem;
            }

            .settings-tabs .nav-link i {
                width: 1.1rem;
                height: 1.1rem;
                font-size: 0.85rem;
            }

            .settings-content {
                padding: 0.75rem !important;
            }

            .settings-content-column .card-header,
            .settings-content-column .card-footer {
                align-items: stretch !important;
                gap: 0.5rem;
            }

            .settings-content-column .card-footer {
                flex-direction: column;
            }

            .settings-content-column .card-footer .btn,
            .settings-content-column .btn {
                max-width: 100%;
            }

            .settings-content-column .card-footer .btn {
                width: 100%;
            }

            .settings-content .d-flex.gap-2,
            .settings-content .d-flex.flex-wrap {
                min-width: 0;
            }

            .settings-content .d-flex.gap-2 > .btn {
                flex: 1 1 10rem;
            }

            .settings-page ._dm-settings-container {
                width: min(100vw, 28rem) !important;
                border-radius: 0 !important;
            }

            .settings-page ._dm-settings-container__content > [class*="col-"] {
                padding: 1rem !important;
            }

            .settings-page ._dm-settings-container .d-flex.mb-4.pb-4 {
                flex-direction: column;
                gap: 1rem;
            }

            .settings-page ._dm-settings-container .vr {
                display: none;
            }

            .settings-page #dm_colorModeContainer .row > [class*="col-"] {
                margin-bottom: 0.75rem;
            }

            .settings-page ._dm-settings-container .pt-3 .d-flex.gap-3 {
                flex-direction: column;
            }

            .settings-page ._dm-settings-container .alert {
                overflow-wrap: anywhere;
            }
        }

        @media (min-width: 768px) {
            .settings-tabs-column {
                position: sticky;
                top: 5rem;
                align-self: flex-start;
                max-height: calc(100vh - 6rem);
                overflow-y: auto;
                scrollbar-width: thin;
            }
        }
    </style>

    <div class="settings-page">
        <div class="content__boxed">
            <div class="content__wrap">
                <div class="settings-hero">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Settings</li>
                        </ol>
                    </nav>
                    <h1><i class="demo-psi-gear me-2"></i>Settings</h1>
                    <p>Manage configuration, company profile, and integrations from one place.</p>
                </div>

                <div class="settings-shell">
                    <div class="row g-0">
                        <div class="col-12 col-md-4 col-lg-3 border-end settings-tabs-column">
                            <h6 class="settings-tabs-heading d-none d-md-block">Categories</h6>
                            <ul class="nav flex-row flex-md-column nav-pills settings-tabs" role="tablist">
                                @can('configuration.settings')
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsConfiguration" type="button" role="tab" aria-selected="false"
                                            tabindex="-1">
                                            <i class="demo-pli-data-settings"></i><span>Configuration</span>
                                        </button>
                                    </li>
                                @endcan
                                @can('product.view')
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsProductSettings" type="button" role="tab"
                                            aria-selected="false" tabindex="-1">
                                            <i class="fa fa-cube"></i><span>Product Settings</span>
                                        </button>
                                    </li>
                                @endcan
                                @can('sale.view')
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsSaleSettings" type="button" role="tab"
                                            aria-selected="false" tabindex="-1">
                                            <i class="demo-pli-receipt-4"></i><span>Sale Settings</span>
                                        </button>
                                    </li>
                                @endcan
                                @can('purchase.view')
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsPurchaseSettings" type="button" role="tab"
                                            aria-selected="false" tabindex="-1">
                                            <i class="demo-pli-credit-card-2"></i><span>Purchase Settings</span>
                                        </button>
                                    </li>
                                @endcan
                                @can('tailoring order.view')
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsTailoringSettings" type="button" role="tab"
                                            aria-selected="false" tabindex="-1">
                                            <i class="demo-pli-repair"></i><span>Tailoring Settings</span>
                                        </button>
                                    </li>
                                @endcan
                                @can('rent out.view')
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsRentOutSettings" type="button" role="tab"
                                            aria-selected="false" tabindex="-1">
                                            <i class="demo-pli-home"></i><span>Rent Out Settings</span>
                                        </button>
                                    </li>
                                @endcan
                                @can('configuration.settings')
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsUniversalUom" type="button" role="tab"
                                            aria-selected="false" tabindex="-1">
                                            <i class="demo-pli-data-storage"></i><span>Universal UOM</span>
                                        </button>
                                    </li>
                                @endcan
                                @if (auth()->user()->is_super_admin)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsUniqueNoCounters" type="button" role="tab"
                                            aria-selected="false" tabindex="-1">
                                            <i class="fa fa-list-ol"></i><span>Unique No Counters</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsModuleConfiguration" type="button" role="tab"
                                            aria-selected="false" tabindex="-1">
                                            <i class="fa fa-cubes"></i><span>Module Configuration</span>
                                        </button>
                                    </li>
                                @endif
                                @can('configuration.settings')
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabsCompanyProfile" type="button" role="tab"
                                            aria-selected="true" tabindex="0">
                                            <i class="demo-pli-male"></i><span>Company Profile</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsTheme" type="button" role="tab"
                                            aria-selected="false" tabindex="-1">
                                            <i class="demo-psi-gear"></i><span>Theme</span>
                                        </button>
                                    </li>
                                @endcan
                                @if (false)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsWorkingDay" type="button" role="tab" aria-selected="false"
                                            tabindex="-1">
                                            <i class="demo-pli-calendar-4"></i><span>Working Day</span>
                                        </button>
                                    </li>
                                @endif
                                @can('configuration.settings')
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsNavigationOrder" type="button" role="tab"
                                            aria-selected="false" tabindex="-1">
                                            <i class="fa fa-bars"></i><span>Navigation Order</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsTelegram" type="button" role="tab" aria-selected="false"
                                            tabindex="-1">
                                            <i class="demo-pli-speech-bubble-5"></i><span>Telegram</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsNotificationPreferences" type="button" role="tab"
                                            aria-selected="false" tabindex="-1">
                                            <i class="demo-pli-bell"></i><span>Notifications</span>
                                        </button>
                                    </li>
                                @endcan
                                @if (false)
                                    @can('whatsapp.integration')
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsWhatsapp" type="button" role="tab" aria-selected="false"
                                                tabindex="-1">
                                                <i class="demo-pli-speech-bubble-4"></i><span>Whatsapp</span>
                                            </button>
                                        </li>
                                    @endcan
                                @endif
                            </ul>
                        </div>
                        <div class="col-12 col-md-8 col-lg-9 settings-content-column">
                            <div class="tab-content settings-content">
                                @can('configuration.settings')
                                    <div id="tabsConfiguration" class="tab-pane" role="tabpanel">
                                        @livewire('settings.configurations')
                                    </div>
                                    <div id="tabsProductSettings" class="tab-pane" role="tabpanel">
                                        @livewire('settings.product-configuration')
                                    </div>
                                @endcan
                                @can('sale.view')
                                    <div id="tabsSaleSettings" class="tab-pane" role="tabpanel">
                                        @livewire('settings.sale-configuration')
                                    </div>
                                @endcan
                                @can('purchase.view')
                                    <div id="tabsPurchaseSettings" class="tab-pane" role="tabpanel">
                                        @livewire('settings.purchase-configuration')
                                    </div>
                                @endcan
                                @can('tailoring order.view')
                                    <div id="tabsTailoringSettings" class="tab-pane" role="tabpanel">
                                        @livewire('settings.tailoring-configuration')
                                    </div>
                                @endcan
                                @can('rent out.view')
                                    <div id="tabsRentOutSettings" class="tab-pane" role="tabpanel">
                                        @livewire('settings.rent-out-configuration')
                                    </div>
                                @endcan
                                @can('configuration.settings')
                                    <div id="tabsUniversalUom" class="tab-pane" role="tabpanel">
                                        @livewire('settings.universal-uom-configuration')
                                    </div>
                                @endcan
                                @if (auth()->user()->is_super_admin)
                                    <div id="tabsUniqueNoCounters" class="tab-pane" role="tabpanel">
                                        @livewire('settings.unique-no-counter-configuration')
                                    </div>
                                    <div id="tabsModuleConfiguration" class="tab-pane" role="tabpanel">
                                        @livewire('settings.module-configuration')
                                    </div>
                                @endif
                                @can('configuration.settings')
                                    <div id="tabsCompanyProfile" class="tab-pane fade active show" role="tabpanel">
                                        @livewire('settings.company-profile')
                                    </div>
                                    <div id="tabsTheme" class="tab-pane" role="tabpanel">
                                        <div class="card">
                                            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <div>
                                                    <h5 class="card-title mb-0 fw-bold">Theme Settings</h5>
                                                    <small class="text-body-secondary">Customize your workspace appearance</small>
                                                </div>
                                                <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">
                                                    <i class="demo-psi-magic-wand me-1"></i>Personalize
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-body-secondary small mb-3">
                                                    All settings are automatically saved to your browser's local storage and synchronized with your account.
                                                </p>
                                                <div class="row g-3">
                                                    <div class="col-12 col-md-6 col-xl-4">
                                                        <div class="theme-action-card">
                                                            <div class="icon-pill primary"><i class="demo-psi-gear"></i></div>
                                                            <h6>Layout Preferences</h6>
                                                            <p>Choose your preferred layout style, transitions and positioning.</p>
                                                            <button class="btn btn-primary btn-sm w-100" id="openSettingsOffcanvas" type="button" data-bs-toggle="offcanvas"
                                                                data-bs-target="#_dm-settingsContainer">
                                                                <i class="demo-psi-gear me-1"></i> Open Settings Panel
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6 col-xl-4">
                                                        <div class="theme-action-card">
                                                            <div class="icon-pill success"><i class="demo-psi-synchronize"></i></div>
                                                            <h6>Sync with Server</h6>
                                                            <p>Synchronize theme settings between your browser and the server.</p>
                                                            <button class="btn btn-outline-success btn-sm w-100" id="syncThemeSettings">
                                                                <i class="demo-psi-synchronize me-1"></i> Sync Settings
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6 col-xl-4">
                                                        <div class="theme-action-card">
                                                            <div class="icon-pill danger"><i class="demo-psi-reset"></i></div>
                                                            <h6>Reset to Defaults</h6>
                                                            <p>Reset all theme settings to their default values.</p>
                                                            <button class="btn btn-outline-danger btn-sm w-100" id="resetThemeSettings">
                                                                <i class="demo-psi-reset me-1"></i> Reset Settings
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="theme-status-card">
                                                            <h6><i class="demo-psi-information"></i>Theme Settings Status</h6>
                                                            <div id="themeSettingsStatus">
                                                                <div class="status-row">
                                                                    <strong>Storage</strong>
                                                                    <span id="storageStatus" class="text-body">Checking...</span>
                                                                </div>
                                                                <div class="status-row">
                                                                    <strong>Last Updated</strong>
                                                                    <span id="lastUpdated" class="text-body">Checking...</span>
                                                                </div>
                                                                <div class="status-row">
                                                                    <strong>Sync Status</strong>
                                                                    <span id="syncStatus" class="text-body">Checking...</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endcan
                                <div id="tabsWorkingDay" class="tab-pane" role="tabpanel">
                                    @livewire('settings.working-day')
                                </div>
                                @can('configuration.settings')
                                    <div id="tabsNavigationOrder" class="tab-pane" role="tabpanel">
                                        @livewire('settings.navigation-order')
                                    </div>
                                    <div id="tabsTelegram" class="tab-pane" role="tabpanel">
                                        @livewire('settings.telegram')
                                    </div>
                                    <div id="tabsNotificationPreferences" class="tab-pane" role="tabpanel">
                                        @livewire('settings.notification-preferences')
                                    </div>
                                @endcan
                                @can('whatsapp.integration')
                                    <div id="tabsWhatsapp" class="tab-pane" role="tabpanel">
                                        {{-- @livewire('settings.whatsapp') --}}
                                    </div>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- SETTINGS CONTAINER  -->
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <div id="_dm-settingsContainer" class="_dm-settings-container offcanvas offcanvas-end rounded-start" tabindex="-1">
            <button id="_dm-settingsToggler" class="_dm-btn-settings btn btn-sm btn-danger p-2 rounded-0 rounded-start shadow-none" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#_dm-settingsContainer" aria-label="Customization button" aria-controls="#_dm-settingsContainer">
                <i class="demo-psi-gear fs-1"></i>
            </button>

            @livewire('settings.theme-settings')

            <div class="offcanvas-body py-0">
                <div class="_dm-settings-container__content row">
                    <div class="col-lg-3 p-4">

                        <h4 class="fw-bold pb-3 mb-2">Layouts</h4>

                        <!-- OPTION : Centered Layout -->
                        <h6 class="mb-2 pb-1">Layouts</h6>
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-fluidLayoutRadio">Fluid Layout</label>
                            <div class="form-check form-switch">
                                <input id="_dm-fluidLayoutRadio" class="form-check-input ms-0" type="radio" name="settingLayouts" autocomplete="off" checked>
                            </div>
                        </div>

                        <!-- OPTION : Boxed layout -->
                        <div class="d-flex align-items-center pt-1 mb-2" hidden style="display: none">
                            <label class="form-check-label flex-fill" for="_dm-boxedLayoutRadio">Boxed Layout</label>
                            <div class="form-check form-switch">
                                <input id="_dm-boxedLayoutRadio" class="form-check-input ms-0" type="radio" name="settingLayouts" autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Boxed layout with background images -->
                        <div id="_dm-boxedBgOption" class="opacity-50 d-flex align-items-center pt-1 mb-2" hidden style="display: none">
                            <label class="form-label flex-fill mb-0">BG for
                                Boxed Layout</label>

                            <button id="_dm-boxedBgBtn" class="btn btn-icon btn-primary btn-xs" type="button" data-bs-toggle="offcanvas" data-bs-target="#_dm-boxedBgContent" disabled>
                                <i class="demo-psi-dot-horizontal"></i>
                            </button>
                        </div>

                        <!-- OPTION : Centered Layout -->
                        <div class="d-flex align-items-start pt-1 pb-3 mb-2">
                            <label class="form-check-label flex-fill text-nowrap" for="_dm-centeredLayoutRadio">Centered
                                Layout</label>
                            <div class="form-check form-switch">
                                <input id="_dm-centeredLayoutRadio" class="form-check-input ms-0" type="radio" name="settingLayouts" autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Transition timing -->
                        <h6 class="mt-4 mb-2 py-1">Transitions</h6>
                        <div class="d-flex align-items-center pt-1 pb-3 mb-2">
                            <select id="_dm-transitionSelect" class="form-select" aria-label="select transition timing">
                                <option value="in-quart">In Quart</option>
                                <option value="out-quart" selected>Out
                                    Quart</option>
                                <option value="in-back">In Back</option>
                                <option value="out-back">Out Back</option>
                                <option value="in-out-back">In Out Back</option>
                                <option value="steps">Steps</option>
                                <option value="jumping">Jumping</option>
                                <option value="rubber">Rubber</option>
                            </select>
                        </div>

                        <!-- OPTION : Sticky Header -->
                        <h6 class="mt-4 mb-2 py-1">Header</h6>
                        <div class="d-flex align-items-center pt-1 pb-3 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-stickyHeaderCheckbox">Sticky
                                header</label>
                            <div class="form-check form-switch">
                                <input id="_dm-stickyHeaderCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                            </div>
                        </div>

                    </div>
                    <div class="col-lg-3 p-4 bg-body">

                        <h4 class="fw-bold pb-3 mb-2">Sidebars</h4>

                        <!-- OPTION : Sticky Navigation -->
                        <h6 class="mb-2 pb-1">Navigation</h6>
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-stickyNavCheckbox">Sticky
                                navigation</label>
                            <div class="form-check form-switch">
                                <input id="_dm-stickyNavCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Navigation Profile Widget -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-profileWidgetCheckbox">Widget
                                Profile</label>
                            <div class="form-check form-switch">
                                <input id="_dm-profileWidgetCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off" checked>
                            </div>
                        </div>

                        <!-- OPTION : Mini navigation mode -->
                        <div class="d-flex align-items-center pt-3 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-miniNavRadio">Min / Collapsed
                                Mode</label>
                            <div class="form-check form-switch">
                                <input id="_dm-miniNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode" autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Maxi navigation mode -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-maxiNavRadio">Max / Expanded
                                Mode</label>
                            <div class="form-check form-switch">
                                <input id="_dm-maxiNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode" autocomplete="off" checked>
                            </div>
                        </div>

                        <!-- OPTION : Push navigation mode -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-pushNavRadio">Push Mode</label>
                            <div class="form-check form-switch">
                                <input id="_dm-pushNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode" autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Slide on top navigation mode -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-slideNavRadio">Slide on top</label>
                            <div class="form-check form-switch">
                                <input id="_dm-slideNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode" autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Slide on top navigation mode -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-revealNavRadio">Reveal Mode</label>
                            <div class="form-check form-switch">
                                <input id="_dm-revealNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode" autocomplete="off">
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-3 py-3">
                            <button class="nav-toggler btn btn-primary btn-sm" type="button">
                                Navigation
                            </button>
                            <button class="sidebar-toggler btn btn-primary btn-sm" type="button">
                                Sidebar
                            </button>
                        </div>

                        <h6 class="mt-3 mb-2 py-1">Sidebar</h6>

                        <!-- OPTION : Disable sidebar backdrop -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-disableBackdropCheckbox">Disable
                                backdrop</label>
                            <div class="form-check form-switch">
                                <input id="_dm-disableBackdropCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Static position -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-staticSidebarCheckbox">Static
                                position</label>
                            <div class="form-check form-switch">
                                <input id="_dm-staticSidebarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Stuck sidebar -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-stuckSidebarCheckbox">Stuck Sidebar
                            </label>
                            <div class="form-check form-switch">
                                <input id="_dm-stuckSidebarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Unite Sidebar -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-uniteSidebarCheckbox">Unite
                                Sidebar</label>
                            <div class="form-check form-switch">
                                <input id="_dm-uniteSidebarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Pinned Sidebar -->
                        <div class="d-flex align-items-start pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-pinnedSidebarCheckbox">Pinned
                                Sidebar</label>
                            <div class="form-check form-switch">
                                <input id="_dm-pinnedSidebarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                            </div>
                        </div>

                    </div>
                    <div class="col-lg-6 p-4">
                        <h4 class="fw-bold pb-3 mb-2">Colors</h4>

                        <div class="d-flex mb-4 pb-4">
                            <div class="d-flex flex-column">
                                <h5 class="h6">Modes</h5>
                                <div class="form-check form-check-alt form-switch">
                                    <input id="settingsThemeToggler" class="form-check-input mode-switcher" type="checkbox" role="switch">
                                    <label class="form-check-label ps-3 fw-bold d-none d-md-flex align-items-center " for="settingsThemeToggler">
                                        <i class="mode-switcher-icon icon-light demo-psi-sun fs-3"></i>
                                        <i class="mode-switcher-icon icon-dark d-none demo-psi-half-moon fs-5"></i>
                                    </label>
                                </div>
                            </div>
                            <div class="vr mx-4"></div>
                            <div class="_dm-colorSchemesMode__colors">
                                <h5 class="h6">Color Schemes</h5>
                                <div id="dm_colorSchemesContainer" class="d-flex flex-wrap justify-content-center">
                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-gray" type="button" data-color="gray"></button>
                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-navy" type="button" data-color="navy"></button>
                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-ocean" type="button" data-color="ocean"></button>
                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-lime" type="button" data-color="lime"></button>

                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-violet" type="button" data-color="violet"></button>
                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-orange" type="button" data-color="orange"></button>
                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-teal" type="button" data-color="teal"></button>
                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-corn" type="button" data-color="corn"></button>

                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-cherry" type="button" data-color="cherry"></button>
                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-coffee" type="button" data-color="coffee"></button>
                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-pear" type="button" data-color="pear"></button>
                                    <button class="_dm-colorSchemes _dm-box-xs _dm-bg-night" type="button" data-color="night"></button>
                                </div>
                            </div>
                        </div>

                        <div id="dm_colorModeContainer">
                            <div class="row text-center mb-2">

                                <!-- Expanded Header -->
                                <div class="col-md-4">
                                    <h6 class="m-0">Expanded Header</h6>
                                    <div class="_dm-colorShcemesMode">

                                        <!-- Scheme Button -->
                                        <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--expanded-hd">
                                            <img src="./assets/img/color-schemes/expanded-header.png" alt="color scheme illusttration" loading="lazy">
                                        </button>

                                    </div>
                                </div>

                                <!-- Fair Header -->
                                <div class="col-md-4">
                                    <h6 class="m-0">Fair Header</h6>
                                    <div class="_dm-colorShcemesMode">

                                        <!-- Scheme Button -->
                                        <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--fair-hd">
                                            <img src="./assets/img/color-schemes/fair-header.png" alt="color scheme illusttration" loading="lazy">
                                        </button>

                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <h6 class="m-0">Full Header</h6>

                                    <div class="_dm-colorShcemesMode">

                                        <!-- Scheme Button -->
                                        <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--full-hd">
                                            <img src="./assets/img/color-schemes/full-header.png" alt="color scheme illusttration" loading="lazy">
                                        </button>

                                    </div>
                                </div>
                            </div>

                            <div class="row text-center mb-2">
                                <div class="col-md-4">
                                    <h6 class="m-0">Primary Nav</h6>

                                    <div class="_dm-colorShcemesMode">

                                        <!-- Scheme Button -->
                                        <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--primary-mn">
                                            <img src="./assets/img/color-schemes/navigation.png" alt="color scheme illusttration" loading="lazy">
                                        </button>

                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <h6 class="m-0">Brand</h6>

                                    <div class="_dm-colorShcemesMode">

                                        <!-- Scheme Button -->
                                        <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--primary-brand">
                                            <img src="./assets/img/color-schemes/brand.png" alt="color scheme illusttration" loading="lazy">
                                        </button>

                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <h6 class="m-0">Tall Header</h6>
                                    <div class="_dm-colorShcemesMode">

                                        <!-- Scheme Button -->
                                        <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--tall-hd">
                                            <img src="./assets/img/color-schemes/tall-header.png" alt="color scheme illusttration" loading="lazy">
                                        </button>

                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="pt-3">

                            <h5 class="fw-bold mt-2">Miscellaneous</h5>

                            <div class="d-flex gap-3 my-3">
                                <label for="_dm-fontSizeRange" class="form-label flex-shrink-0 mb-0">Root
                                    Font sizes</label>
                                <div class="position-relative flex-fill">
                                    <input type="range" class="form-range" min="9" max="19" step="1" value="16" id="_dm-fontSizeRange">
                                    <output id="_dm-fontSizeValue" class="range-bubble"></output>
                                </div>
                            </div>

                            <h5 class="fw-bold mt-4">Scrollbars</h5>
                            <p class="mb-2">Hides native scrollbars and creates
                                custom styleable overlay scrollbars.</p>
                            <div class="row">
                                <div class="col-5">

                                    <!-- OPTION : Apply the OverlayScrollBar to the body. -->
                                    <div class="d-flex align-items-center pt-1 mb-2">
                                        <label class="form-check-label flex-fill" for="_dm-bodyScrollbarCheckbox">Body
                                            scrollbar</label>
                                        <div class="form-check form-switch">
                                            <input id="_dm-bodyScrollbarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- OPTION : Apply the OverlayScrollBar to content containing class .scrollable-content. -->
                                    <div class="d-flex align-items-center pt-1 mb-2">
                                        <label class="form-check-label flex-fill" for="_dm-sidebarsScrollbarCheckbox">Navigation
                                            and Sidebar</label>
                                        <div class="form-check form-switch">
                                            <input id="_dm-sidebarsScrollbarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                                        </div>
                                    </div>

                                </div>
                                <div class="col-7">

                                    <div class="alert alert-warning mb-0" role="alert">
                                        Please consider the performance impact
                                        of using any scrollbar plugin.
                                    </div>

                                </div>
                            </div>

                        </div>

                    </div>
                </div>

            </div>
        </div>
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <!-- END - SETTINGS CONTAINER [ DEMO ] -->

        <!-- OFFCANVAS [ DEMO ] -->
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <div id="_dm-offcanvas" class="offcanvas" tabindex="-1">

            <!-- Offcanvas header -->
            <div class="offcanvas-header">
                <h5 class="offcanvas-title">Offcanvas Header</h5>
                <button type="button" class="btn-close btn-lg text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>

            <!-- Offcanvas content -->
            <div class="offcanvas-body">
                <h5>Content Here</h5>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                    Sapiente eos nihil earum aliquam quod in dolor, aspernatur
                    obcaecati et at. Dicta, ipsum aut, fugit nam dolore porro
                    non est totam sapiente animi recusandae obcaecati dolorum,
                    rem ullam cumque. Illum quidem reiciendis autem neque
                    excepturi odit est accusantium, facilis provident molestias,
                    dicta obcaecati itaque ducimus fuga iure in distinctio
                    voluptate nesciunt dignissimos rem error a. Expedita
                    officiis nam dolore dolores ea. Soluta repellendus delectus
                    culpa quo. Ea tenetur impedit error quod exercitationem ut
                    ad provident quisquam omnis! Nostrum quasi ex delectus vero,
                    facilis aut recusandae deleniti beatae. Qui velit commodi
                    inventore.</p>
            </div>

        </div>
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <!-- END - OFFCANVAS [ DEMO ] -->
        @push('scripts')
            @include('components.select.accountSelect')
            <script src="{{ asset('js/theme-settings.js') }}"></script>
            <script src="{{ asset('js/theme-settings-status.js') }}"></script>
            <script src="{{ asset('js/theme-settings-sync.js') }}"></script>
        @endpush
    </div>
</x-app-layout>
