{{--
    Tenant Control → add / edit tenant modal. Scoped ".tmx" skin in the same
    family as the tenant view (.svx): gradient header, soft field tiles, live
    address preview, tap-to-choose status. Theme-var driven, dark-mode aware.
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
                .tmx .tm-sec { margin-bottom: 18px; }
                .tmx .tm-sec-h { display: flex; align-items: center; gap: 9px; margin-bottom: 10px; }
                .tmx .tm-sec-h .n { width: 22px; height: 22px; border-radius: 7px; display: grid; place-items: center; font-size: 11px; font-weight: 700;
                    background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); flex: none; }
                .tmx .tm-sec-h h6 { margin: 0; font-size: 13px; font-weight: 600; color: var(--ink); }
                .tmx .tm-sec-h .hint { font-size: 11.5px; color: var(--mut); margin-inline-start: auto; }

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

                .tmx .tm-preview { display: flex; align-items: center; gap: 10px; margin-top: 10px; padding: 10px 12px; border-radius: 12px;
                    background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); font-size: 12.5px; }
                .tmx .tm-preview b { font-family: var(--mono); font-weight: 600; overflow-wrap: anywhere; }

                .tmx .tm-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 99px; font-size: 11.5px; font-weight: 500;
                    background: var(--soft); color: var(--ink); border: 1px solid var(--ln); }
                .tmx .tm-chip.ok { background: var(--bs-success-bg-subtle); border-color: var(--bs-success-border-subtle); color: var(--bs-success-text-emphasis); }
                .tmx .tm-chip.wait { background: var(--bs-warning-bg-subtle); border-color: var(--bs-warning-border-subtle); color: var(--bs-warning-text-emphasis); }
                .tmx .tm-chip.bad { background: var(--bs-danger-bg-subtle); border-color: var(--bs-danger-border-subtle); color: var(--bs-danger-text-emphasis); }

                .tmx .tm-choice { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                .tmx .tm-opt { position: relative; display: flex; gap: 11px; align-items: flex-start; padding: 12px 14px; border: 1.5px solid var(--ln);
                    border-radius: 14px; cursor: pointer; background: var(--bs-body-bg); transition: border-color .15s, background .15s, box-shadow .15s; }
                .tmx .tm-opt input { position: absolute; opacity: 0; pointer-events: none; }
                .tmx .tm-opt .dot { width: 30px; height: 30px; border-radius: 10px; display: grid; place-items: center; flex: none; font-size: 13px;
                    background: var(--soft); color: var(--mut); transition: background .15s, color .15s; }
                .tmx .tm-opt b { display: block; font-size: 13px; color: var(--ink); }
                .tmx .tm-opt span { font-size: 11.5px; color: var(--mut); }
                .tmx .tm-opt:hover { border-color: color-mix(in srgb, var(--acc) 40%, var(--ln)); }
                .tmx .tm-opt.on-active { border-color: var(--bs-success); background: color-mix(in srgb, var(--bs-success) 6%, var(--bs-body-bg)); }
                .tmx .tm-opt.on-active .dot { background: var(--bs-success); color: #fff; }
                .tmx .tm-opt.on-inactive { border-color: var(--bs-secondary); background: color-mix(in srgb, var(--bs-secondary) 7%, var(--bs-body-bg)); }
                .tmx .tm-opt.on-inactive .dot { background: var(--bs-secondary); color: #fff; }
                .tmx .tm-opt:focus-within { box-shadow: 0 0 0 3px color-mix(in srgb, var(--acc) 18%, transparent); }

                .tmx .tm-foot { display: flex; align-items: center; gap: 8px; padding: 14px 22px; border-top: 1px solid var(--ln); background: var(--soft); flex-wrap: wrap; }
                .tmx .tm-foot .btn { border-radius: 10px; font-weight: 600; font-size: 13px; padding: 8px 16px; }
                .tmx .tm-foot .btn-link { color: var(--mut); text-decoration: none; padding-inline: 6px; }
                .tmx .tm-foot .btn-link:hover { color: var(--ink); }

                @media (max-width: 575.98px) {
                    .tmx .tm-choice { grid-template-columns: 1fr; }
                    .tmx .tm-body, .tmx .tm-head, .tmx .tm-foot { padding-inline: 16px; }
                    .tmx .tm-foot .btn-primary { flex: 1; }
                }
            </style>
        @endpush
    @endonce

    <div class="tm-head">
        <div class="tm-ico"><i class="fa {{ $isEdit ? 'fa-pencil' : 'fa-building' }}"></i></div>
        <div style="position: relative; z-index: 1; min-width: 0">
            <h1 class="tm-title">{{ $isEdit ? 'Edit tenant' : 'New tenant' }}</h1>
            <div class="tm-sub text-truncate">
                @if ($isEdit)
                    {{ $tenants['name'] ?? '' }} · #{{ $tenants['code'] ?? '' }}
                @else
                    A new workspace with its own users, data and address
                @endif
            </div>
        </div>
        <button type="button" class="tm-close" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
    </div>

    <form wire:submit="save">
        <div class="tm-body">

            {{-- 1. Identity --}}
            <div class="tm-sec">
                <div class="tm-sec-h">
                    <span class="n">1</span>
                    <h6>Who is it</h6>
                </div>
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="tm-lab" for="tm_name">Name <span class="req">*</span></label>
                        <div @class(['tm-in', 'is-invalid' => $errors->has('tenants.name')])>
                            <i class="fa fa-building"></i>
                            <input id="tm_name" type="text" wire:model="tenants.name" placeholder="e.g. Solan Trading" autocomplete="off" required>
                        </div>
                        @error('tenants.name') <div class="tm-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label class="tm-lab" for="tm_code">Code <span class="req">*</span></label>
                        <div @class(['tm-in', 'is-invalid' => $errors->has('tenants.code')])>
                            <span class="affix">#</span>
                            <input id="tm_code" type="text" class="mono" wire:model="tenants.code" placeholder="SOLAN" autocomplete="off" required>
                        </div>
                        @error('tenants.code')
                            <div class="tm-err">{{ $message }}</div>
                        @else
                            <div class="tm-help">Short unique reference</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- 2. Address --}}
            <div class="tm-sec">
                <div class="tm-sec-h">
                    <span class="n">2</span>
                    <h6>Where it lives</h6>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="tm-lab" for="tm_subdomain">Subdomain <span class="req">*</span></label>
                        <div @class(['tm-in', 'is-invalid' => $errors->has('tenants.subdomain')])>
                            <span class="affix">{{ $scheme }}://</span>
                            <input id="tm_subdomain" type="text" class="mono" wire:model.live.debounce.400ms="tenants.subdomain" placeholder="solan" autocomplete="off" required>
                            <span class="affix">{{ $suffix }}</span>
                        </div>
                        @error('tenants.subdomain') <div class="tm-err">{{ $message }}</div> @enderror
                        <div class="tm-preview">
                            <i class="fa fa-globe"></i>
                            <span>Workspace address <b>{{ $scheme }}://{{ $subdomainPreview }}{{ $suffix }}</b></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="tm-lab d-flex align-items-center gap-2" for="tm_domain">
                            Custom domain <span class="text-body-secondary fw-normal text-lowercase" style="letter-spacing: 0">(optional)</span>
                            @if ($statusChip && filled($tenants['domain'] ?? null))
                                <span class="tm-chip {{ $statusChip[0] }} ms-auto" style="text-transform: none; letter-spacing: 0"><i class="fa {{ $statusChip[1] }}"></i>{{ $statusChip[2] }}</span>
                            @endif
                        </label>
                        <div @class(['tm-in', 'is-invalid' => $errors->has('tenants.domain')])>
                            <i class="fa fa-link"></i>
                            <input id="tm_domain" type="text" class="mono ps-0" wire:model="tenants.domain" placeholder="shop.example.com" autocomplete="off">
                        </div>
                        @error('tenants.domain')
                            <div class="tm-err">{{ $message }}</div>
                        @else
                            <div class="tm-help">
                                Only for the client's own domain. Point its DNS <b>A record</b> to this server; nginx and SSL are set up automatically.
                                Never use <span class="font-monospace">{{ $appHost }}</span>.
                            </div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- 3. Status --}}
            <div class="tm-sec">
                <div class="tm-sec-h">
                    <span class="n">3</span>
                    <h6>Status</h6>
                </div>
                <div class="tm-choice" role="radiogroup" aria-label="Status">
                    <label @class(['tm-opt', 'on-active' => (bool) ($tenants['is_active'] ?? false)])>
                        <input type="radio" name="tm_status" wire:model.live="tenants.is_active" value="1" @checked((bool) ($tenants['is_active'] ?? false))>
                        <span class="dot"><i class="fa fa-check"></i></span>
                        <div><b>Active</b><span>Users can sign in and work</span></div>
                    </label>
                    <label @class(['tm-opt', 'on-inactive' => !($tenants['is_active'] ?? false)])>
                        <input type="radio" name="tm_status" wire:model.live="tenants.is_active" value="0" @checked(!($tenants['is_active'] ?? false))>
                        <span class="dot"><i class="fa fa-power-off"></i></span>
                        <div><b>Inactive</b><span>Workspace switched off</span></div>
                    </label>
                </div>
            </div>

            {{-- 4. Notes --}}
            <div class="tm-sec">
                <div class="tm-sec-h">
                    <span class="n">4</span>
                    <h6>Notes</h6>
                    <span class="hint">Only super admins see this</span>
                </div>
                <div class="tm-in">
                    <textarea id="tm_description" wire:model="tenants.description" rows="2" placeholder="Contract, contact person, anything worth remembering"></textarea>
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
