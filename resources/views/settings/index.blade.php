<x-app-layout :collapsed-nav="true">

    <style>
        /* ============================================================
           .stx — Settings "Studio Split" (scoped; accent = theme colour)
           ============================================================ */
        .stx {
            --stx-acc: var(--bs-primary);
            --stx-acc-soft: color-mix(in srgb, var(--stx-acc) 11%, transparent);
            --stx-acc-ink: color-mix(in srgb, var(--stx-acc) 85%, var(--bs-emphasis-color));
            --stx-grad: linear-gradient(135deg, color-mix(in srgb, var(--stx-acc) 80%, #fff), var(--stx-acc) 45%, color-mix(in srgb, var(--stx-acc) 70%, #000));
            --stx-glow: 0 10px 22px -12px color-mix(in srgb, var(--stx-acc) 80%, transparent);
            --stx-surface: var(--bs-component-bg, var(--bs-body-bg));
            --stx-surface-2: color-mix(in srgb, var(--stx-acc) 3%, var(--stx-surface));
            --stx-line: var(--bs-border-color);
            --stx-line-soft: color-mix(in srgb, var(--bs-border-color) 60%, transparent);
            --stx-shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 14px 34px -18px rgba(16, 24, 40, .22);
            min-width: 0;
        }

        [data-bs-theme="dark"] .stx {
            --stx-acc-ink: color-mix(in srgb, var(--stx-acc) 50%, #fff);
            --stx-acc-soft: color-mix(in srgb, var(--stx-acc) 24%, transparent);
            --stx-shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 14px 34px -16px rgba(0, 0, 0, .6);
        }

        .stx .content__wrap {
            padding-block: 1rem;
        }

        .stx-shell {
            display: grid;
            grid-template-columns: 272px minmax(0, 1fr);
            min-height: calc(100vh - 8rem);
            border-radius: 20px;
            background: var(--stx-surface);
            box-shadow: var(--stx-shadow);
            overflow: clip;
        }

        /* ---- Sidebar ---- */
        .stx-side {
            position: sticky;
            top: 4.5rem;
            align-self: start;
            max-height: calc(100vh - 5rem);
            overflow-y: auto;
            scrollbar-width: thin;
            padding: 1.1rem .75rem 1.25rem;
            border-inline-end: 1px solid var(--stx-line-soft);
            background: linear-gradient(180deg, color-mix(in srgb, var(--stx-acc) 7%, var(--stx-surface)), var(--stx-surface) 320px);
        }

        .stx-brand {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: 0 .4rem .9rem;
        }

        .stx-brand-ic {
            width: 40px;
            height: 40px;
            flex: 0 0 auto;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.2rem;
            color: #fff;
            background: var(--stx-grad);
            box-shadow: var(--stx-glow);
        }

        .stx-brand h1 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            color: var(--bs-emphasis-color);
        }

        .stx-brand small {
            color: var(--bs-secondary-color);
            font-size: .74rem;
        }

        .stx-search {
            position: relative;
            display: block;
            margin: 0 .25rem .9rem;
        }

        .stx-search i {
            position: absolute;
            inset-inline-start: .8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--bs-tertiary-color);
            font-size: .8rem;
        }

        .stx-search input {
            width: 100%;
            height: 36px;
            border-radius: 10px;
            border: 1px solid var(--stx-line);
            background: var(--stx-surface);
            color: var(--bs-emphasis-color);
            padding: 0 .75rem 0 2.1rem;
            font-size: .82rem;
            outline: 0;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        [dir="rtl"] .stx-search input {
            padding: 0 2.1rem 0 .75rem;
        }

        .stx-search input:focus {
            border-color: var(--stx-acc);
            box-shadow: 0 0 0 4px var(--stx-acc-soft);
        }

        .stx-group+.stx-group {
            margin-top: .65rem;
        }

        .stx-group h6 {
            margin: .3rem .65rem .3rem;
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--bs-tertiary-color);
        }

        .stx-cat {
            all: unset;
            box-sizing: border-box;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: .65rem;
            width: 100%;
            padding: .4rem .5rem;
            border-radius: 11px;
            color: var(--bs-body-color);
            font-size: .84rem;
            font-weight: 500;
            transition: background-color .15s ease, box-shadow .15s ease;
        }

        .stx-cat:hover {
            background: var(--stx-acc-soft);
        }

        .stx-cat:focus-visible {
            outline: 2px solid var(--stx-acc);
            outline-offset: 1px;
        }

        .stx-cat.active {
            background: var(--stx-surface);
            color: var(--bs-emphasis-color);
            font-weight: 600;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .06), 0 8px 18px -12px rgba(0, 0, 0, .3), inset 0 0 0 1px var(--stx-line-soft);
        }

        .stx-cat-ic {
            width: 28px;
            height: 28px;
            flex: 0 0 auto;
            border-radius: 8px;
            display: grid;
            place-items: center;
            color: #fff;
            font-size: .85rem;
            background: var(--tile, var(--stx-acc));
            box-shadow: inset 0 -1px 0 rgba(0, 0, 0, .12);
        }

        .stx-cat-ic i::before {
            margin: 0 !important;
        }

        .stx-cat-t {
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stx-cat-arrow {
            color: var(--bs-tertiary-color);
            font-size: .8rem;
            opacity: 0;
            transition: opacity .15s ease;
        }

        [dir="rtl"] .stx-cat-arrow {
            transform: scaleX(-1);
        }

        .stx-cat:hover .stx-cat-arrow,
        .stx-cat.active .stx-cat-arrow {
            opacity: 1;
        }

        .stx .tone-blue { --tile: #2f6fd6; }
        .stx .tone-indigo { --tile: var(--stx-acc); }
        .stx .tone-violet { --tile: #7c4fd6; }
        .stx .tone-green { --tile: #2f9e62; }
        .stx .tone-teal { --tile: #1f9a96; }
        .stx .tone-amber { --tile: #d4931c; }
        .stx .tone-orange { --tile: #e0672a; }
        .stx .tone-pink { --tile: #d4478a; }
        .stx .tone-red { --tile: #d94848; }
        .stx .tone-slate { --tile: #5b6779; }

        .stx-empty {
            margin: .75rem .65rem 0;
            font-size: .78rem;
            color: var(--bs-secondary-color);
        }

        /* ---- Main ---- */
        .stx-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .stx-head {
            padding: 1.35rem 1.75rem 1rem;
            border-bottom: 1px solid var(--stx-line-soft);
        }

        .stx-head .breadcrumb {
            margin-bottom: .3rem;
            font-size: .76rem;
        }

        .stx-head .breadcrumb,
        .stx-head .breadcrumb a,
        .stx-head .breadcrumb-item.active,
        .stx-head .breadcrumb-item+.breadcrumb-item::before {
            color: var(--bs-secondary-color);
        }

        .stx-head .breadcrumb a:hover {
            color: var(--stx-acc-ink);
        }

        .stx-head h2 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -.01em;
            color: var(--bs-emphasis-color);
        }

        .stx-head p {
            margin: .25rem 0 0;
            font-size: .84rem;
            color: var(--bs-secondary-color);
        }

        .stx-body {
            padding: 1.35rem 1.75rem 1.75rem;
            min-width: 0;
            flex: 1;
        }

        /* Every tab's own cards become flat, bordered "group" cards inside the surface. */
        .stx-body .card {
            border: 1px solid var(--stx-line);
            border-radius: 14px;
            box-shadow: none;
            background: var(--stx-surface);
        }

        .stx-body .card-header {
            background: var(--stx-surface-2);
            border-bottom: 1px solid var(--stx-line-soft);
            padding: .9rem 1.1rem;
        }

        .stx-body .card-footer {
            background: var(--stx-surface-2);
            border-top: 1px solid var(--stx-line-soft);
        }

        .stx-body .card-title {
            color: var(--bs-emphasis-color);
        }

        .stx-body .form-control:focus,
        .stx-body .form-select:focus {
            border-color: var(--stx-acc);
            box-shadow: 0 0 0 4px var(--stx-acc-soft);
        }

        .stx-body .tab-pane,
        .stx-body .tab-content {
            min-width: 0;
        }

        /* ---- Responsive ---- */
        @media (max-width: 991.98px) {
            .stx-shell {
                grid-template-columns: minmax(0, 1fr);
                min-height: 0;
            }

            .stx-side {
                position: static;
                max-height: none;
                padding: .65rem;
                border-inline-end: 0;
                border-bottom: 1px solid var(--stx-line-soft);
            }

            .stx-brand {
                padding-bottom: .65rem;
            }

            .stx-search {
                margin-bottom: .55rem;
            }

            .stx-cats {
                display: flex;
                gap: .35rem;
                overflow-x: auto;
                scrollbar-width: thin;
                padding-bottom: .2rem;
            }

            .stx-group {
                display: contents;
            }

            .stx-group h6,
            .stx-cat-arrow {
                display: none;
            }

            .stx-cat {
                width: auto;
                flex: 0 0 auto;
                padding: .35rem .65rem .35rem .4rem;
                border: 1px solid var(--stx-line-soft);
            }

            .stx-cat-ic {
                width: 24px;
                height: 24px;
                font-size: .75rem;
            }

            .stx-head {
                padding: 1rem 1rem .85rem;
            }

            .stx-body {
                padding: 1rem;
            }
        }

        @media (max-width: 575.98px) {
            .stx .content__wrap {
                padding-inline: .5rem;
            }

            .stx-shell {
                border-radius: 14px;
            }

            .stx-head h2 {
                font-size: 1.15rem;
            }
        }
    </style>

    @php
        $user = auth()->user();
        $canSettings = $user->can('configuration.settings');
        $settingsGroups = [
            'General' => [
                ['target' => 'CompanyProfile', 'label' => 'Company Profile', 'icon' => 'demo-pli-male', 'tone' => 'blue', 'desc' => 'Company name, logo, address and contact details.', 'show' => $canSettings],
                ['target' => 'Configuration', 'label' => 'Configuration', 'icon' => 'demo-pli-data-settings', 'tone' => 'violet', 'desc' => 'Global preferences that apply across every module.', 'show' => $canSettings],
                ['target' => 'Currencies', 'label' => 'Currencies', 'icon' => 'fa fa-money', 'tone' => 'green', 'desc' => 'Base currency, symbols and exchange rates.', 'show' => $canSettings],
                ['target' => 'ModuleConfiguration', 'label' => 'Module Configuration', 'icon' => 'fa fa-cubes', 'tone' => 'amber', 'desc' => 'Switch application modules on or off.', 'show' => (bool) $user->is_super_admin],
                ['target' => 'UniqueNoCounters', 'label' => 'Unique No Counters', 'icon' => 'fa fa-list-ol', 'tone' => 'teal', 'desc' => 'Document numbering sequences and prefixes.', 'show' => (bool) $user->is_super_admin],
                ['target' => 'Theme', 'label' => 'Theme', 'icon' => 'demo-psi-gear', 'tone' => 'pink', 'desc' => 'Workspace appearance, colours and layout.', 'show' => $canSettings],
                ['target' => 'LoginPage', 'label' => 'Login Page', 'icon' => 'fa fa-sign-in', 'tone' => 'indigo', 'desc' => 'Sign-in screen layout and live background.', 'show' => $canSettings],
                ['target' => 'Storefront', 'label' => 'Storefront', 'icon' => 'fa fa-paint-brush', 'tone' => 'pink', 'desc' => 'Public storefront branding.', 'show' => false],
            ],
            'Sales & Stock' => [
                ['target' => 'ProductSettings', 'label' => 'Product Settings', 'icon' => 'fa fa-cube', 'tone' => 'orange', 'desc' => 'Product defaults, barcodes and catalogue options.', 'show' => $user->can('product.view')],
                ['target' => 'SaleSettings', 'label' => 'Sale Settings', 'icon' => 'demo-pli-receipt-4', 'tone' => 'green', 'desc' => 'POS behaviour, receipts and sale defaults.', 'show' => $user->can('sale.view')],
                ['target' => 'Printers', 'label' => 'Printers', 'icon' => 'demo-pli-printer', 'tone' => 'slate', 'desc' => 'Receipt and label printers for this workspace.', 'show' => $user->canAny(['sale.create', 'student.view', 'tailoring order.view', 'issue.view'])],
                ['target' => 'PurchaseSettings', 'label' => 'Purchase Settings', 'icon' => 'demo-pli-credit-card-2', 'tone' => 'blue', 'desc' => 'Purchase and LPO defaults.', 'show' => $user->can('purchase.view')],
                ['target' => 'UniversalUom', 'label' => 'Universal UOM', 'icon' => 'demo-pli-data-storage', 'tone' => 'violet', 'desc' => 'Units of measure shared by every product.', 'show' => $canSettings],
                ['target' => 'TailoringSettings', 'label' => 'Tailoring Settings', 'icon' => 'demo-pli-repair', 'tone' => 'amber', 'desc' => 'Tailoring order options.', 'show' => $user->can('tailoring order.view')],
            ],
            'Property' => [
                ['target' => 'RentOutSettings', 'label' => 'Rent Out Settings', 'icon' => 'demo-pli-home', 'tone' => 'indigo', 'desc' => 'Bookings, agreement print layout and PDF branding.', 'show' => $user->can('rent out.view')],
                ['target' => 'LeadSettings', 'label' => 'Lead Settings', 'icon' => 'demo-pli-list-view', 'tone' => 'orange', 'desc' => 'Lead sources, statuses and assignee designations.', 'show' => $canSettings],
            ],
            'School' => [
                ['target' => 'StudentCards', 'label' => 'Student Cards', 'icon' => 'fa fa-graduation-cap', 'tone' => 'teal', 'desc' => 'Student card and wallet settings.', 'show' => $user->can('student settings.edit')],
                ['target' => 'OnlinePayments', 'label' => 'Online Payments', 'icon' => 'fa fa-credit-card', 'tone' => 'green', 'desc' => 'Online payment gateways and keys.', 'show' => $user->can('student settings.edit')],
            ],
            'Calendar' => [
                ['target' => 'WorkingDay', 'label' => 'Working Day', 'icon' => 'demo-pli-calendar-4', 'tone' => 'teal', 'desc' => 'Opening hours and working days per branch.', 'show' => true],
                ['target' => 'Holiday', 'label' => 'Holiday Calendar', 'icon' => 'fa fa-calendar-o', 'tone' => 'red', 'desc' => 'Public holidays and closures.', 'show' => true],
            ],
            'System' => [
                ['target' => 'NavigationOrder', 'label' => 'Navigation Order', 'icon' => 'fa fa-bars', 'tone' => 'slate', 'desc' => 'Order of the items in the main menu.', 'show' => $canSettings],
                ['target' => 'NotificationPreferences', 'label' => 'Notifications', 'icon' => 'demo-pli-bell', 'tone' => 'amber', 'desc' => 'Which alerts are sent and to whom.', 'show' => $canSettings],
                ['target' => 'Telegram', 'label' => 'Telegram', 'icon' => 'demo-pli-speech-bubble-5', 'tone' => 'blue', 'desc' => 'Telegram bot notifications.', 'show' => $canSettings],
                ['target' => 'Whatsapp', 'label' => 'Whatsapp', 'icon' => 'demo-pli-speech-bubble-4', 'tone' => 'green', 'desc' => 'WhatsApp messaging integration.', 'show' => $user->can('whatsapp.integration')],
            ],
        ];
        $settingsGroups = array_filter(array_map(fn ($items) => array_values(array_filter($items, fn ($item) => $item['show'])), $settingsGroups));
        $defaultTab = 'CompanyProfile';
        $defaultItem = collect($settingsGroups)->flatten(1)->firstWhere('target', $defaultTab);
        $defaultGroup = collect($settingsGroups)->search(fn ($items) => collect($items)->contains('target', $defaultTab)) ?: 'Settings';
    @endphp

    <div class="settings-page stx">
        <div class="content__boxed">
            <div class="content__wrap">
                <div class="stx-shell">
                    <aside class="stx-side">
                        <div class="stx-brand">
                            <span class="stx-brand-ic"><i class="demo-psi-gear"></i></span>
                            <div>
                                <h1>Settings</h1>
                                <small>Workspace control center</small>
                            </div>
                        </div>
                        <label class="stx-search">
                            <i class="fa fa-search"></i>
                            <input type="search" placeholder="Search settings" aria-label="Search settings" data-settings-search>
                        </label>
                        <div class="stx-cats settings-tabs" role="tablist" aria-label="Settings categories">
                            @foreach ($settingsGroups as $groupName => $items)
                                <div class="stx-group" data-settings-group>
                                    <h6>{{ $groupName }}</h6>
                                    @foreach ($items as $item)
                                        @php $isDefault = $item['target'] === $defaultTab; @endphp
                                        <button class="stx-cat {{ $isDefault ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tabs{{ $item['target'] }}"
                                            type="button" role="tab" aria-selected="{{ $isDefault ? 'true' : 'false' }}" tabindex="{{ $isDefault ? '0' : '-1' }}"
                                            data-settings-title="{{ $item['label'] }}" data-settings-group-name="{{ $groupName }}" data-settings-desc="{{ $item['desc'] }}">
                                            <span class="stx-cat-ic tone-{{ $item['tone'] }}"><i class="{{ $item['icon'] }}"></i></span>
                                            <span class="stx-cat-t">{{ $item['label'] }}</span>
                                            <i class="fa fa-angle-right stx-cat-arrow"></i>
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                            <p class="stx-empty" data-settings-empty hidden>No settings match your search.</p>
                        </div>
                    </aside>

                    <div class="stx-main">
                        <header class="stx-head">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                                    <li class="breadcrumb-item">Settings</li>
                                    <li class="breadcrumb-item active" aria-current="page" data-settings-crumb>{{ $defaultGroup }}</li>
                                </ol>
                            </nav>
                            <h2 data-settings-heading>{{ $defaultItem['label'] ?? 'Settings' }}</h2>
                            <p data-settings-subtitle>{{ $defaultItem['desc'] ?? 'Manage configuration, company profile, and integrations from one place.' }}</p>
                        </header>
                        <div class="stx-body">
                            <div class="tab-content">
                                <div id="tabsMyPermissions" class="tab-pane" role="tabpanel">
                                    @php
                                        $myPermissions = auth()->user()->getAllPermissions()->pluck('name')->sort()->values();
                                        $myPermissionGroups = $myPermissions->groupBy(fn($name) => \Illuminate\Support\Str::before($name, '.'));
                                    @endphp
                                    <div class="card">
                                        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div>
                                                <h5 class="card-title mb-0 fw-bold">My Permissions</h5>
                                                <small class="text-body-secondary">Permissions granted to your account &mdash; these also control
                                                    what you can access in the mobile app.</small>
                                            </div>
                                            <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">
                                                {{ $myPermissions->count() }}
                                                {{ \Illuminate\Support\Str::plural('permission', $myPermissions->count()) }}
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            @if (auth()->user()->is_admin || auth()->user()->is_super_admin)
                                                <div class="alert alert-info mb-3">
                                                    <i class="demo-psi-information me-1"></i>
                                                    Your account is an administrator, so it implicitly has access to every feature regardless of the
                                                    list below.
                                                </div>
                                            @endif
                                            @forelse ($myPermissionGroups as $group => $permissions)
                                                <div class="mb-3">
                                                    <h6 class="text-uppercase text-body-secondary small fw-bold mb-2">{{ $group }}</h6>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach ($permissions as $permission)
                                                            <span class="badge text-bg-secondary-subtle text-body border">{{ $permission }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-body-secondary mb-0">No explicit permissions are assigned to your account.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                                @can('configuration.settings')
                                    <div id="tabsConfiguration" class="tab-pane" role="tabpanel">
                                        @livewire('settings.configurations')
                                    </div>
                                    <div id="tabsCurrencies" class="tab-pane" role="tabpanel">
                                        @livewire('settings.currency-configuration')
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
                                @canany(['sale.create', 'student.view', 'tailoring order.view', 'issue.view'])
                                    <div id="tabsPrinters" class="tab-pane" role="tabpanel">
                                        @include('settings.printers')
                                    </div>
                                @endcanany
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
                                    <div id="tabsLeadSettings" class="tab-pane" role="tabpanel">
                                        @livewire('settings.lead-assignee-designations')
                                        @livewire('settings.lead-dropdown-options')
                                    </div>
                                @endcan
                                @if (\App\Support\ModuleAccess::school() && auth()->user()->can('student settings.edit'))
                                    <div id="tabsStudentCards" class="tab-pane" role="tabpanel">
                                        @livewire('settings.student-configuration')
                                        @livewire('settings.q-pay-payments')
                                        @livewire('settings.mpgs-payments')
                                    </div>
                                @endif
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
                                    <div id="tabsLoginPage" class="tab-pane" role="tabpanel">
                                        @livewire('settings.login-page-settings')
                                    </div>
                                    <div id="tabsStorefront" class="tab-pane" role="tabpanel">
                                        @livewire('settings.storefront-branding')
                                    </div>
                                    <div id="tabsOnlinePayments" class="tab-pane" role="tabpanel">
                                        @livewire('settings.online-payments')
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
                                                    All settings are automatically saved to your browser's local storage and synchronized with your
                                                    account.
                                                </p>
                                                <div class="row g-3">
                                                    <div class="col-12 col-md-6 col-xl-4">
                                                        <div class="border rounded-3 p-3 h-100">
                                                            <div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary fs-5 p-2 lh-1 mb-2"><i class="demo-psi-gear"></i></div>
                                                            <h6 class="fw-semibold mb-1">Layout Preferences</h6>
                                                            <p class="small text-body-secondary mb-3">Choose your preferred layout style, transitions and positioning.</p>
                                                            <button class="btn btn-primary btn-sm w-100" id="openSettingsOffcanvas" type="button"
                                                                data-bs-toggle="offcanvas" data-bs-target="#_dm-settingsContainer">
                                                                <i class="demo-psi-gear me-1"></i> Open Settings Panel
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6 col-xl-4">
                                                        <div class="border rounded-3 p-3 h-100">
                                                            <div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success fs-5 p-2 lh-1 mb-2"><i class="demo-psi-refresh"></i></div>
                                                            <h6 class="fw-semibold mb-1">Sync with Server</h6>
                                                            <p class="small text-body-secondary mb-3">Synchronize theme settings between your browser and the server.</p>
                                                            <button class="btn btn-outline-success btn-sm w-100" id="syncThemeSettings">
                                                                <i class="demo-psi-refresh me-1"></i> Sync Settings
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6 col-xl-4">
                                                        <div class="border rounded-3 p-3 h-100">
                                                            <div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-danger-subtle text-danger fs-5 p-2 lh-1 mb-2"><i class="demo-psi-back"></i></div>
                                                            <h6 class="fw-semibold mb-1">Reset to Defaults</h6>
                                                            <p class="small text-body-secondary mb-3">Reset all theme settings to their default values.</p>
                                                            <button class="btn btn-outline-danger btn-sm w-100" id="resetThemeSettings">
                                                                <i class="demo-psi-back me-1"></i> Reset Settings
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="border border-primary-subtle bg-primary-subtle rounded-3 p-3">
                                                            <h6 class="fw-bold d-flex align-items-center gap-2 mb-2"><i class="demo-psi-information"></i>Theme Settings Status</h6>
                                                            <div id="themeSettingsStatus">
                                                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
                                                                    <strong>Storage</strong>
                                                                    <span id="storageStatus" class="text-body">Checking...</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
                                                                    <strong>Last Updated</strong>
                                                                    <span id="lastUpdated" class="text-body">Checking...</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
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
                                <div id="tabsHoliday" class="tab-pane" role="tabpanel">
                                    @livewire('settings.holiday')
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
                                        @livewire('settings.whatsapp')
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
            <button id="_dm-settingsToggler" class="_dm-btn-settings btn btn-sm btn-danger p-2 rounded-0 rounded-start shadow-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#_dm-settingsContainer" aria-label="Customization button"
                aria-controls="#_dm-settingsContainer">
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
                                <input id="_dm-fluidLayoutRadio" class="form-check-input ms-0" type="radio" name="settingLayouts"
                                    autocomplete="off" checked>
                            </div>
                        </div>

                        <!-- OPTION : Boxed layout -->
                        <div class="d-flex align-items-center pt-1 mb-2" hidden style="display: none">
                            <label class="form-check-label flex-fill" for="_dm-boxedLayoutRadio">Boxed Layout</label>
                            <div class="form-check form-switch">
                                <input id="_dm-boxedLayoutRadio" class="form-check-input ms-0" type="radio" name="settingLayouts"
                                    autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Boxed layout with background images -->
                        <div id="_dm-boxedBgOption" class="opacity-50 d-flex align-items-center pt-1 mb-2" hidden style="display: none">
                            <label class="form-label flex-fill mb-0">BG for
                                Boxed Layout</label>

                            <button id="_dm-boxedBgBtn" class="btn btn-icon btn-primary btn-xs" type="button" data-bs-toggle="offcanvas"
                                data-bs-target="#_dm-boxedBgContent" disabled>
                                <i class="demo-psi-dot-horizontal"></i>
                            </button>
                        </div>

                        <!-- OPTION : Centered Layout -->
                        <div class="d-flex align-items-start pt-1 pb-3 mb-2">
                            <label class="form-check-label flex-fill text-nowrap" for="_dm-centeredLayoutRadio">Centered
                                Layout</label>
                            <div class="form-check form-switch">
                                <input id="_dm-centeredLayoutRadio" class="form-check-input ms-0" type="radio" name="settingLayouts"
                                    autocomplete="off">
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
                                <input id="_dm-miniNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode"
                                    autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Maxi navigation mode -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-maxiNavRadio">Max / Expanded
                                Mode</label>
                            <div class="form-check form-switch">
                                <input id="_dm-maxiNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode"
                                    autocomplete="off" checked>
                            </div>
                        </div>

                        <!-- OPTION : Push navigation mode -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-pushNavRadio">Push Mode</label>
                            <div class="form-check form-switch">
                                <input id="_dm-pushNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode"
                                    autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Slide on top navigation mode -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-slideNavRadio">Slide on top</label>
                            <div class="form-check form-switch">
                                <input id="_dm-slideNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode"
                                    autocomplete="off">
                            </div>
                        </div>

                        <!-- OPTION : Slide on top navigation mode -->
                        <div class="d-flex align-items-center pt-1 mb-2">
                            <label class="form-check-label flex-fill" for="_dm-revealNavRadio">Reveal Mode</label>
                            <div class="form-check form-switch">
                                <input id="_dm-revealNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode"
                                    autocomplete="off">
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

                        <!-- OPTION : Sidebar Style (premium skin) -->
                        <h6 class="mt-4 mb-2 py-1">Sidebar Style</h6>
                        <p class="text-muted small mb-2">Premium look for the left navigation. Applies instantly.</p>
                        <div class="row row-cols-3 g-2">
                            <div class="col">
                                <button type="button" class="_dm-sidebarStyle btn btn-outline-primary w-100 d-flex flex-column align-items-center gap-1 p-2 active"
                                    data-nav-skin="standard" title="Standard — clean flat, single blue accent">
                                    <span class="d-block w-100 rounded border p-3 bg-primary"></span>
                                    <span class="small fw-semibold">Standard</span>
                                </button>
                            </div>
                            <div class="col">
                                <button type="button" class="_dm-sidebarStyle btn btn-outline-primary w-100 d-flex flex-column align-items-center gap-1 p-2"
                                    data-nav-skin="mono" title="Editorial Mono — minimal, hairline restraint">
                                    <span class="d-block w-100 rounded border p-3 bg-dark"></span>
                                    <span class="small fw-semibold">Mono</span>
                                </button>
                            </div>
                            <div class="col">
                                <button type="button" class="_dm-sidebarStyle btn btn-outline-primary w-100 d-flex flex-column align-items-center gap-1 p-2"
                                    data-nav-skin="atelier" title="Warm Atelier — champagne-brass luxury">
                                    <span class="d-block w-100 rounded border p-3 bg-warning"></span>
                                    <span class="small fw-semibold">Atelier</span>
                                </button>
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
                                            <img src="./assets/img/color-schemes/expanded-header.png" alt="color scheme illusttration"
                                                loading="lazy">
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
                                    <input type="range" class="form-range" min="9" max="19" step="1" value="16"
                                        id="_dm-fontSizeRange">
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
                                            <input id="_dm-bodyScrollbarCheckbox" class="form-check-input ms-0" type="checkbox"
                                                autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- OPTION : Apply the OverlayScrollBar to content containing class .scrollable-content. -->
                                    <div class="d-flex align-items-center pt-1 mb-2">
                                        <label class="form-check-label flex-fill" for="_dm-sidebarsScrollbarCheckbox">Navigation
                                            and Sidebar</label>
                                        <div class="form-check form-switch">
                                            <input id="_dm-sidebarsScrollbarCheckbox" class="form-check-input ms-0" type="checkbox"
                                                autocomplete="off">
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
            <script>
                (function() {
                    const tabs = document.querySelectorAll('.settings-tabs [data-bs-toggle="tab"]');
                    const toSlug = (target) => target.replace(/^#tabs/, '').replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();

                    const requested = new URLSearchParams(window.location.search).get('tab');
                    if (requested) {
                        const button = Array.from(tabs).find((tab) => toSlug(tab.dataset.bsTarget) === requested.toLowerCase());
                        if (button) {
                            bootstrap.Tab.getOrCreateInstance(button).show();
                            button.scrollIntoView({
                                block: 'nearest',
                                inline: 'center'
                            });
                        }
                    }

                    const heading = document.querySelector('[data-settings-heading]');
                    const crumb = document.querySelector('[data-settings-crumb]');
                    const desc = document.querySelector('[data-settings-subtitle]');
                    const syncHeader = (tab) => {
                        if (!tab || !tab.dataset.settingsTitle) return;
                        heading.textContent = tab.dataset.settingsTitle;
                        crumb.textContent = tab.dataset.settingsGroupName;
                        desc.textContent = tab.dataset.settingsDesc;
                    };
                    syncHeader(document.querySelector('.settings-tabs .active[data-bs-toggle="tab"]'));

                    tabs.forEach((tab) => {
                        tab.addEventListener('shown.bs.tab', (event) => {
                            const url = new URL(window.location.href);
                            url.searchParams.set('tab', toSlug(event.target.dataset.bsTarget));
                            window.history.replaceState(window.history.state, '', url);
                            syncHeader(event.target);
                        });
                    });

                    const search = document.querySelector('[data-settings-search]');
                    const empty = document.querySelector('[data-settings-empty]');
                    search?.addEventListener('input', () => {
                        const term = search.value.trim().toLowerCase();
                        let shown = 0;
                        document.querySelectorAll('[data-settings-group]').forEach((group) => {
                            let groupShown = 0;
                            group.querySelectorAll('[data-bs-toggle="tab"]').forEach((tab) => {
                                const match = !term || (tab.dataset.settingsTitle + ' ' + tab.dataset.settingsGroupName + ' ' + tab.dataset.settingsDesc).toLowerCase().includes(term);
                                tab.hidden = !match;
                                groupShown += match ? 1 : 0;
                            });
                            group.hidden = groupShown === 0;
                            shown += groupShown;
                        });
                        empty.hidden = shown > 0;
                    });
                })();
            </script>
        @endpush
    </div>
    <x-qz-print />
</x-app-layout>
