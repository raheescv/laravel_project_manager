<div class="brx">
    <style>
        /* ════════════  Branch form — "Premium" design system (scoped under .brx)  ════════════
           Colour derives from the active settings theme (--bs-primary) and tracks dark mode,
           mirroring the Employee (.empx) / General Voucher (.gvx) Premium systems. Font Awesome 4 only. */

        #BranchModal .modal-dialog {
            max-width: 680px;
        }

        #BranchModal .modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 28px 70px -24px rgba(16, 24, 40, .55);
        }

        [data-bs-theme="dark"] #BranchModal .modal-content {
            background: #272d34;
        }

        .brx {
            --brand: var(--bs-primary, #2563eb);
            --brand-rgb: var(--bs-primary-rgb, 37, 99, 235);
            --brand-600: color-mix(in srgb, var(--brand), #000 12%);
            --brand-700: color-mix(in srgb, var(--brand), #000 28%);
            --brand-400: color-mix(in srgb, var(--brand), #fff 22%);
            --hero-1: color-mix(in srgb, var(--brand), #000 40%);
            --hero-2: color-mix(in srgb, var(--brand), #000 4%);
            --hero-3: color-mix(in srgb, var(--brand), #fff 8%);

            --surface: #ffffff;
            --surface-2: #f5f7fa;
            --surface-3: #eceff4;
            --border: #e4e8ee;
            --border-strong: #d3d9e1;
            --text: #1f2937;
            --text-2: #5b6573;
            --text-3: #8a94a3;

            --success: #059669;
            --success-bg: #ecfdf5;
            --success-rgb: 5, 150, 105;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --danger-rgb: 220, 38, 38;
            --amber: #b45309;
            --amber-bg: #fffbeb;
            --amber-rgb: 180, 83, 9;

            --r-sm: 7px;
            --r-md: 10px;
            --r-lg: 12px;
            --shadow-sm: 0 1px 2px rgba(16, 24, 40, .05), 0 1px 3px rgba(16, 24, 40, .05);

            font-size: 12px;
            color: var(--text);
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
            letter-spacing: -.003em;
        }

        [data-bs-theme="dark"] .brx {
            --hero-1: color-mix(in srgb, var(--brand), #000 60%);
            --hero-2: color-mix(in srgb, var(--brand), #000 44%);
            --hero-3: color-mix(in srgb, var(--brand), #000 26%);
            --surface: #272d34;
            --surface-2: #2e353d;
            --surface-3: #353d46;
            --border: #3a424c;
            --border-strong: #4a535e;
            --text: #e8ebef;
            --text-2: #aab2bd;
            --text-3: #7c8693;
            --success-bg: color-mix(in srgb, var(--success), #000 72%);
            --danger-bg: color-mix(in srgb, var(--danger), #000 72%);
            --amber: #fbbf24;
            --amber-bg: color-mix(in srgb, #b45309, #000 70%);
        }

        /* ═══════════  HERO  ═══════════ */
        .brx-hero {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            padding: 16px 20px;
            color: #fff;
            background:
                radial-gradient(120% 160% at 12% -10%, rgba(255, 255, 255, .20), transparent 50%),
                radial-gradient(90% 140% at 100% 0%, var(--hero-3), transparent 55%),
                linear-gradient(118deg, var(--hero-1) 0%, var(--hero-2) 60%, var(--hero-3) 130%);
        }

        .brx-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            opacity: .5;
            background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .10) 1px, transparent 0);
            background-size: 22px 22px;
            -webkit-mask-image: linear-gradient(180deg, #000, transparent 80%);
            mask-image: linear-gradient(180deg, #000, transparent 80%);
        }

        .brx-glow {
            position: absolute;
            z-index: -1;
            border-radius: 50%;
            filter: blur(34px);
        }

        .brx-glow.a {
            width: 220px;
            height: 220px;
            top: -90px;
            right: 6%;
            background: rgba(255, 255, 255, .28);
            opacity: .5;
        }

        .brx-glow.b {
            width: 170px;
            height: 170px;
            bottom: -80px;
            left: -20px;
            background: var(--brand-400);
            opacity: .4;
        }

        .brx-hero-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .brx-hero-id {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brx-hero-ic {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #fff;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .28);
            backdrop-filter: blur(6px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .25);
        }

        .brx-eyebrow {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .78);
        }

        .brx-hero-title {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -.02em;
            margin: 1px 0 0;
            line-height: 1.1;
            color: #fff;
            text-shadow: 0 1px 14px rgba(0, 0, 0, .18);
        }

        .brx-hero-tools {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .brx-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .02em;
            padding: 5px 10px;
            border-radius: 999px;
            line-height: 1;
            color: var(--brand-700);
            background: rgba(255, 255, 255, .95);
            box-shadow: 0 4px 12px -4px rgba(0, 0, 0, .3);
            white-space: nowrap;
        }

        .brx-pill .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .2);
        }

        .brx-x {
            width: 32px;
            height: 32px;
            flex: 0 0 auto;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #fff;
            cursor: pointer;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .26);
            backdrop-filter: blur(6px);
            transition: background .15s ease, transform .15s ease;
        }

        .brx-x:hover {
            background: rgba(255, 255, 255, .28);
        }

        .brx-x:active {
            transform: scale(.94);
        }

        /* ═══════════  BODY  ═══════════ */
        .brx-body {
            padding: 14px 16px;
            background: var(--surface-2);
            max-height: 66vh;
            overflow-y: auto;
        }

        .brx-errors {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            background: var(--danger-bg);
            border: 1px solid rgba(var(--danger-rgb), .28);
            border-radius: var(--r-md);
            padding: 10px 12px;
            margin-bottom: 14px;
        }

        .brx-errors-ic {
            color: var(--danger);
            font-size: 14px;
            margin-top: 1px;
        }

        .brx-errors-title {
            font-weight: 700;
            color: var(--danger);
            font-size: 11.5px;
        }

        .brx-errors-list {
            margin: 3px 0 0;
            padding-left: 16px;
            color: var(--danger);
            font-size: 11px;
        }

        .brx-errors-list li {
            margin-top: 2px;
        }

        /* panel */
        .brx-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 14px;
        }

        .brx-panel:last-child {
            margin-bottom: 0;
        }

        .brx-panel-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 13px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(110deg, rgba(var(--brand-rgb), .07), var(--surface) 60%);
        }

        .brx-panel-ic {
            width: 26px;
            height: 26px;
            flex: 0 0 auto;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(var(--brand-rgb), .12);
            color: var(--brand-600);
            font-size: 12.5px;
        }

        .brx-panel-title {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -.01em;
        }

        .brx-panel-sub {
            font-size: 10px;
            color: var(--text-3);
            margin-top: 1px;
        }

        .brx-panel-body {
            padding: 14px 13px;
        }

        /* field grid */
        .brx-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 13px 12px;
        }

        .brx .c4 {
            grid-column: span 4;
        }

        .brx .c6 {
            grid-column: span 6;
        }

        .brx .c8 {
            grid-column: span 8;
        }

        .brx .c12 {
            grid-column: span 12;
        }

        .brx-label {
            display: block;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--text-3);
            margin-bottom: 5px;
        }

        .brx-label i {
            margin-right: 3px;
            opacity: .8;
        }

        .brx-label .req {
            color: var(--danger);
        }

        .brx-input {
            display: flex;
            align-items: stretch;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-sm);
            overflow: hidden;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .brx-input:focus-within {
            border-color: var(--brand-400);
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .16);
        }

        .brx-input-ic {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            flex: 0 0 auto;
            color: var(--text-3);
            background: var(--surface-2);
            border-right: 1px solid var(--border);
            font-size: 12px;
        }

        .brx-control {
            flex: 1;
            min-width: 0;
            border: none;
            outline: none;
            background: transparent;
            padding: 7px 10px;
            font-size: 12px;
            color: var(--text);
            width: 100%;
        }

        .brx-control::placeholder {
            color: var(--text-3);
        }

        .brx-hint {
            font-size: 10px;
            color: var(--text-3);
            margin-top: 4px;
        }

        /* ═══════════  TOGGLE ROWS  ═══════════ */
        .brx-toggles {
            display: grid;
            gap: 10px;
        }

        .brx-toggle {
            position: relative;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 12px;
            border: 1px solid var(--border);
            border-radius: var(--r-md);
            background: var(--surface);
            cursor: pointer;
            user-select: none;
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }

        .brx-toggle:hover {
            border-color: var(--border-strong);
        }

        .brx-toggle input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .brx-toggle-ic {
            width: 30px;
            height: 30px;
            flex: 0 0 auto;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            background: var(--surface-3);
            color: var(--text-3);
            transition: background .15s ease, color .15s ease;
        }

        .brx-toggle-meta {
            flex: 1;
            min-width: 0;
        }

        .brx-toggle-title {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text);
        }

        .brx-toggle-sub {
            display: block;
            font-size: 10px;
            color: var(--text-3);
            margin-top: 1px;
        }

        .brx-switch {
            position: relative;
            width: 38px;
            height: 21px;
            flex: 0 0 auto;
            border-radius: 999px;
            background: var(--surface-3);
            border: 1px solid var(--border-strong);
            transition: background .18s ease, border-color .18s ease;
        }

        .brx-switch::after {
            content: "";
            position: absolute;
            top: 2px;
            left: 2px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(16, 24, 40, .35);
            transition: transform .18s cubic-bezier(.4, 0, .2, 1);
        }

        .brx-toggle:has(input:checked) {
            border-color: var(--brand);
            background: rgba(var(--brand-rgb), .06);
            box-shadow: 0 4px 12px -8px rgba(var(--brand-rgb), .8);
        }

        .brx-toggle:has(input:checked) .brx-toggle-ic {
            background: rgba(var(--brand-rgb), .14);
            color: var(--brand-600);
        }

        .brx-toggle:has(input:checked) .brx-switch {
            background: var(--brand);
            border-color: var(--brand);
        }

        .brx-toggle:has(input:checked) .brx-switch::after {
            transform: translateX(17px);
        }

        .brx-toggle input:focus-visible ~ .brx-switch {
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .25);
        }

        /* the "hidden from showcase" toggle warns in amber rather than brand blue */
        .brx-toggle.warn:has(input:checked) {
            border-color: rgba(var(--amber-rgb), .55);
            background: var(--amber-bg);
            box-shadow: none;
        }

        .brx-toggle.warn:has(input:checked) .brx-toggle-ic {
            background: rgba(var(--amber-rgb), .16);
            color: var(--amber);
        }

        .brx-toggle.warn:has(input:checked) .brx-switch {
            background: var(--amber);
            border-color: var(--amber);
        }

        .brx-note {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            font-size: 10.5px;
            color: var(--text-2);
            background: var(--surface-2);
            border: 1px dashed var(--border-strong);
            border-radius: var(--r-md);
            padding: 8px 10px;
            margin-top: 10px;
        }

        .brx-note i {
            color: var(--text-3);
            margin-top: 1px;
        }

        /* ═══════════  FOOTER  ═══════════ */
        .brx-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 9px;
            padding: 12px 16px;
            background: var(--surface);
            border-top: 1px solid var(--border);
        }

        .brx-footer-right {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .brx-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid transparent;
            white-space: nowrap;
            transition: background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease, transform .1s ease;
        }

        .brx-btn:active {
            transform: translateY(1px);
        }

        .brx-btn.ghost {
            background: var(--surface);
            border-color: var(--border);
            color: var(--text-2);
        }

        .brx-btn.ghost:hover {
            background: var(--surface-2);
            border-color: var(--border-strong);
            color: var(--text);
        }

        .brx-btn.soft {
            background: var(--success-bg);
            color: var(--success);
            border-color: rgba(var(--success-rgb), .3);
        }

        .brx-btn.soft:hover {
            background: color-mix(in srgb, var(--success-bg), var(--success) 12%);
            border-color: rgba(var(--success-rgb), .5);
        }

        .brx-btn.primary {
            color: #fff;
            border: none;
            background: linear-gradient(120deg, var(--brand), var(--brand-600));
            box-shadow: 0 8px 18px -7px rgba(var(--brand-rgb), .6);
        }

        .brx-btn.primary:hover {
            background: linear-gradient(120deg, var(--brand-600), var(--brand-700));
            box-shadow: 0 10px 22px -7px rgba(var(--brand-rgb), .7);
        }

        .brx-btn:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        /* ═══════════  RESPONSIVE  ═══════════ */
        @media (max-width: 720px) {
            .brx-grid {
                grid-template-columns: 1fr;
            }

            .brx .c4,
            .brx .c6,
            .brx .c8,
            .brx .c12 {
                grid-column: 1 / -1;
            }

            .brx-footer {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .brx-footer-right {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .brx-btn {
                justify-content: center;
            }
        }
    </style>

    <!-- ═══════════  HERO  ═══════════ -->
    <div class="brx-hero">
        <span class="brx-glow a"></span>
        <span class="brx-glow b"></span>
        <div class="brx-hero-row">
            <div class="brx-hero-id">
                <span class="brx-hero-ic">
                    <i class="fa {{ $table_id ? 'fa-building' : 'fa-plus-square-o' }}"></i>
                </span>
                <div>
                    <div class="brx-eyebrow">Outlet</div>
                    <h1 class="brx-hero-title">{{ $table_id ? 'Edit Branch' : 'Add New Branch' }}</h1>
                </div>
            </div>
            <div class="brx-hero-tools">
                <span class="brx-pill">
                    <span class="dot"></span>
                    {{ $table_id ? 'Editing' : 'New' }}
                </span>
                <button type="button" class="brx-x" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <form wire:submit="save">
        <!-- ═══════════  BODY  ═══════════ -->
        <div class="brx-body">
            @if ($this->getErrorBag()->count())
                <div class="brx-errors">
                    <i class="fa fa-exclamation-triangle brx-errors-ic"></i>
                    <div>
                        <div class="brx-errors-title">Please correct the following errors:</div>
                        <ul class="brx-errors-list">
                            @foreach ($this->getErrorBag()->toArray() as $field => $fieldErrors)
                                <li>{{ $fieldErrors[0] }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Branch Details -->
            <div class="brx-panel">
                <div class="brx-panel-head">
                    <span class="brx-panel-ic"><i class="fa fa-building"></i></span>
                    <div>
                        <div class="brx-panel-title">Branch Details</div>
                        <div class="brx-panel-sub">How this outlet is identified across the system</div>
                    </div>
                </div>
                <div class="brx-panel-body">
                    <div class="brx-grid">
                        <div class="c4">
                            <label for="code" class="brx-label"><i class="fa fa-barcode"></i> Code <span class="req">*</span></label>
                            <div class="brx-input">
                                <span class="brx-input-ic"><i class="fa fa-barcode"></i></span>
                                {{ html()->input('code')->value('')->class('brx-control')->required(true)->attribute('wire:model', 'branches.code')->placeholder('BR-001') }}
                            </div>
                        </div>
                        <div class="c8">
                            <label for="name" class="brx-label"><i class="fa fa-tag"></i> Name <span class="req">*</span></label>
                            <div class="brx-input">
                                <span class="brx-input-ic"><i class="fa fa-tag"></i></span>
                                {{ html()->input('name')->value('')->class('brx-control')->required(true)->attribute('wire:model', 'branches.name')->placeholder('Enter branch name') }}
                            </div>
                        </div>
                        <div class="c6">
                            <label for="location" class="brx-label"><i class="fa fa-map-marker"></i> Location</label>
                            <div class="brx-input">
                                <span class="brx-input-ic"><i class="fa fa-map-marker"></i></span>
                                {{ html()->input('location')->value('')->class('brx-control')->attribute('wire:model', 'branches.location')->placeholder('Mall / area / city') }}
                            </div>
                            <div class="brx-hint">Shown to customers as the shop name in the showcase.</div>
                        </div>
                        <div class="c6">
                            <label for="mobile" class="brx-label"><i class="fa fa-phone"></i> Mobile</label>
                            <div class="brx-input">
                                <span class="brx-input-ic"><i class="fa fa-phone"></i></span>
                                {{ html()->input('mobile')->value('')->class('brx-control')->attribute('wire:model', 'branches.mobile')->placeholder('Contact number') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visibility & Sync -->
            <div class="brx-panel">
                <div class="brx-panel-head">
                    <span class="brx-panel-ic"><i class="fa fa-sliders"></i></span>
                    <div>
                        <div class="brx-panel-title">Visibility &amp; Sync</div>
                        <div class="brx-panel-sub">Where this branch appears and what it syncs with</div>
                    </div>
                </div>
                <div class="brx-panel-body">
                    <div class="brx-toggles">
                        <label class="brx-toggle warn">
                            {{ html()->checkbox('exclude_from_showcase')->attribute('wire:model', 'branches.exclude_from_showcase')->id('exclude_from_showcase') }}
                            <span class="brx-toggle-ic"><i class="fa fa-eye-slash"></i></span>
                            <span class="brx-toggle-meta">
                                <span class="brx-toggle-title">Exclude from Showcase</span>
                                <span class="brx-toggle-sub">Hide this branch from the public catalog API — the showcase app and website will not list it</span>
                            </span>
                            <span class="brx-switch"></span>
                        </label>

                        <label class="brx-toggle">
                            {{ html()->checkbox('moq_sync')->attribute('wire:model', 'branches.moq_sync')->id('moq_sync') }}
                            <span class="brx-toggle-ic"><i class="fa fa-refresh"></i></span>
                            <span class="brx-toggle-meta">
                                <span class="brx-toggle-title">MOQ Sync</span>
                                <span class="brx-toggle-sub">Synchronize this branch with the MOQ service</span>
                            </span>
                            <span class="brx-switch"></span>
                        </label>
                    </div>

                    <div class="brx-note">
                        <i class="fa fa-info-circle"></i>
                        <span>Excluding a branch only affects customer-facing listings. Staff apps, stock and reporting keep using it as normal.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════  FOOTER  ═══════════ -->
        <div class="brx-footer">
            <button type="button" class="brx-btn ghost" data-bs-dismiss="modal">
                <i class="fa fa-times"></i> Cancel
            </button>
            <div class="brx-footer-right">
                @if (!$table_id)
                    <button type="button" wire:click="save(1)" class="brx-btn soft" wire:loading.attr="disabled" wire:target="save">
                        <i class="fa fa-plus"></i> Save &amp; Add New
                    </button>
                @endif
                <button type="submit" class="brx-btn primary" wire:loading.attr="disabled" wire:target="save">
                    <i class="fa fa-check" wire:loading.remove wire:target="save"></i>
                    <i class="fa fa-spinner fa-spin" wire:loading wire:target="save"></i>
                    {{ $table_id ? 'Update Branch' : 'Save Branch' }}
                </button>
            </div>
        </div>
    </form>
</div>
