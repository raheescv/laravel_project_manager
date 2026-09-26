{{--
    Tenant Control → add / edit tenant modal. Scoped ".tmx" skin in the same
    family as the tenant view (.svx), compact: gradient header, paired fields,
    inline address preview, segmented status. Theme-var driven, dark-mode aware.
    Font Awesome 4.3 icons only.
--}}
@php
    $isEdit = isset($tenants['id']);
    $subdomainPreview = trim((string) ($tenants['subdomain'] ?? '')) ?: 'your-subdomain';
    $domainStatus = $tenants['domain_status'] ?? null;
    $statusChip = [
        \App\Models\Tenant::DOMAIN_ACTIVE => ['ok', 'fa-lock', 'Live with SSL'],
        \App\Models\Tenant::DOMAIN_PENDING => ['wait', 'fa-clock-o', 'Waiting for the server'],
        \App\Models\Tenant::DOMAIN_FAILED => ['bad', 'fa-exclamation-triangle', 'Setup failed'],
    ][$domainStatus] ?? null;
@endphp
<div class="tmx">
    @once
        @push('styles')
            <style>
                .tmx {
                    --acc: var(--bs-primary);
                    --hero-1: color-mix(in srgb, var(--bs-primary), #000 42%);
                    --hero-2: color-mix(in srgb, var(--bs-primary), #000 4%);
                    --ln: #e4e8ee;
                    --soft: #f5f7fa;
                    --mut: var(--bs-secondary-color);
                    --ink: var(--bs-emphasis-color);
                    --mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                }
                [data-bs-theme="dark"] .tmx {
                    --hero-1: color-mix(in srgb, var(--bs-primary), #000 64%);
                    --hero-2: color-mix(in srgb, var(--bs-primary), #000 48%);
                    --ln: #3a424c;
                    --soft: #2e353d;
                }
                #TenantModal .modal-content { border: 0; border-radius: 20px; overflow: hidden; box-shadow: 0 30px 70px -25px rgba(15, 23, 42, .55); }

                .tmx .tm-head { position: relative; display: flex; align-items: center; gap: 14px; padding: 20px 22px; color: #fff;
                    background: linear-gradient(118deg, var(--hero-1) 0%, var(--hero-2) 100%); }
                .tmx .tm-head::after { content: ""; position: absolute; inset: 0; pointer-events: none; opacity: .5;
                    background: radial-gradient(circle at 90% -20%, rgba(255, 255, 255, .28), transparent 45%); }
                .tmx .tm-ico { width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; font-size: 19px; flex: none;
                    background: rgba(255, 255, 255, .16); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .28); }
                .tmx .tm-title { font-size: 17px; font-weight: 700; letter-spacing: -.01em; margin: 0; color: #fff; }
                .tmx .tm-sub { font-size: 12px; color: rgba(255, 255, 255, .75); margin-top: 2px; }
                .tmx .tm-close { position: relative; z-index: 1; margin-inline-start: auto; width: 34px; height: 34px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, .25);
                    background: rgba(255, 255, 255, .1); color: #fff; display: grid; place-items: center; transition: background .15s; }
                .tmx .tm-close:hover { background: rgba(255, 255, 255, .22); }

                .tmx .tm-body { padding: 20px 22px 6px; }

                .tmx .tm-lab { display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--mut); margin-bottom: 5px; }
                .tmx .tm-lab .req { color: var(--bs-danger); }
                .tmx .tm-in { display: flex; align-items: center; border: 1px solid var(--ln); border-radius: 12px; background: var(--bs-body-bg);
                    transition: border-color .15s, box-shadow .15s; overflow: hidden; }
                .tmx .tm-in:focus-within { border-color: var(--acc); box-shadow: 0 0 0 3px color-mix(in srgb, var(--acc) 18%, transparent); }
                .tmx .tm-in.is-invalid { border-color: var(--bs-danger); }
                .tmx .tm-in > i { width: 38px; text-align: center; color: var(--mut); flex: none; }
                .tmx .tm-in input, .tmx .tm-in textarea { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; padding: 10px 12px 10px 0; font-size: 14px; color: var(--ink); }
                .tmx .tm-in textarea { padding-inline-start: 12px; resize: vertical; min-height: 70px; }
                .tmx .tm-in .affix { padding: 0 12px; align-self: stretch; display: flex; align-items: center; font-family: var(--mono); font-size: 12.5px;
                    color: var(--mut); background: var(--soft); white-space: nowrap; }
                .tmx .tm-in .affix:first-child { border-inline-end: 1px solid var(--ln); }
                .tmx .tm-in .affix:last-child { border-inline-start: 1px solid var(--ln); }
                .tmx .tm-in input.mono { font-family: var(--mono); font-size: 13.5px; padding-inline-start: 12px; }
                .tmx .tm-err { font-size: 11.5px; color: var(--bs-danger-text-emphasis); margin-top: 5px; }
                .tmx .tm-help { font-size: 11.5px; color: var(--mut); margin-top: 5px; }


                .tmx .tm-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 99px; font-size: 11.5px; font-weight: 500;
                    background: var(--soft); color: var(--ink); border: 1px solid var(--ln); }
                .tmx .tm-chip.ok { background: var(--bs-success-bg-subtle); border-color: var(--bs-success-border-subtle); color: var(--bs-success-text-emphasis); }
                .tmx .tm-chip.wait { background: var(--bs-warning-bg-subtle); border-color: var(--bs-warning-border-subtle); color: var(--bs-warning-text-emphasis); }
                .tmx .tm-chip.bad { background: var(--bs-danger-bg-subtle); border-color: var(--bs-danger-border-subtle); color: var(--bs-danger-text-emphasis); }


                .tmx .tm-foot { display: flex; align-items: center; gap: 8px; padding: 14px 22px; border-top: 1px solid var(--ln); background: var(--soft); flex-wrap: wrap; }
                .tmx .tm-foot .btn { border-radius: 10px; font-weight: 600; font-size: 13px; padding: 8px 16px; }
                .tmx .tm-foot .btn-link { color: var(--mut); text-decoration: none; padding-inline: 6px; }
                .tmx .tm-foot .btn-link:hover { color: var(--ink); }

                @media (max-width: 575.98px) {
                    .tmx .tm-body, .tmx .tm-head, .tmx .tm-foot { padding-inline: 16px; }
                    .tmx .tm-foot .btn-primary { flex: 1; }
                }
                /* Compact density */
                .tmx .tm-head { padding: 12px 16px; gap: 11px; }
                .tmx .tm-ico { width: 36px; height: 36px; border-radius: 11px; font-size: 15px; }
                .tmx .tm-title { font-size: 15px; }
                .tmx .tm-sub { font-size: 11.5px; margin-top: 0; }
                .tmx .tm-close { width: 30px; height: 30px; border-radius: 9px; }
                .tmx .tm-body { padding: 14px 16px 2px; }
                .tmx .tm-lab { font-size: 10px; margin-bottom: 4px; }
                .tmx .tm-in { border-radius: 10px; }
                .tmx .tm-in > i { width: 32px; font-size: 13px; }
                .tmx .tm-in input { padding: 7px 10px 7px 0; font-size: 13px; }
                .tmx .tm-in input.mono { font-size: 12.5px; padding-inline-start: 10px; }
                .tmx .tm-in textarea { min-height: 0; padding: 7px 10px; font-size: 13px; }
                .tmx .tm-in .affix { padding: 0 9px; font-size: 11.5px; }
                .tmx .tm-help, .tmx .tm-err { font-size: 11px; margin-top: 4px; line-height: 1.35; }
                .tmx .tm-help b.addr { font-family: var(--mono); font-weight: 600; color: var(--bs-primary-text-emphasis); overflow-wrap: anywhere; }
                .tmx .tm-seg { display: flex; padding: 3px; gap: 3px; border: 1px solid var(--ln); border-radius: 10px; background: var(--soft); }
                .tmx .tm-seg label { flex: 1; margin: 0; display: flex; align-items: center; justify-content: center; gap: 6px; height: 29px; border-radius: 7px;
                    font-size: 12.5px; font-weight: 600; color: var(--mut); cursor: pointer; transition: background .15s, color .15s, box-shadow .15s; }
                .tmx .tm-seg input { position: absolute; opacity: 0; pointer-events: none; }
                .tmx .tm-seg label:hover { color: var(--ink); }
                .tmx .tm-seg label.on-active { background: var(--bs-success); color: #fff; box-shadow: 0 2px 6px -2px rgba(var(--bs-success-rgb), .6); }
                .tmx .tm-seg label.on-inactive { background: var(--bs-secondary); color: #fff; }
                .tmx .tm-seg label:focus-within { outline: 2px solid color-mix(in srgb, var(--acc) 40%, transparent); }
                .tmx .tm-foot { padding: 10px 16px; }
                .tmx .tm-foot .btn { padding: 6px 14px; font-size: 12.5px; }
            </style>
        @endpush
    @endonce

    <div class="tm-head">
        <div class="tm-ico"><i class="fa {{ $isEdit ? 'fa-pencil' : 'fa-building' }}"></i></div>
        <div style="position: relative; z-index: 1; min-width: 0">
            <h1 class="tm-title">{{ $isEdit ? 'Edit tenant' : 'New tenant' }}</h1>
            <div class="tm-sub text-truncate">
                {{ $isEdit ? ($tenants['name'] ?? '') . ' · #' . ($tenants['code'] ?? '') : 'Its own users, data and address' }}
            </div>
        </div>
        <button type="button" class="tm-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
    </div>

    <form wire:submit="save">
        <div class="tm-body">
            <div class="row g-2 mb-2">
                <div class="col-sm-5">
                    <label class="tm-lab" for="tm_name">Name <span class="req">*</span></label>
                    <div @class(['tm-in', 'is-invalid' => $errors->has('tenants.name')])>
                        <i class="fa fa-building"></i>
                        <input id="tm_name" type="text" wire:model="tenants.name" placeholder="Solan Trading" autocomplete="off" required>
                    </div>
                    @error('tenants.name') <div class="tm-err">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-sm-3">
                    <label class="tm-lab" for="tm_code">Code <span class="req">*</span></label>
                    <div @class(['tm-in', 'is-invalid' => $errors->has('tenants.code')])>
                        <span class="affix">#</span>
                        <input id="tm_code" type="text" class="mono" wire:model="tenants.code" placeholder="SOLAN" autocomplete="off" required>
                    </div>
                    @error('tenants.code') <div class="tm-err">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-sm-4">
                    <span class="tm-lab">Status</span>
                    <div class="tm-seg" role="radiogroup" aria-label="Status">
                        <label @class(['on-active' => (bool) ($tenants['is_active'] ?? false)])>
                            <input type="radio" name="tm_status" wire:model.live="tenants.is_active" value="1" @checked((bool) ($tenants['is_active'] ?? false))>
                            <i class="fa fa-check"></i>Active
                        </label>
                        <label @class(['on-inactive' => !($tenants['is_active'] ?? false)])>
                            <input type="radio" name="tm_status" wire:model.live="tenants.is_active" value="0" @checked(!($tenants['is_active'] ?? false))>
                            <i class="fa fa-power-off"></i>Inactive
                        </label>
                    </div>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <label class="tm-lab" for="tm_subdomain">Subdomain <span class="req">*</span></label>
                    <div @class(['tm-in', 'is-invalid' => $errors->has('tenants.subdomain')])>
                        <span class="affix">{{ $scheme }}://</span>
                        <input id="tm_subdomain" type="text" class="mono" wire:model.live.debounce.400ms="tenants.subdomain" placeholder="solan" autocomplete="off" required>
                        <span class="affix">{{ $suffix }}</span>
                    </div>
                    @error('tenants.subdomain')
                        <div class="tm-err">{{ $message }}</div>
                    @else
                        <div class="tm-help text-truncate"><i class="fa fa-globe me-1"></i><b class="addr">{{ $scheme }}://{{ $subdomainPreview }}{{ $suffix }}</b></div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="tm-lab d-flex align-items-center" for="tm_domain">
                        <span>Custom domain</span>
                        @if ($statusChip && filled($tenants['domain'] ?? null))
                            <span class="tm-chip {{ $statusChip[0] }} ms-auto py-0" style="text-transform: none; letter-spacing: 0; font-size: 10.5px"><i class="fa {{ $statusChip[1] }}"></i>{{ $statusChip[2] }}</span>
                        @endif
                    </label>
                    <div @class(['tm-in', 'is-invalid' => $errors->has('tenants.domain')])>
                        <i class="fa fa-link"></i>
                        <input id="tm_domain" type="text" class="mono ps-0" wire:model="tenants.domain" placeholder="Optional · shop.example.com" autocomplete="off">
                    </div>
                    @error('tenants.domain')
                        <div class="tm-err">{{ $message }}</div>
                    @else
                        <div class="tm-help" title="Point its DNS A record to this server; nginx and SSL are set up automatically. Never use {{ $appHost }}.">
                            Client's own domain · DNS A record to this server, SSL is automatic
                        </div>
                    @enderror
                </div>
            </div>

            <div class="mb-2">
                <label class="tm-lab" for="tm_description">Notes <span class="text-lowercase fw-normal" style="letter-spacing: 0">(super admins only)</span></label>
                <div class="tm-in">
                    <textarea id="tm_description" wire:model="tenants.description" rows="1" placeholder="Contract, contact person…"></textarea>
                </div>
                @error('tenants.description') <div class="tm-err">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="tm-foot">
            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
            <div class="ms-auto d-flex gap-2 flex-wrap">
                @unless ($isEdit)
                    <button type="button" wire:click="save(1)" class="btn btn-outline-primary" wire:loading.attr="disabled" wire:target="save">
                        <i class="fa fa-plus me-1"></i>Save &amp; add another
                    </button>
                @endunless
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save"><i class="fa fa-check me-1"></i>{{ $isEdit ? 'Save changes' : 'Create tenant' }}</span>
                    <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i>Saving…</span>
                </button>
            </div>
        </div>
    </form>
</div>
