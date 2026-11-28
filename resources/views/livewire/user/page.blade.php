<div class="usx">
    <style>
        /* ════════  User form — scoped under .usx, sibling of the Employee form's .empx system.
           Colour follows the settings theme (--bs-primary) and dark mode. Font Awesome 4 only. ════════ */
        #UserModal .modal-dialog { max-width: 660px; }
        #UserModal .modal-content { border: none; border-radius: 14px; overflow: hidden; box-shadow: 0 28px 70px -24px rgba(16, 24, 40, .55); }
        [data-bs-theme="dark"] #UserModal .modal-content { background: #272d34; }

        .usx {
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
            font-size: 12px;
            color: var(--text);
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
        }

        [data-bs-theme="dark"] .usx {
            --brand-600: color-mix(in srgb, var(--brand), #fff 20%);
            --brand-700: color-mix(in srgb, var(--brand), #fff 35%);
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

        /* hero */
        .usx-hero { position: relative; overflow: hidden; padding: 16px 18px; color: #fff;
            background: radial-gradient(120% 160% at 12% -10%, rgba(255, 255, 255, .2), transparent 50%),
                linear-gradient(118deg, var(--hero-1) 0%, var(--hero-2) 60%, var(--hero-3) 130%); }
        .usx-hero-row { display: flex; align-items: center; gap: 14px; }
        .usx-avatar { position: relative; width: 58px; height: 58px; flex: 0 0 auto; }
        .usx-avatar.is-loading { opacity: .55; }
        .usx-avatar img { width: 58px; height: 58px; object-fit: cover; border-radius: 50%; border: 2px solid rgba(255, 255, 255, .7); background: var(--surface-3); box-shadow: 0 6px 16px -6px rgba(0, 0, 0, .45); }
        .usx-avatar-btn { position: absolute; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border-radius: 50%; color: #fff; cursor: pointer; border: 2px solid #fff; padding: 0; line-height: 1; font-size: .65rem; transition: transform .15s ease; }
        .usx-avatar-btn:hover { transform: scale(1.1); }
        .usx-avatar-btn.edit { right: -3px; bottom: -3px; background: var(--brand); }
        .usx-avatar-btn.remove { right: -3px; top: -3px; background: var(--danger); }
        .usx-avatar-spin { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .usx-hero-copy { flex: 1 1 auto; min-width: 0; }
        .usx-eyebrow { font-size: 9px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: rgba(255, 255, 255, .78); }
        .usx-title { font-size: 17px; font-weight: 800; letter-spacing: -.02em; margin: 1px 0 2px; line-height: 1.15; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .usx-hint-light { font-size: 10px; color: rgba(255, 255, 255, .72); }
        .usx-hint-err { font-size: 10.5px; color: #fff; background: rgba(220, 38, 38, .55); border-radius: 6px; padding: 1px 6px; display: inline-block; }
        .usx-x { width: 32px; height: 32px; flex: 0 0 auto; align-self: flex-start; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; color: #fff; cursor: pointer; background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .26); transition: background .15s ease; }
        .usx-x:hover { background: rgba(255, 255, 255, .28); }

        /* body */
        .usx-body { padding: 12px 14px; background: var(--surface-2); max-height: 66vh; overflow-y: auto; }
        .usx-errors { display: flex; gap: 10px; align-items: flex-start; background: var(--danger-bg); border: 1px solid rgba(var(--danger-rgb), .28); border-radius: var(--r-md); padding: 9px 12px; margin-bottom: 10px; color: var(--danger); }
        .usx-errors ul { margin: 2px 0 0; padding-left: 16px; font-size: 11px; }
        .usx-panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-lg); box-shadow: 0 1px 2px rgba(16, 24, 40, .05); margin-bottom: 10px; }
        .usx-panel:last-child { margin-bottom: 0; }
        .usx-panel-head { display: flex; align-items: center; gap: 9px; padding: 7px 12px; border-bottom: 1px solid var(--border); border-radius: var(--r-lg) var(--r-lg) 0 0; background: linear-gradient(110deg, rgba(var(--brand-rgb), .07), var(--surface) 60%); }
        .usx-panel-ic { width: 22px; height: 22px; flex: 0 0 auto; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(var(--brand-rgb), .12); color: var(--brand-600); font-size: 11px; }
        .usx-panel-title { font-size: 12.5px; font-weight: 700; color: var(--text); }
        .usx-panel-sub { font-size: 10px; color: var(--text-3); margin-left: auto; }
        .usx-panel-body { padding: 10px 12px 12px; }

        /* fields */
        .usx-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px 10px; }
        .usx-grid .full { grid-column: 1 / -1; }
        .usx-label { display: block; font-size: 9.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--text-3); margin-bottom: 3px; }
        .usx-label .req { color: var(--danger); }
        .usx-label .opt { text-transform: none; letter-spacing: 0; font-weight: 500; }
        .usx-input { display: flex; align-items: stretch; background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-sm); transition: border-color .15s ease, box-shadow .15s ease; }
        .usx-input:focus-within { border-color: var(--brand-400); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .16); }
        .usx-input.has-error { border-color: rgba(var(--danger-rgb), .6); }
        .usx-input-ic { display: inline-flex; align-items: center; justify-content: center; width: 30px; flex: 0 0 auto; color: var(--text-3); background: var(--surface-2); border-right: 1px solid var(--border); border-radius: var(--r-sm) 0 0 var(--r-sm); font-size: 12px; }
        .usx-control { flex: 1; min-width: 0; border: none; outline: none; background: transparent; padding: 6px 9px; font-size: 12px; color: var(--text); width: 100%; }
        .usx-control::placeholder { color: var(--text-3); }
        .usx-eye { flex: 0 0 auto; width: 32px; border: none; border-left: 1px solid var(--border); background: var(--surface-2); color: var(--text-3); cursor: pointer; border-radius: 0 var(--r-sm) var(--r-sm) 0; transition: color .15s ease; }
        .usx-eye:hover { color: var(--brand-600); }
        .usx-field-err { color: var(--danger); font-size: 10.5px; margin-top: 3px; }

        /* designation — TomSelect seated inside a .usx-input row */
        .usx-input-select .ts-wrapper { flex: 1 1 auto; min-width: 0; }
        .usx .usx-input-select .ts-control,
        .usx .usx-input-select .ts-wrapper.focus .ts-control { display: flex; align-items: center; flex-wrap: nowrap; min-height: 29px; padding: 0 28px 0 9px !important; border: 0 !important; border-radius: 0 var(--r-sm) var(--r-sm) 0 !important; background: transparent !important; color: var(--text) !important; box-shadow: none !important; font-size: 12px; }
        .usx .usx-input-select .ts-control .item { display: inline-flex; align-items: center; margin: 0 !important; padding: 0 !important; border: 0 !important; background: transparent !important; color: var(--text) !important; font-size: 12px; font-weight: 500; }
        .usx .usx-input-select .ts-control .item .remove { margin-left: 6px; padding: 0 4px !important; border: 0 !important; border-radius: 4px; color: var(--text-3); font-size: 13px; line-height: 1; }
        .usx .usx-input-select .ts-control .item .remove:hover { background: var(--danger-bg) !important; color: var(--danger); }
        .usx .usx-input-select .ts-control input { font-size: 12px; color: var(--text) !important; }
        .usx .ts-dropdown { z-index: 30; margin-top: 4px; border: 1px solid var(--border) !important; background: var(--surface) !important; color: var(--text) !important; border-radius: var(--r-sm) !important; box-shadow: 0 16px 40px -16px rgba(16, 24, 40, .4) !important; }
        .usx .ts-dropdown .option.active, .usx .ts-dropdown .create.active { background: rgba(var(--brand-rgb), .12) !important; color: var(--brand-700) !important; }

        /* roles */
        .usx-roles { display: flex; flex-wrap: wrap; gap: 7px; }
        .usx-role { position: relative; display: inline-flex; align-items: center; gap: 7px; padding: 5px 11px 5px 8px; margin: 0; border: 1px solid var(--border); border-radius: 999px; background: var(--surface); cursor: pointer; user-select: none; transition: border-color .15s ease, background .15s ease; }
        .usx-role:hover { border-color: var(--brand-400); }
        .usx-role input { position: absolute; opacity: 0; width: 0; height: 0; }
        .usx-role-box { width: 16px; height: 16px; flex: 0 0 auto; border-radius: 6px; border: 1.5px solid var(--border-strong); display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; transition: background .15s ease, border-color .15s ease; }
        .usx-role-box i { opacity: 0; transform: scale(.5); transition: opacity .12s ease, transform .12s ease; }
        .usx-role-name { font-size: 12px; font-weight: 600; color: var(--text-2); }
        .usx-role:has(input:checked) { border-color: var(--brand); background: rgba(var(--brand-rgb), .08); }
        .usx-role:has(input:checked) .usx-role-box { background: var(--brand); border-color: var(--brand); }
        .usx-role:has(input:checked) .usx-role-box i { opacity: 1; transform: scale(1); }
        .usx-role:has(input:checked) .usx-role-name { color: var(--brand-700); }
        .usx-role:has(input:focus-visible) { box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .2); }
        .usx-empty { display: flex; align-items: center; gap: 8px; font-size: 11.5px; color: var(--text-2); background: var(--surface-2); border: 1px dashed var(--border-strong); border-radius: var(--r-md); padding: 10px 12px; }

        /* administrator switch */
        .usx-switch-row { display: flex; align-items: center; gap: 12px; margin: 10px 0 0; padding: 8px 12px; border: 1px solid var(--border); border-radius: var(--r-md); background: var(--surface-2); cursor: pointer; transition: border-color .15s ease, background .15s ease; }
        .usx-switch-row:has(input:checked) { border-color: var(--brand); background: rgba(var(--brand-rgb), .08); }
        .usx-switch-copy { flex: 1 1 auto; min-width: 0; }
        .usx-switch-title { font-size: 12.5px; font-weight: 700; color: var(--text); }
        .usx-switch-title i { color: var(--brand); margin-right: 4px; }
        .usx-switch-sub { display: block; font-size: 11px; color: var(--text-2); margin-top: 1px; }
        .usx-switch { position: relative; flex: 0 0 auto; width: 42px; height: 23px; }
        .usx-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
        .usx-switch-track { display: block; width: 42px; height: 23px; border-radius: 999px; background: var(--border-strong); transition: background .15s ease; }
        .usx-switch-track::after { content: ''; position: absolute; top: 3px; left: 3px; width: 17px; height: 17px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(16, 24, 40, .35); transition: transform .15s ease; }
        .usx-switch input:checked + .usx-switch-track { background: var(--brand); }
        .usx-switch input:checked + .usx-switch-track::after { transform: translateX(19px); }

        /* footer */
        .usx-footer { display: flex; align-items: center; justify-content: space-between; gap: 9px; padding: 10px 14px; background: var(--surface); border-top: 1px solid var(--border); }
        .usx-footer-right { display: flex; align-items: center; gap: 8px; }
        .usx-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; font-size: 12px; font-weight: 700; padding: 8px 15px; border-radius: 8px; cursor: pointer; border: 1px solid transparent; white-space: nowrap; transition: background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease; }
        .usx-btn:disabled { opacity: .65; cursor: not-allowed; }
        .usx-btn.ghost { background: var(--surface); border-color: var(--border); color: var(--text-2); }
        .usx-btn.ghost:hover { background: var(--surface-2); color: var(--text); }
        .usx-btn.soft { background: var(--success-bg); color: var(--success); border-color: rgba(var(--success-rgb), .3); }
        .usx-btn.soft:hover { border-color: rgba(var(--success-rgb), .55); }
        .usx-btn.primary { color: #fff; border: none; background: linear-gradient(120deg, var(--brand), var(--brand-600)); box-shadow: 0 8px 18px -7px rgba(var(--brand-rgb), .6); }
        .usx-btn.primary:hover { background: linear-gradient(120deg, var(--brand-600), var(--brand-700)); }

        @media (max-width: 575.98px) {
            .usx-grid { grid-template-columns: 1fr; }
            .usx-panel-sub { display: none; }
            .usx-footer, .usx-footer-right { flex-direction: column-reverse; align-items: stretch; }
        }
    </style>

    @php
        $isEditing = isset($users['id']);
        $previewUrl = $photo
            ? $photo->temporaryUrl()
            : (! empty($users['image'])
                ? asset('storage/' . $users['image'])
                : secure_asset('assets/img/profile-photos/1.png'));
    @endphp

    <!-- Hero: identity + photo -->
    <div class="usx-hero">
        <div class="usx-hero-row">
            <div class="usx-avatar" wire:loading.class="is-loading" wire:target="photo">
                <img src="{{ $previewUrl }}" alt="User photo">
                <label for="user_photo_input" class="usx-avatar-btn edit" title="Upload photo"><i class="fa fa-camera"></i></label>
                @if ($photo || ! empty($users['image']))
                    <button type="button" wire:click="removePhoto" class="usx-avatar-btn remove" title="Remove photo"><i class="fa fa-times"></i></button>
                @endif
                <span wire:loading wire:target="photo" class="usx-avatar-spin"><span class="spinner-border spinner-border-sm text-white"></span></span>
            </div>
            <input type="file" id="user_photo_input" class="d-none" wire:model="photo" accept="image/png,image/jpeg,image/webp">
            <div class="usx-hero-copy">
                <div class="usx-eyebrow">{{ $isEditing ? 'Edit User' : 'New User' }}</div>
                <h1 class="usx-title">{{ $isEditing && filled($users['name'] ?? null) ? $users['name'] : 'Add New User' }}</h1>
                @error('photo')
                    <span class="usx-hint-err"><i class="fa fa-exclamation-circle me-1"></i>{{ $message }}</span>
                @else
                    <span class="usx-hint-light"><i class="fa fa-camera me-1"></i>Photo · JPG / PNG / WEBP, max 5MB</span>
                @enderror
            </div>
            <button type="button" class="usx-x" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
        </div>
    </div>

    <form wire:submit="save">
        <div class="usx-body">
            @if ($this->getErrorBag()->count())
                <div class="usx-errors">
                    <i class="fa fa-exclamation-triangle mt-1"></i>
                    <div>
                        <strong>Please correct the following:</strong>
                        <ul>
                            @foreach ($this->getErrorBag()->toArray() as $fieldErrors)
                                <li>{{ $fieldErrors[0] }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Profile -->
            <div class="usx-panel">
                <div class="usx-panel-head">
                    <span class="usx-panel-ic"><i class="fa fa-user"></i></span>
                    <span class="usx-panel-title">Profile</span>
                    <span class="usx-panel-sub">Who they are & how to reach them</span>
                </div>
                <div class="usx-panel-body">
                    <div class="usx-grid">
                        <div>
                            <label for="name" class="usx-label">Full Name <span class="req">*</span></label>
                            <div class="usx-input @error('users.name') has-error @enderror">
                                <span class="usx-input-ic"><i class="fa fa-user"></i></span>
                                {{ html()->input('name')->value('')->class('usx-control')->required(true)->attribute('wire:model', 'users.name')->placeholder('Enter full name') }}
                            </div>
                        </div>
                        <div wire:ignore>
                            <label for="user_designation_id" class="usx-label">Designation <span class="opt">(optional)</span></label>
                            <div class="usx-input usx-input-select">
                                <span class="usx-input-ic"><i class="fa fa-briefcase"></i></span>
                                {{ html()->select('designation_id', [])->value('')->class('select-designation_id')->id('user_designation_id')->placeholder('Select designation') }}
                            </div>
                        </div>
                        <div>
                            <label for="mobile" class="usx-label">Mobile Number <span class="req">*</span></label>
                            <div class="usx-input @error('users.mobile') has-error @enderror">
                                <span class="usx-input-ic"><i class="fa fa-phone"></i></span>
                                {{ html()->input('mobile')->value('')->class('usx-control')->required(true)->attribute('wire:model', 'users.mobile')->placeholder('Enter mobile number') }}
                            </div>
                        </div>
                        <div>
                            <label for="email" class="usx-label">Email Address <span class="req">*</span></label>
                            <div class="usx-input @error('users.email') has-error @enderror">
                                <span class="usx-input-ic"><i class="fa fa-envelope"></i></span>
                                {{ html()->email('email')->value('')->class('usx-control')->required(true)->attribute('wire:model', 'users.email')->placeholder('example@company.com') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sign-in -->
            <div class="usx-panel">
                <div class="usx-panel-head">
                    <span class="usx-panel-ic"><i class="fa fa-lock"></i></span>
                    <span class="usx-panel-title">Sign-in</span>
                    <span class="usx-panel-sub">Email or username + password</span>
                </div>
                <div class="usx-panel-body">
                    <div class="usx-grid">
                        <div>
                            <label for="username" class="usx-label">Username <span class="opt">(optional)</span></label>
                            <div class="usx-input @error('users.username') has-error @enderror">
                                <span class="usx-input-ic"><i class="fa fa-at"></i></span>
                                {{ html()->input('username')->value('')->class('usx-control')->attribute('wire:model', 'users.username')->attribute('autocomplete', 'off')->attribute('autocapitalize', 'none')->placeholder('e.g. john.doe') }}
                            </div>
                            @error('users.username')
                                <div class="usx-field-err">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="password" class="usx-label">
                                Password
                                @if ($isEditing)
                                    <span class="opt">(blank = keep current)</span>
                                @else
                                    <span class="req">*</span>
                                @endif
                            </label>
                            <div class="usx-input @error('users.password') has-error @enderror" x-data="{ showPassword: false }">
                                <span class="usx-input-ic"><i class="fa fa-key"></i></span>
                                {{ html()->password('password')->value('')->class('usx-control')->attribute('wire:model', 'users.password')->attribute('x-bind:type', "showPassword ? 'text' : 'password'")->attribute('autocomplete', 'new-password')->placeholder($isEditing ? '••••••••' : 'Enter password') }}
                                <button type="button" class="usx-eye" tabindex="-1" x-on:click="showPassword = !showPassword" aria-label="Toggle password">
                                    <i class="fa" x-bind:class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Access -->
            <div class="usx-panel">
                <div class="usx-panel-head">
                    <span class="usx-panel-ic"><i class="fa fa-shield"></i></span>
                    <span class="usx-panel-title">Access</span>
                    <span class="usx-panel-sub">Roles decide what they can do</span>
                </div>
                <div class="usx-panel-body">
                    @if (isset($roles) && count($roles) > 0)
                        <div class="usx-roles">
                            @foreach ($roles as $role)
                                <label class="usx-role">
                                    <input type="checkbox" id="user-role-{{ $role->id }}" value="{{ $role->name }}" wire:model="selectedRoles">
                                    <span class="usx-role-box"><i class="fa fa-check"></i></span>
                                    <span class="usx-role-name">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="usx-empty">
                            <i class="fa fa-info-circle"></i>
                            No roles are available to assign. Please create roles first.
                        </div>
                    @endif

                    @if ($this->canManageAdminFlag())
                        <label class="usx-switch-row">
                            <span class="usx-switch-copy">
                                <span class="usx-switch-title"><i class="fa fa-star"></i>Administrator</span>
                                <span class="usx-switch-sub">Full access across the app, and the mobile app is unrestricted to their own records.</span>
                            </span>
                            <span class="usx-switch">
                                <input type="checkbox" id="is_admin" wire:model="isAdmin">
                                <span class="usx-switch-track"></span>
                            </span>
                        </label>
                    @endif
                </div>
            </div>
        </div>

        <div class="usx-footer">
            <button type="button" class="usx-btn ghost" data-bs-dismiss="modal">
                <i class="fa fa-times"></i> Cancel
            </button>
            <div class="usx-footer-right">
                <button type="button" wire:click="save(1)" class="usx-btn soft" wire:loading.attr="disabled" wire:target="save">
                    <i class="fa fa-plus"></i> Save & Add New
                </button>
                <button type="submit" class="usx-btn primary" wire:loading.attr="disabled" wire:target="save">
                    <i class="fa fa-check" wire:loading.remove wire:target="save"></i>
                    <i class="fa fa-spinner fa-spin" wire:loading wire:target="save"></i>
                    {{ $isEditing ? 'Update User' : 'Save User' }}
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('change', '#user_designation_id', function() {
                    @this.set('users.designation_id', $(this).val() || null, false);
                });
                window.addEventListener('UserDesignationSelected', event => {
                    var designation = event.detail.designation;
                    var tomSelectInstance = document.querySelector('#user_designation_id')?.tomselect;
                    if (!tomSelectInstance) return;
                    if (designation) {
                        tomSelectInstance.addOption({ id: designation.id, name: designation.name });
                        tomSelectInstance.addItem(designation.id, true);
                    } else {
                        tomSelectInstance.clear(true);
                    }
                });
            });
        </script>
    @endpush
</div>
