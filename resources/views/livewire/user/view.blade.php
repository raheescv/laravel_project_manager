{{--
    User / Employee view — "Dossier" premium design system (scope .uvx).
    Identity hero + KPI strip + tab rail; the heavy tabs (inventory, schedule)
    mount the first time they are opened. Colour derives from the active settings
    theme (--bs-primary) and tracks dark mode, mirroring .empx / .cvx / .rvx.
    Font Awesome 4 only.
--}}
@php
    $isEmployee = $user->type == 'employee';
    $notificationsOn =
        (int) $user->is_whatsapp_enabled + (int) $user->is_browser_notification_enabled + (int) $user->is_telegram_enabled;

    $tabs = collect([
        ['key' => 'profile', 'icon' => 'fa-user', 'label' => 'Profile', 'count' => null, 'show' => true],
        ['key' => 'access', 'icon' => 'fa-shield', 'label' => 'Access', 'count' => count($role_names) ?: null, 'show' => true],
        ['key' => 'notifications', 'icon' => 'fa-bell-o', 'label' => 'Notifications', 'count' => $notificationsOn ?: null, 'show' => true],
        ['key' => 'inventory', 'icon' => 'fa-cubes', 'label' => 'Inventory', 'count' => null, 'show' => $isEmployee],
        [
            'key' => 'schedule',
            'icon' => 'fa-calendar-o',
            'label' => 'Schedule',
            'count' => null,
            'show' => $isEmployee && auth()->user()?->can('property appointment.view'),
        ],
    ])->filter(fn($tab) => $tab['show'])->values();
@endphp

<div class="uvx">
    <style>
        /* ════════════  Tokens  ════════════ */
        .uvx {
            --brand: var(--bs-primary, #2563eb);
            --brand-rgb: var(--bs-primary-rgb, 37, 99, 235);
            --brand-600: color-mix(in srgb, var(--brand), #000 18%);
            --brand-700: color-mix(in srgb, var(--brand), #000 34%);
            --hero-1: color-mix(in srgb, var(--brand), #000 48%);
            --hero-2: color-mix(in srgb, var(--brand), #000 18%);
            --tint: color-mix(in srgb, var(--brand), transparent 90%);

            --surface: #ffffff;
            --surface-2: #f7f9fc;
            --border: #e4e9f0;
            --border-soft: #eef1f6;
            --text: #16202e;
            --text-2: #46536a;
            --text-3: #6f7d92;

            --ok: #16a34a;
            --ok-rgb: 22, 163, 74;
            --off: #94a3b8;

            font-size: 13px;
            color: var(--text);
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
        }

        [data-bs-theme="dark"] .uvx {
            /* foreground accent has to flip lighter in dark — it sits on tinted
               dark surfaces (active tab, tags, panel icons), never on white */
            --brand-600: color-mix(in srgb, var(--brand), #fff 30%);
            --hero-1: color-mix(in srgb, var(--brand), #000 62%);
            --hero-2: color-mix(in srgb, var(--brand), #000 38%);
            --tint: color-mix(in srgb, var(--brand), transparent 82%);
            --surface: #272d34;
            --surface-2: #2e353d;
            --border: #3a424c;
            --border-soft: #333b44;
            --text: #e8ebef;
            --text-2: #b5bdc9;
            --text-3: #8a94a3;
        }

        /* ════════════  Breadcrumb  ════════════ */
        .uvx-crumb {
            font-size: 11.5px;
            color: var(--text-3);
            margin-bottom: 12px;
        }

        .uvx-crumb a {
            color: var(--text-3);
            text-decoration: none;
        }

        .uvx-crumb a:hover {
            color: var(--brand);
        }

        .uvx-crumb b {
            color: var(--text);
            font-weight: 650;
        }

        /* ════════════  Hero  ════════════ */
        .uvx-hero {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            border-radius: 18px;
            margin-bottom: 14px;
            background: radial-gradient(120% 160% at 100% 0, color-mix(in srgb, var(--brand) 34%, transparent), transparent 58%),
                linear-gradient(125deg, var(--hero-1), var(--hero-2));
            box-shadow: 0 22px 46px -26px rgba(var(--brand-rgb), .6);
        }

        .uvx-glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(46px);
            opacity: .5;
            pointer-events: none;
            z-index: 0;
        }

        .uvx-glow.a {
            width: 260px;
            height: 260px;
            top: -140px;
            inset-inline-end: -60px;
            background: rgba(255, 255, 255, .3);
        }

        .uvx-glow.b {
            width: 220px;
            height: 220px;
            bottom: -150px;
            inset-inline-start: 12%;
            background: rgba(255, 255, 255, .16);
        }

        .uvx-hero-top {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 18px;
            align-items: center;
            flex-wrap: wrap;
            padding: 20px 22px;
        }

        .uvx-ava {
            position: relative;
            flex: none;
        }

        .uvx-ava img {
            width: 86px;
            height: 86px;
            border-radius: 24px;
            object-fit: cover;
            background: rgba(255, 255, 255, .15);
            border: 1.5px solid rgba(255, 255, 255, .32);
        }

        .uvx-ava .st {
            position: absolute;
            inset-inline-end: -5px;
            bottom: -5px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #fff;
            background: var(--ok);
            border: 3px solid var(--hero-1);
        }

        .uvx-ava .st.off {
            background: #dc2626;
        }

        .uvx-hero-id {
            flex: 1;
            min-width: 220px;
            color: #fff;
        }

        .uvx-eyebrow {
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 2.4px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .72);
        }

        .uvx-name {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -.4px;
            margin: 3px 0 0;
            color: #fff;
        }

        .uvx-chips {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            margin-top: 9px;
        }

        .uvx-chip {
            background: rgba(255, 255, 255, .15);
            border: 1px solid rgba(255, 255, 255, .26);
            color: #fff;
            padding: 4px 11px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 650;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            max-width: 100%;
        }

        .uvx-chip i {
            opacity: .8;
        }

        .uvx-chip span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .uvx-hero-acts {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .uvx-hbtn {
            border: 1px solid rgba(255, 255, 255, .3);
            background: rgba(255, 255, 255, .14);
            color: #fff;
            padding: 9px 15px;
            border-radius: 11px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: .15s;
        }

        .uvx-hbtn:hover {
            background: rgba(255, 255, 255, .24);
            color: #fff;
        }

        .uvx-hbtn.solid {
            background: #fff;
            color: var(--hero-1);
            border-color: #fff;
        }

        .uvx-hbtn.solid:hover {
            background: rgba(255, 255, 255, .88);
            color: var(--hero-1);
        }

        .uvx-hbtn:disabled {
            opacity: .6;
            cursor: default;
        }

        .uvx-stats {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: rgba(255, 255, 255, .16);
            border-top: 1px solid rgba(255, 255, 255, .16);
        }

        .uvx-stat {
            background: linear-gradient(180deg, rgba(255, 255, 255, .06), transparent);
            padding: 11px 18px;
            min-width: 0;
        }

        .uvx-stat .k {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .62);
        }

        .uvx-stat .v {
            font-size: 17px;
            font-weight: 800;
            color: #fff;
            margin-top: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .uvx-stat .v small {
            font-size: 10.5px;
            font-weight: 600;
            opacity: .72;
        }

        @media (max-width: 767.98px) {
            .uvx-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .uvx-name {
                font-size: 21px;
            }
        }

        /* ════════════  Tab rail  ════════════ */
        .uvx-rail {
            display: flex;
            gap: 4px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 5px;
            margin-bottom: 14px;
            overflow-x: auto;
        }

        .uvx-rail::-webkit-scrollbar {
            height: 0;
        }

        .uvx-rail button {
            border: 0;
            background: transparent;
            color: var(--text-3);
            padding: 9px 15px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: .15s;
        }

        .uvx-rail button:hover {
            color: var(--text);
            background: var(--surface-2);
        }

        .uvx-rail button.active {
            background: var(--tint);
            color: var(--brand-600);
        }

        .uvx-rail .cnt {
            background: var(--border-soft);
            color: var(--text-3);
            border-radius: 999px;
            font-size: 10px;
            padding: 1px 7px;
            font-weight: 800;
        }

        .uvx-rail button.active .cnt {
            background: var(--brand-600);
            color: #fff;
        }

        /* ════════════  Panels  ════════════ */
        /* NOT overflow:hidden — Tom Select drops its menu outside the panel and it
           would be clipped. Nothing inside paints into the corners anyway. */
        .uvx-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 15px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 12px 28px -20px rgba(16, 24, 40, .22);
        }

        .uvx-ph {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-soft);
        }

        .uvx-ph .ic {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            background: var(--tint);
            color: var(--brand-600);
            flex: none;
        }

        .uvx-ph h2 {
            font-size: 13px;
            font-weight: 750;
            margin: 0;
            color: var(--text);
        }

        .uvx-ph .sub {
            font-size: 10.5px;
            color: var(--text-3);
            margin-top: 1px;
        }

        .uvx-ph .tools {
            margin-inline-start: auto;
            display: flex;
            gap: 6px;
        }

        .uvx-pb {
            padding: 13px 15px;
        }

        /* ════════════  Key / value grid  ════════════ */
        .uvx-kv {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        @media (max-width: 575.98px) {
            .uvx-kv {
                grid-template-columns: 1fr;
            }
        }

        .uvx-kvi {
            background: var(--surface-2);
            border: 1px solid var(--border-soft);
            border-radius: 11px;
            padding: 9px 11px;
            min-width: 0;
        }

        .uvx-kvi .k {
            font-size: 9px;
            font-weight: 750;
            letter-spacing: .7px;
            text-transform: uppercase;
            color: var(--text-3);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .uvx-kvi .v {
            font-size: 13px;
            font-weight: 650;
            margin-top: 4px;
            color: var(--text);
            overflow-wrap: anywhere;
        }

        .uvx-kvi .v.empty {
            color: var(--text-3);
            font-weight: 500;
        }

        /* ════════════  Chips (in-panel)  ════════════ */
        .uvx-label {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
            color: var(--text-3);
            margin-bottom: 8px;
        }

        .uvx-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .uvx-tag {
            background: var(--tint);
            color: var(--brand-600);
            border: 1px solid color-mix(in srgb, var(--brand), transparent 78%);
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .uvx-tag.ok {
            background: rgba(var(--ok-rgb), .11);
            color: var(--ok);
            border-color: rgba(var(--ok-rgb), .28);
        }

        .uvx-tag.mute {
            background: var(--surface-2);
            color: var(--text-3);
            border-color: var(--border-soft);
        }

        .uvx-tag small {
            opacity: .75;
            font-weight: 600;
        }

        /* ════════════  Toggle rows  ════════════ */
        .uvx-tgl {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid var(--border-soft);
        }

        .uvx-tgl:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .uvx-tgl:first-child {
            padding-top: 0;
        }

        .uvx-tgl .ic {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--surface-2);
            border: 1px solid var(--border-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-3);
            font-size: 14px;
            flex: none;
        }

        .uvx-tgl.on .ic {
            background: rgba(var(--ok-rgb), .12);
            border-color: rgba(var(--ok-rgb), .28);
            color: var(--ok);
        }

        .uvx-tgl .tx {
            flex: 1;
            min-width: 0;
        }

        .uvx-tgl .tx b {
            font-size: 12.5px;
            font-weight: 700;
            display: block;
            color: var(--text);
        }

        .uvx-tgl .tx span {
            font-size: 11px;
            color: var(--text-3);
        }

        .uvx .form-check.form-switch {
            padding-inline-start: 2.6rem;
            margin: 0;
            flex: none;
        }

        .uvx .form-switch .form-check-input {
            width: 2.5rem;
            height: 1.4rem;
            margin-inline-start: -2.6rem;
            cursor: pointer;
        }

        .uvx .form-switch .form-check-input:checked {
            background-color: var(--ok);
            border-color: var(--ok);
        }

        .uvx .form-switch .form-check-input:focus {
            box-shadow: 0 0 0 .2rem rgba(var(--ok-rgb), .18);
        }

        /* ════════════  Buttons  ════════════ */
        .uvx-btn {
            border: 1px solid var(--border);
            background: var(--surface-2);
            color: var(--text-2);
            border-radius: 10px;
            padding: 8px 13px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: .15s;
        }

        .uvx-btn:hover {
            border-color: var(--brand);
            color: var(--brand-600);
        }

        .uvx-btn.pri {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .uvx-btn.pri:hover {
            background: var(--brand-600);
            border-color: var(--brand-600);
            color: #fff;
        }

        .uvx-hint {
            font-size: 10.5px;
            color: var(--text-3);
            margin-top: 8px;
            display: flex;
            align-items: flex-start;
            gap: 6px;
        }

        /* TomSelect sits flush with its Save button */
        .uvx-selrow {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        .uvx-selrow>div:first-child {
            flex: 1;
            min-width: 0;
        }

        .uvx .ts-wrapper .ts-control {
            border-radius: 10px;
            border-color: var(--border);
            background: var(--surface-2);
            min-height: 38px;
        }

        .uvx .ts-wrapper.focus .ts-control {
            border-color: var(--brand);
            box-shadow: 0 0 0 .2rem rgba(var(--brand-rgb), .14);
        }

        /* Tom Select ships light-only colours — force ours so dark mode stays legible */
        .uvx .ts-wrapper .ts-control,
        .uvx .ts-wrapper .ts-control>input,
        .uvx .ts-wrapper .ts-control>.item {
            color: var(--text);
        }

        /* multi-select chips carry their own ground, so they need their own ink */
        .uvx .ts-wrapper.multi .ts-control>.item {
            background: var(--tint);
            color: var(--brand-600);
            border: 1px solid color-mix(in srgb, var(--brand), transparent 78%);
            border-radius: 7px;
            font-weight: 650;
        }

        .uvx .ts-wrapper.multi .ts-control>.item .remove {
            border-inline-start-color: color-mix(in srgb, var(--brand), transparent 78%);
            color: inherit;
        }

        .uvx .ts-wrapper {
            position: relative;
            z-index: 3;
        }

        .uvx .ts-dropdown {
            z-index: 30;
            background: var(--surface);
            color: var(--text);
            border-color: var(--border);
            border-radius: 0 0 10px 10px;
            box-shadow: 0 16px 34px -16px rgba(16, 24, 40, .35);
            overflow: hidden;
        }

        .uvx .ts-dropdown .option:hover,
        .uvx .ts-dropdown .active {
            background: var(--tint);
            color: var(--brand-600);
        }

        /* ════════════  Nested Livewire cards re-skin (telegram, inventory)  ════════════ */
        .uvx .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 12px 28px -20px rgba(16, 24, 40, .22);
        }

        .uvx .card-header {
            background: var(--surface-2) !important;
            border-bottom: 1px solid var(--border-soft);
            padding: .72rem .95rem !important;
        }

        .uvx .card-header h5,
        .uvx .card-header h6 {
            font-size: 13px;
            font-weight: 750;
        }

        .uvx .card-body {
            padding: .85rem .95rem;
        }

        .uvx .card-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-2);
        }

        .uvx .table thead th {
            background: var(--surface-2);
            color: var(--text-3);
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .uvx-tabc>.tab-pane {
            outline: none;
        }
    </style>

    <div class="content__boxed">
        <div class="content__wrap py-3">

            <nav class="uvx-crumb" aria-label="breadcrumb">
                <a href="{{ route('dashboard') }}">Home</a> &nbsp;/&nbsp;
                <a href="{{ route('users::index') }}">Users</a> &nbsp;/&nbsp;
                <b>{{ $user->name }}</b>
            </nav>

            {{-- ══════════════════════════════  HERO  ══════════════════════════════ --}}
            <header class="uvx-hero">
                <span class="uvx-glow a"></span><span class="uvx-glow b"></span>

                <div class="uvx-hero-top">
                    <div class="uvx-ava">
                        <img src="{{ $user->photo_url }}" alt="{{ $user->name }}" loading="lazy">
                        <span class="st {{ $user->is_active ? '' : 'off' }}"
                            title="{{ $user->is_active ? 'Active' : 'Disabled' }}">
                            <i class="fa {{ $user->is_active ? 'fa-check' : 'fa-ban' }}"></i>
                        </span>
                    </div>

                    <div class="uvx-hero-id">
                        <div class="uvx-eyebrow">{{ $isEmployee ? 'Employee Record' : 'User Account' }}</div>
                        <h1 class="uvx-name">{{ $user->name }}</h1>
                        <div class="uvx-chips">
                            @if ($isEmployee && $user->code)
                                <span class="uvx-chip"><i class="fa fa-barcode"></i> <span>Code {{ $user->code }}</span></span>
                            @endif
                            @if ($user->designation?->name)
                                <span class="uvx-chip"><i class="fa fa-briefcase"></i> <span>{{ $user->designation->name }}</span></span>
                            @endif
                            @if (getUserRoles($user))
                                <span class="uvx-chip"><i class="fa fa-shield"></i> <span>{{ getUserRoles($user) }}</span></span>
                            @endif
                            @if ($user->branch?->name)
                                <span class="uvx-chip"><i class="fa fa-sitemap"></i> <span>{{ $user->branch->name }}</span></span>
                            @endif
                            @if ($user->email)
                                <span class="uvx-chip"><i class="fa fa-envelope-o"></i> <span>{{ $user->email }}</span></span>
                            @endif
                            @if ($user->mobile)
                                <span class="uvx-chip"><i class="fa fa-phone"></i> <span>{{ $user->mobile }}</span></span>
                            @endif
                            @if ($user->is_admin)
                                <span class="uvx-chip"><i class="fa fa-star"></i> <span>Admin</span></span>
                            @endif
                        </div>
                    </div>

                    <div class="uvx-hero-acts">
                        <button type="button" class="uvx-hbtn solid" id="{{ $isEmployee ? 'EmployeeEdit' : 'UserEdit' }}">
                            <i class="fa fa-pencil"></i> Edit Profile
                        </button>
                        @if ($user->id != auth()->id())
                            @can('user.impersonate')
                                <button type="button" class="uvx-hbtn" wire:click="impersonate" wire:loading.attr="disabled"
                                    title="Temporarily sign in as {{ $user->name }} for {{ \App\Services\ImpersonationService::DURATION_MINUTES }} minutes"
                                    wire:confirm="Sign in as {{ $user->name }}?&#10;&#10;This is a temporary login lasting {{ \App\Services\ImpersonationService::DURATION_MINUTES }} minutes. Everything you do will be recorded against their account.">
                                    <i class="fa fa-user-secret"></i>
                                    <span wire:loading.remove wire:target="impersonate">Impersonate</span>
                                    <span wire:loading wire:target="impersonate">Logging in...</span>
                                </button>
                            @endcan
                        @endif
                    </div>
                </div>

                <div class="uvx-stats">
                    @if ($isEmployee)
                        <div class="uvx-stat">
                            <div class="k">Salary</div>
                            <div class="v">{{ currency($user->salary) }}</div>
                        </div>
                        <div class="uvx-stat">
                            <div class="k">Allowance</div>
                            <div class="v">{{ currency($user->allowance) }}</div>
                        </div>
                        <div class="uvx-stat">
                            <div class="k">HRA</div>
                            <div class="v">{{ currency($user->hra) }}</div>
                        </div>
                        <div class="uvx-stat">
                            <div class="k">Max Discount / Sale</div>
                            <div class="v">{{ (float) $user->max_discount_per_sale }}<small>%</small></div>
                        </div>
                    @else
                        <div class="uvx-stat">
                            <div class="k">Roles</div>
                            <div class="v">{{ count($role_names) }}</div>
                        </div>
                        <div class="uvx-stat">
                            <div class="k">Branches</div>
                            <div class="v">{{ count($branch_ids) }}</div>
                        </div>
                        <div class="uvx-stat">
                            <div class="k">Default Branch</div>
                            <div class="v">{{ $user->branch?->name ?? '—' }}</div>
                        </div>
                        <div class="uvx-stat">
                            <div class="k">Member Since</div>
                            <div class="v">{{ $user->created_at ? systemDate($user->created_at->toDateString()) : '—' }}</div>
                        </div>
                    @endif
                </div>
            </header>

            {{-- ══════════════════════════════  TAB RAIL  ══════════════════════════════ --}}
            <div class="uvx-rail" role="tablist">
                @foreach ($tabs as $tab)
                    <button type="button" role="tab" data-bs-toggle="tab" data-bs-target="#uvx-tab-{{ $tab['key'] }}"
                        wire:click="selectTab('{{ $tab['key'] }}')"
                        class="@if ($selected_tab === $tab['key']) active @endif">
                        <i class="fa {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
                        @if ($tab['count'])
                            <span class="cnt">{{ $tab['count'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="tab-content uvx-tabc">

                {{-- ─────────────  PROFILE  ───────────── --}}
                <div id="uvx-tab-profile" role="tabpanel"
                    class="tab-pane fade @if ($selected_tab === 'profile') show active @endif">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <section class="uvx-panel">
                                <div class="uvx-ph">
                                    <span class="ic"><i class="fa fa-user"></i></span>
                                    <div>
                                        <h2>Personal</h2>
                                        <div class="sub">Identity &amp; contact details</div>
                                    </div>
                                    <div class="tools">
                                        <button type="button" class="uvx-btn"
                                            id="{{ $isEmployee ? 'EmployeeEditPanel' : 'UserEditPanel' }}">
                                            <i class="fa fa-pencil"></i> Edit
                                        </button>
                                    </div>
                                </div>
                                <div class="uvx-pb">
                                    <div class="uvx-kv">
                                        <div class="uvx-kvi">
                                            <div class="k"><i class="fa fa-user fa-fw"></i> Name</div>
                                            <div class="v">{{ $user->name }}</div>
                                        </div>
                                        <div class="uvx-kvi">
                                            <div class="k"><i class="fa fa-envelope-o fa-fw"></i> Email</div>
                                            <div class="v {{ $user->email ? '' : 'empty' }}">{{ $user->email ?: 'Not set' }}</div>
                                        </div>
                                        <div class="uvx-kvi">
                                            <div class="k"><i class="fa fa-phone fa-fw"></i> Mobile</div>
                                            <div class="v {{ $user->mobile ? '' : 'empty' }}">{{ $user->mobile ?: 'Not set' }}</div>
                                        </div>
                                        @if ($isEmployee)
                                            <div class="uvx-kvi">
                                                <div class="k"><i class="fa fa-barcode fa-fw"></i> Code</div>
                                                <div class="v {{ $user->code ? '' : 'empty' }}">{{ $user->code ?: 'Not set' }}</div>
                                            </div>
                                            <div class="uvx-kvi">
                                                <div class="k"><i class="fa fa-map-marker fa-fw"></i> Place</div>
                                                <div class="v {{ $user->place ? '' : 'empty' }}">{{ $user->place ?: 'Not set' }}</div>
                                            </div>
                                            <div class="uvx-kvi">
                                                <div class="k"><i class="fa fa-flag-o fa-fw"></i> Nationality</div>
                                                <div class="v {{ $user->nationality ? '' : 'empty' }}">{{ $user->nationality ?: 'Not set' }}</div>
                                            </div>
                                            <div class="uvx-kvi">
                                                <div class="k"><i class="fa fa-birthday-cake fa-fw"></i> Date of Birth</div>
                                                <div class="v {{ $user->dob ? '' : 'empty' }}">{{ $user->dob ? systemDate($user->dob) : 'Not set' }}</div>
                                            </div>
                                            <div class="uvx-kvi">
                                                <div class="k"><i class="fa fa-calendar fa-fw"></i> Date of Joining</div>
                                                <div class="v {{ $user->doj ? '' : 'empty' }}">{{ $user->doj ? systemDate($user->doj) : 'Not set' }}</div>
                                            </div>
                                        @endif
                                        <div class="uvx-kvi">
                                            <div class="k"><i class="fa fa-briefcase fa-fw"></i> Designation</div>
                                            <div class="v {{ $user->designation?->name ? '' : 'empty' }}">
                                                {{ $user->designation?->name ?: 'Not set' }}</div>
                                        </div>
                                        <div class="uvx-kvi">
                                            <div class="k"><i class="fa fa-sitemap fa-fw"></i> Default Branch</div>
                                            <div class="v {{ $user->branch?->name ? '' : 'empty' }}">
                                                {{ $user->branch?->name ?: 'Not set' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="col-lg-5">
                            @if ($isEmployee)
                                <section class="uvx-panel mb-3">
                                    <div class="uvx-ph">
                                        <span class="ic"><i class="fa fa-money"></i></span>
                                        <div>
                                            <h2>Payroll</h2>
                                            <div class="sub">Compensation &amp; selling limits</div>
                                        </div>
                                    </div>
                                    <div class="uvx-pb">
                                        <div class="uvx-kv">
                                            <div class="uvx-kvi">
                                                <div class="k">Salary</div>
                                                <div class="v">{{ currency($user->salary) }}</div>
                                            </div>
                                            <div class="uvx-kvi">
                                                <div class="k">Allowance</div>
                                                <div class="v">{{ currency($user->allowance) }}</div>
                                            </div>
                                            <div class="uvx-kvi">
                                                <div class="k">HRA</div>
                                                <div class="v">{{ currency($user->hra) }}</div>
                                            </div>
                                            <div class="uvx-kvi">
                                                <div class="k">Max Discount / Sale</div>
                                                <div class="v">{{ (float) $user->max_discount_per_sale }}%</div>
                                            </div>
                                        </div>
                                        <div class="uvx-hint">
                                            <i class="fa fa-info-circle"></i>
                                            <span>Max discount caps what this employee can knock off a single sale.</span>
                                        </div>
                                    </div>
                                </section>
                            @endif

                            <section class="uvx-panel">
                                <div class="uvx-ph">
                                    <span class="ic"><i class="fa fa-eye"></i></span>
                                    <div>
                                        <h2>Snapshot</h2>
                                        <div class="sub">Access &amp; channels at a glance</div>
                                    </div>
                                </div>
                                <div class="uvx-pb">
                                    <div class="uvx-label">Status</div>
                                    <div class="uvx-tags mb-3">
                                        <span class="uvx-tag {{ $user->is_active ? 'ok' : 'mute' }}">
                                            <i class="fa {{ $user->is_active ? 'fa-check-circle' : 'fa-ban' }}"></i>
                                            {{ $user->is_active ? 'Active' : 'Disabled' }}
                                        </span>
                                        <span class="uvx-tag mute"><i class="fa fa-bell-o"></i> {{ $notificationsOn }} of 3 channels on</span>
                                    </div>

                                    <div class="uvx-label">Roles</div>
                                    <div class="uvx-tags mb-3">
                                        @forelse ($role_names as $item)
                                            <span class="uvx-tag">{{ $item }}</span>
                                        @empty
                                            <span class="uvx-tag mute">None assigned</span>
                                        @endforelse
                                    </div>

                                    <div class="uvx-label">Branches</div>
                                    <div class="uvx-tags">
                                        @forelse ($branch_ids as $id)
                                            <span class="uvx-tag {{ $id == $default_branch_id ? 'ok' : 'mute' }}">
                                                @if ($id == $default_branch_id)
                                                    <i class="fa fa-star"></i>
                                                @endif
                                                {{ $branches[$id] ?? $id }}
                                            </span>
                                        @empty
                                            <span class="uvx-tag mute">None assigned</span>
                                        @endforelse
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>

                {{-- ─────────────  ACCESS  ───────────── --}}
                <div id="uvx-tab-access" role="tabpanel"
                    class="tab-pane fade @if ($selected_tab === 'access') show active @endif">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <section class="uvx-panel">
                                <div class="uvx-ph">
                                    <span class="ic"><i class="fa fa-shield"></i></span>
                                    <div>
                                        <h2>Roles</h2>
                                        <div class="sub">Every permission this account holds</div>
                                    </div>
                                </div>
                                <div class="uvx-pb">
                                    <div class="uvx-label">Assigned</div>
                                    <div class="uvx-tags mb-3">
                                        @forelse ($role_names as $item)
                                            <span class="uvx-tag"><i class="fa fa-check"></i> {{ $item }}</span>
                                        @empty
                                            <span class="uvx-tag mute">No roles assigned</span>
                                        @endforelse
                                    </div>

                                    <div class="uvx-label">Manage</div>
                                    <div class="uvx-selrow">
                                        <div wire:ignore>
                                            {{ html()->select('role_id', $roles)->value($role_names)->class('tomSelect')->multiple(true)->attribute('width', '100%')->attribute('wire:model', 'role_names')->id('roles_select') }}
                                        </div>
                                        <button class="uvx-btn pri" type="button" wire:click="saveRoles">
                                            <i class="fa fa-save"></i> Save
                                        </button>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="col-lg-6">
                            <section class="uvx-panel">
                                <div class="uvx-ph">
                                    <span class="ic"><i class="fa fa-sitemap"></i></span>
                                    <div>
                                        <h2>Branches</h2>
                                        <div class="sub">Where this account can operate</div>
                                    </div>
                                </div>
                                <div class="uvx-pb">
                                    <div class="uvx-label">Assigned</div>
                                    <div class="uvx-tags mb-3">
                                        @forelse ($branch_ids as $id)
                                            @if ($id == $default_branch_id)
                                                <span class="uvx-tag ok"><i class="fa fa-star"></i> {{ $branches[$id] ?? $id }}
                                                    <small>default</small></span>
                                            @else
                                                <span class="uvx-tag mute">{{ $branches[$id] ?? $id }}</span>
                                            @endif
                                        @empty
                                            <span class="uvx-tag mute">No branches assigned</span>
                                        @endforelse
                                    </div>

                                    <div class="uvx-label">Manage</div>
                                    <div class="uvx-selrow mb-3">
                                        <div wire:ignore>
                                            {{ html()->select('branch_id', $branches)->value($branch_ids)->class('tomSelect')->multiple(true)->attribute('wire:model', 'branch_ids')->id('branch_ids_select') }}
                                        </div>
                                        <button class="uvx-btn pri" type="button" wire:click="saveBranches">
                                            <i class="fa fa-save"></i> Save
                                        </button>
                                    </div>

                                    <div class="uvx-label">Default Branch</div>
                                    <div wire:ignore>
                                        {{ html()->select('default_branch_id', $default_branch)->value($default_branch_id)->class('select-assigned-branch_id-list')->attribute('width', '100%')->attribute('wire:model', 'default_branch_id')->id('default_branch_select') }}
                                    </div>
                                    <div class="uvx-hint">
                                        <i class="fa fa-info-circle"></i>
                                        <span>The default branch loads first at sign-in. Save the branch list to apply a change.</span>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>

                {{-- ─────────────  NOTIFICATIONS  ───────────── --}}
                <div id="uvx-tab-notifications" role="tabpanel"
                    class="tab-pane fade @if ($selected_tab === 'notifications') show active @endif">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <section class="uvx-panel mb-3">
                                <div class="uvx-ph">
                                    <span class="ic"><i class="fa fa-bell-o"></i></span>
                                    <div>
                                        <h2>Notification Channels</h2>
                                        <div class="sub">How this user is reached</div>
                                    </div>
                                </div>
                                <div class="uvx-pb">
                                    <div class="uvx-tgl {{ $user->is_whatsapp_enabled ? 'on' : '' }}">
                                        <span class="ic"><i class="fa fa-whatsapp"></i></span>
                                        <div class="tx">
                                            <b>WhatsApp notifications</b>
                                            <span>{{ $user->is_whatsapp_enabled ? 'Enabled' : 'Currently off' }}</span>
                                        </div>
                                        <div class="form-check form-switch">
                                            {{ html()->checkbox('is_whatsapp_enabled')->value('')->checked($user->is_whatsapp_enabled)->class('form-check-input')->attribute('wire:click', 'enabledWhatsapp')->id('whatsappSwitch') }}
                                            <label class="form-check-label" for="whatsappSwitch"></label>
                                        </div>
                                    </div>
                                    <div class="uvx-tgl {{ $user->is_browser_notification_enabled ? 'on' : '' }}">
                                        <span class="ic"><i class="fa fa-desktop"></i></span>
                                        <div class="tx">
                                            <b>Browser notifications</b>
                                            <span>{{ $user->is_browser_notification_enabled ? 'Enabled while signed in' : 'Currently off' }}</span>
                                        </div>
                                        <div class="form-check form-switch">
                                            {{ html()->checkbox('is_browser_notification_enabled')->value('')->checked($user->is_browser_notification_enabled)->class('form-check-input')->attribute('wire:click', 'toggleBrowserNotification')->id('browserNotifSwitch') }}
                                            <label class="form-check-label" for="browserNotifSwitch"></label>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="uvx-panel">
                                <div class="uvx-ph">
                                    <span class="ic"><i class="fa fa-lock"></i></span>
                                    <div>
                                        <h2>Account Status</h2>
                                        <div class="sub">Whether this account may sign in</div>
                                    </div>
                                </div>
                                <div class="uvx-pb">
                                    <div class="uvx-tgl {{ $user->is_active ? 'on' : '' }}">
                                        <span class="ic"><i class="fa fa-toggle-on"></i></span>
                                        <div class="tx">
                                            <b>{{ $user->is_active ? 'Active' : 'Disabled' }}</b>
                                            <span>{{ $user->is_active ? 'Can sign in to POS and web' : 'Sign-in is blocked' }}</span>
                                        </div>
                                        <div class="form-check form-switch">
                                            {{ html()->checkbox('is_active')->value('')->checked($user->is_active)->class('form-check-input')->attribute('wire:click', 'activeUser')->id('userStatusSwitch') }}
                                            <label class="form-check-label" for="userStatusSwitch"></label>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="col-lg-6">
                            @livewire('user.telegram-setup', ['userId' => $user->id], key('telegram-setup-' . $user->id))
                        </div>
                    </div>
                </div>

                {{-- ─────────────  INVENTORY  ───────────── --}}
                @if ($isEmployee)
                    <div id="uvx-tab-inventory" role="tabpanel"
                        class="tab-pane fade @if ($selected_tab === 'inventory') show active @endif">
                        @if (isset($loaded_tabs['inventory']))
                            <div class="card">
                                @livewire('user.employee-inventory-list', ['employee_id' => $user->id], key('employee-inventory-list-' . $user->id))
                            </div>
                        @else
                            <div class="uvx-panel">
                                <div class="uvx-pb text-center py-4" style="color:var(--text-3)">
                                    <i class="fa fa-circle-o-notch fa-spin me-2"></i> Loading inventory…
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ─────────────  SCHEDULE  ───────────── --}}
                @if ($isEmployee)
                    @can('property appointment.view')
                        <div id="uvx-tab-schedule" role="tabpanel"
                            class="tab-pane fade @if ($selected_tab === 'schedule') show active @endif">
                            @if (isset($loaded_tabs['schedule']))
                                {{-- the schedule brings its own .apx design system; no outer panel --}}
                                @livewire('property-appointment.employee-schedule', ['userId' => $user->id], key('employee-schedule-' . $user->id))
                            @else
                                <div class="uvx-panel">
                                    <div class="uvx-pb text-center py-4" style="color:var(--text-3)">
                                        <i class="fa fa-circle-o-notch fa-spin me-2"></i> Loading schedule…
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endcan
                @endif

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('click', '#UserEdit, #UserEditPanel', function() {
                    Livewire.dispatch("User-Page-Update-Component", {
                        id: "{{ $user->id }}"
                    });
                });
                $(document).on('click', '#EmployeeEdit, #EmployeeEditPanel', function() {
                    Livewire.dispatch("Employee-Page-Update-Component", {
                        id: "{{ $user->id }}"
                    });
                });
                window.addEventListener('RefreshUserPage', event => {
                    Livewire.dispatch("User-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
