<div class="empx">
    <style>
        /* ════════════  Employee form — "Premium" design system (scoped under .empx)  ════════════
           Colour derives from the active settings theme (--bs-primary) and tracks dark mode,
           mirroring the General Voucher (.gvx) / RentOut (.rvx) Premium systems. Font Awesome 4 only. */

        #EmployeeModal .modal-dialog {
            max-width: 900px;
        }

        #EmployeeModal .modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 28px 70px -24px rgba(16, 24, 40, .55);
        }

        [data-bs-theme="dark"] #EmployeeModal .modal-content {
            background: #272d34;
        }

        .empx {
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

        [data-bs-theme="dark"] .empx {
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
        }

        /* ═══════════  HERO  ═══════════ */
        .empx-hero {
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

        .empx-hero::after {
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

        .empx-glow {
            position: absolute;
            z-index: -1;
            border-radius: 50%;
            filter: blur(34px);
        }

        .empx-glow.a {
            width: 220px;
            height: 220px;
            top: -90px;
            right: 6%;
            background: rgba(255, 255, 255, .28);
            opacity: .5;
        }

        .empx-glow.b {
            width: 170px;
            height: 170px;
            bottom: -80px;
            left: -20px;
            background: var(--brand-400);
            opacity: .4;
        }

        .empx-hero-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .empx-hero-id {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .empx-hero-ic {
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

        .empx-eyebrow {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .78);
        }

        .empx-hero-title {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -.02em;
            margin: 1px 0 0;
            line-height: 1.1;
            color: #fff;
            text-shadow: 0 1px 14px rgba(0, 0, 0, .18);
        }

        .empx-hero-tools {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .empx-pill {
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

        .empx-pill .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .2);
        }

        .empx-x {
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

        .empx-x:hover {
            background: rgba(255, 255, 255, .28);
        }

        .empx-x:active {
            transform: scale(.94);
        }

        /* ═══════════  BODY  ═══════════ */
        .empx-body {
            padding: 14px 16px;
            background: var(--surface-2);
            max-height: 64vh;
            overflow-y: auto;
        }

        .empx-errors {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            background: var(--danger-bg);
            border: 1px solid rgba(var(--danger-rgb), .28);
            border-radius: var(--r-md);
            padding: 10px 12px;
            margin-bottom: 14px;
        }

        .empx-errors-ic {
            color: var(--danger);
            font-size: 14px;
            margin-top: 1px;
        }

        .empx-errors-title {
            font-weight: 700;
            color: var(--danger);
            font-size: 11.5px;
        }

        .empx-errors-list {
            margin: 3px 0 0;
            padding-left: 16px;
            color: var(--danger);
            font-size: 11px;
        }

        .empx-errors-list li {
            margin-top: 2px;
        }

        /* panel */
        .empx-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 14px;
        }

        .empx-panel:last-child {
            margin-bottom: 0;
        }

        .empx-panel-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 13px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(110deg, rgba(var(--brand-rgb), .07), var(--surface) 60%);
        }

        .empx-panel-ic {
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

        .empx-panel-title {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -.01em;
        }

        .empx-panel-sub {
            font-size: 10px;
            color: var(--text-3);
            margin-top: 1px;
        }

        .empx-panel-body {
            padding: 14px 13px;
        }

        /* field grid */
        .empx-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 13px 12px;
        }

        .c4 {
            grid-column: span 4;
        }

        .c6 {
            grid-column: span 6;
        }

        .c8 {
            grid-column: span 8;
        }

        .c12 {
            grid-column: span 12;
        }

        .empx-label {
            display: block;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--text-3);
            margin-bottom: 5px;
        }

        .empx-label i {
            margin-right: 3px;
            opacity: .8;
        }

        .empx-label .req {
            color: var(--danger);
        }

        .empx-label .hint {
            text-transform: none;
            letter-spacing: 0;
            font-weight: 500;
            color: var(--text-3);
        }

        .empx-input {
            display: flex;
            align-items: stretch;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-sm);
            overflow: hidden;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .empx-input:focus-within {
            border-color: var(--brand-400);
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .16);
        }

        .empx-input-ic {
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

        .empx-control {
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

        .empx-control::placeholder {
            color: var(--text-3);
        }

        /* tame native date / number widgets */
        .empx-control::-webkit-calendar-picker-indicator {
            opacity: .55;
            cursor: pointer;
        }

        .empx-control::-webkit-outer-spin-button,
        .empx-control::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .empx-eye {
            flex: 0 0 auto;
            width: 34px;
            border: none;
            border-left: 1px solid var(--border);
            background: var(--surface-2);
            color: var(--text-3);
            cursor: pointer;
            font-size: 12px;
            transition: color .15s ease, background .15s ease;
        }

        .empx-eye:hover {
            color: var(--brand-600);
            background: var(--surface-3);
        }

        /* TomSelect reskin within .empx (designation) */
        .empx .ts-wrapper {
            min-width: 0;
        }

        .empx .ts-control {
            border: 1px solid var(--border) !important;
            border-radius: var(--r-sm) !important;
            background: var(--surface) !important;
            color: var(--text) !important;
            min-height: 36px;
            padding: 4px 9px !important;
            box-shadow: none !important;
            font-size: 12px;
        }

        .empx .ts-wrapper.focus .ts-control {
            border-color: var(--brand-400) !important;
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .16) !important;
        }

        .empx .ts-control .item {
            background: rgba(var(--brand-rgb), .12) !important;
            color: var(--brand-700) !important;
            border-radius: 6px !important;
        }

        .empx .ts-control input::placeholder {
            color: var(--text-3);
        }

        .empx .ts-dropdown {
            border: 1px solid var(--border) !important;
            background: var(--surface) !important;
            color: var(--text) !important;
            border-radius: var(--r-sm) !important;
            box-shadow: 0 16px 40px -16px rgba(16, 24, 40, .4) !important;
        }

        .empx .ts-dropdown .option.active {
            background: rgba(var(--brand-rgb), .12) !important;
            color: var(--brand-700) !important;
        }

        /* role chips */
        .empx-roles {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .empx-role {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px 7px 10px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--surface);
            cursor: pointer;
            user-select: none;
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }

        .empx-role:hover {
            border-color: var(--brand-400);
        }

        .empx-role input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .empx-role-box {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            border-radius: 6px;
            border: 1.5px solid var(--border-strong);
            background: var(--surface);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 10px;
            transition: background .15s ease, border-color .15s ease;
        }

        .empx-role-box i {
            opacity: 0;
            transform: scale(.5);
            transition: opacity .12s ease, transform .12s ease;
        }

        .empx-role-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-2);
            text-transform: capitalize;
        }

        .empx-role:has(input:checked) {
            border-color: var(--brand);
            background: rgba(var(--brand-rgb), .08);
            box-shadow: 0 4px 12px -6px rgba(var(--brand-rgb), .5);
        }

        .empx-role:has(input:checked) .empx-role-box {
            background: var(--brand);
            border-color: var(--brand);
        }

        .empx-role:has(input:checked) .empx-role-box i {
            opacity: 1;
            transform: scale(1);
        }

        .empx-role:has(input:checked) .empx-role-name {
            color: var(--brand-700);
        }

        .empx-empty {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11.5px;
            color: var(--text-2);
            background: var(--surface-2);
            border: 1px dashed var(--border-strong);
            border-radius: var(--r-md);
            padding: 10px 12px;
        }

        /* ═══════════  FOOTER  ═══════════ */
        .empx-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 9px;
            padding: 12px 16px;
            background: var(--surface);
            border-top: 1px solid var(--border);
        }

        .empx-footer-right {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .empx-btn {
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

        .empx-btn:active {
            transform: translateY(1px);
        }

        .empx-btn.ghost {
            background: var(--surface);
            border-color: var(--border);
            color: var(--text-2);
        }

        .empx-btn.ghost:hover {
            background: var(--surface-2);
            border-color: var(--border-strong);
            color: var(--text);
        }

        .empx-btn.soft {
            background: var(--success-bg);
            color: var(--success);
            border-color: rgba(var(--success-rgb), .3);
        }

        .empx-btn.soft:hover {
            background: color-mix(in srgb, var(--success-bg), var(--success) 12%);
            border-color: rgba(var(--success-rgb), .5);
        }

        .empx-btn.primary {
            color: #fff;
            border: none;
            background: linear-gradient(120deg, var(--brand), var(--brand-600));
            box-shadow: 0 8px 18px -7px rgba(var(--brand-rgb), .6);
        }

        .empx-btn.primary:hover {
            background: linear-gradient(120deg, var(--brand-600), var(--brand-700));
            box-shadow: 0 10px 22px -7px rgba(var(--brand-rgb), .7);
        }

        .empx-btn:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        /* ═══════════  RESPONSIVE  ═══════════ */
        @media (max-width: 720px) {
            .empx-grid {
                grid-template-columns: 1fr;
            }

            .c4,
            .c6,
            .c8,
            .c12 {
                grid-column: 1 / -1;
            }

            .empx-footer {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .empx-footer-right {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .empx-btn {
                justify-content: center;
            }
        }
    </style>

    <!-- ═══════════  HERO  ═══════════ -->
    <div class="empx-hero">
        <span class="empx-glow a"></span>
        <span class="empx-glow b"></span>
        <div class="empx-hero-row">
            <div class="empx-hero-id">
                <span class="empx-hero-ic">
                    <i class="fa {{ isset($users['id']) ? 'fa-user' : 'fa-user-plus' }}"></i>
                </span>
                <div>
                    <div class="empx-eyebrow">Team Member</div>
                    <h1 class="empx-hero-title">
                        {{ isset($users['id']) ? 'Edit Employee' : 'Add New Employee' }}
                    </h1>
                </div>
            </div>
            <div class="empx-hero-tools">
                <span class="empx-pill">
                    <span class="dot"></span>
                    {{ isset($users['id']) ? 'Editing' : 'New' }}
                </span>
                <button type="button" class="empx-x" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <form wire:submit="save">
        <!-- ═══════════  BODY  ═══════════ -->
        <div class="empx-body">
            @if ($this->getErrorBag()->count())
                <div class="empx-errors">
                    <i class="fa fa-exclamation-triangle empx-errors-ic"></i>
                    <div>
                        <div class="empx-errors-title">Please correct the following errors:</div>
                        <ul class="empx-errors-list">
                            @foreach ($this->getErrorBag()->toArray() as $field => $errors)
                                <li>{{ $errors[0] }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Personal Information -->
            <div class="empx-panel">
                <div class="empx-panel-head">
                    <span class="empx-panel-ic"><i class="fa fa-address-card"></i></span>
                    <div>
                        <div class="empx-panel-title">Personal Information</div>
                        <div class="empx-panel-sub">Identity & contact details</div>
                    </div>
                </div>
                <div class="empx-panel-body">
                    <div class="empx-grid">
                        <div class="c4">
                            <label for="code" class="empx-label"><i class="fa fa-hashtag"></i> Employee Code</label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-barcode"></i></span>
                                {{ html()->input('code')->value('')->class('empx-control')->attribute('wire:model', 'users.code')->placeholder('EMP-001') }}
                            </div>
                        </div>
                        <div class="c8">
                            <label for="name" class="empx-label"><i class="fa fa-user"></i> Full Name <span class="req">*</span></label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-user"></i></span>
                                {{ html()->input('name')->value('')->class('empx-control')->autofocus()->required(true)->attribute('wire:model', 'users.name')->placeholder('Enter full name') }}
                            </div>
                        </div>
                        <div class="c8" wire:ignore>
                            <label for="designation_id" class="empx-label"><i class="fa fa-id-badge"></i> Designation</label>
                            {{ html()->select('designation_id', [])->value('')->class('select-designation_id')->id('model_designation_id')->placeholder('All') }}
                        </div>
                        <div class="c4">
                            <label for="order_no" class="empx-label"><i class="fa fa-sort-numeric-asc"></i> Order No</label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-sort-numeric-asc"></i></span>
                                {{ html()->number('order_no')->value('')->class('empx-control')->attribute('wire:model', 'users.order_no')->placeholder('0') }}
                            </div>
                        </div>
                        <div class="c6">
                            <label for="email" class="empx-label"><i class="fa fa-envelope"></i> Email Address <span class="req">*</span></label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-envelope"></i></span>
                                {{ html()->email('email')->value('')->class('empx-control')->required(true)->attribute('wire:model', 'users.email')->placeholder('example@company.com') }}
                            </div>
                        </div>
                        <div class="c6">
                            <label for="mobile" class="empx-label"><i class="fa fa-phone"></i> Mobile Number</label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-phone"></i></span>
                                {{ html()->input('mobile')->value('')->class('empx-control')->attribute('wire:model', 'users.mobile')->placeholder('Enter mobile number') }}
                            </div>
                        </div>
                        <div class="c6">
                            <label for="place" class="empx-label"><i class="fa fa-map-marker"></i> Location / Place</label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-map-marker"></i></span>
                                {{ html()->input('place')->value('')->class('empx-control')->attribute('wire:model', 'users.place')->placeholder('Enter location') }}
                            </div>
                        </div>
                        <div class="c6">
                            <label for="nationality" class="empx-label"><i class="fa fa-flag"></i> Nationality</label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-flag"></i></span>
                                {{ html()->input('nationality')->value('')->class('empx-control')->attribute('wire:model', 'users.nationality')->placeholder('Enter nationality') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compensation -->
            <div class="empx-panel">
                <div class="empx-panel-head">
                    <span class="empx-panel-ic"><i class="fa fa-money"></i></span>
                    <div>
                        <div class="empx-panel-title">Compensation Details</div>
                        <div class="empx-panel-sub">Salary, allowances & limits</div>
                    </div>
                </div>
                <div class="empx-panel-body">
                    <div class="empx-grid">
                        <div class="c6">
                            <label for="salary" class="empx-label"><i class="fa fa-dollar"></i> Basic Salary</label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-dollar"></i></span>
                                {{ html()->number('salary')->value('')->class('empx-control number')->attribute('wire:model', 'users.salary')->placeholder('0.00')->attribute('step', '0.01') }}
                            </div>
                        </div>
                        <div class="c6">
                            <label for="allowance" class="empx-label"><i class="fa fa-plus-circle"></i> Allowance</label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-dollar"></i></span>
                                {{ html()->number('allowance')->value('')->class('empx-control number')->attribute('wire:model', 'users.allowance')->placeholder('0.00')->attribute('step', '0.01') }}
                            </div>
                        </div>
                        <div class="c6">
                            <label for="hra" class="empx-label"><i class="fa fa-home"></i> Housing (HRA)</label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-dollar"></i></span>
                                {{ html()->number('hra')->value('')->class('empx-control number')->attribute('wire:model', 'users.hra')->placeholder('0.00')->attribute('step', '0.01') }}
                            </div>
                        </div>
                        <div class="c6">
                            <label for="max_discount_per_sale" class="empx-label"><i class="fa fa-percent"></i> Max Discount Per Sale</label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-percent"></i></span>
                                {{ html()->number('max_discount_per_sale')->value('')->class('empx-control number')->attribute('wire:model', 'users.max_discount_per_sale')->placeholder('0.00')->attribute('step', '0.01')->attribute('min', '0')->attribute('max', '100') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Important Dates -->
            <div class="empx-panel">
                <div class="empx-panel-head">
                    <span class="empx-panel-ic"><i class="fa fa-calendar"></i></span>
                    <div>
                        <div class="empx-panel-title">Important Dates</div>
                        <div class="empx-panel-sub">Birth & joining records</div>
                    </div>
                </div>
                <div class="empx-panel-body">
                    <div class="empx-grid">
                        <div class="c6">
                            <label for="dob" class="empx-label"><i class="fa fa-birthday-cake"></i> Date of Birth</label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-calendar"></i></span>
                                {{ html()->date('dob')->value('')->class('empx-control')->attribute('wire:model', 'users.dob') }}
                            </div>
                        </div>
                        <div class="c6">
                            <label for="doj" class="empx-label"><i class="fa fa-briefcase"></i> Date of Joining</label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-calendar"></i></span>
                                {{ html()->date('doj')->value('')->class('empx-control')->attribute('wire:model', 'users.doj') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Authentication -->
            <div class="empx-panel">
                <div class="empx-panel-head">
                    <span class="empx-panel-ic"><i class="fa fa-lock"></i></span>
                    <div>
                        <div class="empx-panel-title">Authentication</div>
                        <div class="empx-panel-sub">Login credentials & POS PIN</div>
                    </div>
                </div>
                <div class="empx-panel-body">
                    <div class="empx-grid">
                        <div class="c6">
                            <label for="password" class="empx-label">
                                <i class="fa fa-key"></i> Password
                                @if (isset($users['id']))
                                    <span class="hint">(leave blank to keep current)</span>
                                @endif
                            </label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-lock"></i></span>
                                {{ html()->password('password')->value('')->class('empx-control')->attribute('wire:model', 'users.password')->placeholder('Enter password') }}
                                <button type="button" class="empx-eye" tabindex="-1" onclick="empxEye(this,'password')" aria-label="Toggle password">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="c6">
                            <label for="pin" class="empx-label">
                                <i class="fa fa-shield"></i> PIN Code
                                @if (isset($users['id']))
                                    <span class="hint">(leave blank to keep current)</span>
                                @endif
                            </label>
                            <div class="empx-input">
                                <span class="empx-input-ic"><i class="fa fa-shield"></i></span>
                                {{ html()->password('pin')->value('')->class('empx-control')->attribute('wire:model', 'users.pin')->placeholder('Enter PIN') }}
                                <button type="button" class="empx-eye" tabindex="-1" onclick="empxEye(this,'pin')" aria-label="Toggle PIN">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Assignment -->
            <div class="empx-panel">
                <div class="empx-panel-head">
                    <span class="empx-panel-ic"><i class="fa fa-users"></i></span>
                    <div>
                        <div class="empx-panel-title">Role Assignment</div>
                        <div class="empx-panel-sub">Permissions granted to this employee</div>
                    </div>
                </div>
                <div class="empx-panel-body">
                    @if (isset($roles) && count($roles) > 0)
                        <div class="empx-roles">
                            @foreach ($roles as $role)
                                <label class="empx-role">
                                    <input type="checkbox" id="role-{{ $role->id }}" value="{{ $role->name }}" wire:model="selectedRoles">
                                    <span class="empx-role-box"><i class="fa fa-check"></i></span>
                                    <span class="empx-role-name">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="empx-empty">
                            <i class="fa fa-info-circle"></i>
                            No roles are available to assign. Please create roles first.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- ═══════════  FOOTER  ═══════════ -->
        <div class="empx-footer">
            <button type="button" class="empx-btn ghost" data-bs-dismiss="modal">
                <i class="fa fa-times"></i> Cancel
            </button>
            <div class="empx-footer-right">
                @if (!isset($users['id']))
                    <button type="button" wire:click="save(1)" class="empx-btn soft" wire:loading.attr="disabled" wire:target="save">
                        <i class="fa fa-plus"></i> Save &amp; Add New
                    </button>
                @endif
                <button type="submit" class="empx-btn primary" wire:loading.attr="disabled" wire:target="save">
                    <i class="fa fa-check" wire:loading.remove wire:target="save"></i>
                    <i class="fa fa-spinner fa-spin" wire:loading wire:target="save"></i>
                    {{ isset($users['id']) ? 'Update Employee' : 'Save Employee' }}
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            function empxEye(btn, id) {
                var el = document.getElementById(id);
                if (!el) return;
                var show = el.type === 'password';
                el.type = show ? 'text' : 'password';
                var i = btn.querySelector('i');
                if (i) i.className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
            }

            $(document).ready(function() {
                $(document).on('change', '#model_designation_id', function() {
                    @this.set('users.designation_id', $(this).val());
                });
                window.addEventListener('SelectDropDownValues', event => {
                    designation = event.detail[0].designation;
                    var tomSelectInstance = document.querySelector('#model_designation_id').tomselect;
                    if (designation) {
                        @this.set('users.designation_id', designation.id);
                        preselectedData = {
                            id: designation.id,
                            name: designation.name,
                        };
                        tomSelectInstance.addOption(preselectedData);
                        tomSelectInstance.addItem(preselectedData.id);
                    } else {
                        tomSelectInstance.clear();
                    }
                });
            });
        </script>
    @endpush
</div>
