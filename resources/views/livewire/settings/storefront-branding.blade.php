<div class="card shadow-sm border-0">
    <style>
        .sb-hint { font-size: .78rem; color: var(--bs-secondary-color); }
        .sb-hint code { background: var(--bs-tertiary-bg); padding: .05rem .35rem; border-radius: 5px; }
        .sb-swatch { width: 3rem; height: 3rem; border-radius: 12px; border: 1px solid var(--bs-border-color); padding: 0; cursor: pointer; }
        .sb-preview { border: 1px solid var(--bs-border-color); border-radius: 14px; overflow: hidden; }
        .sb-preview__bar { display: flex; align-items: center; gap: .6rem; padding: .8rem 1rem; background: #14151b; }
        .sb-preview__mark { width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; }
        .sb-preview__name { color: #fff; font-weight: 700; letter-spacing: .08em; }
        .sb-preview__body { padding: 1.1rem; background: #f5f6f9; display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
        .sb-preview__btn { border-radius: 99px; padding: .55rem 1.2rem; font-weight: 600; color: #fff; border: none; }
        .sb-preview__ghost { border-radius: 99px; padding: .5rem 1.15rem; font-weight: 600; background: transparent; }
        .sb-preview__tag { font-size: .72rem; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; }
    </style>

    <div class="card-header bg-primary text-white py-2 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 text-white"><i class="fa fa-paint-brush me-1"></i> Storefront Branding</h5>
        <span class="badge bg-light text-primary">Public website</span>
    </div>

    <form wire:submit="save">
        <div class="card-body p-3">
            <p class="sb-hint mb-3">
                The accent color for your public showcase website. Changing it updates buttons, links, highlights
                and hover states across the storefront. The site reads this live from
                <code>GET /api/v1/settings/branding</code>.
            </p>

            <div class="row g-3 align-items-end mb-3">
                <div class="col-auto">
                    <label class="form-label fw-medium small mb-1 d-block">Color</label>
                    <input type="color" class="form-control sb-swatch" wire:model.live="primary_color">
                </div>
                <div class="col-12 col-sm-auto">
                    <label class="form-label fw-medium small mb-1">Hex value</label>
                    <input type="text" class="form-control form-control-sm" style="max-width: 10rem;"
                        wire:model.live="primary_color" placeholder="#1F35E5" maxlength="7">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetToDefault">
                        <i class="fa fa-undo me-1"></i> Reset
                    </button>
                </div>
            </div>

            <label class="form-label fw-medium small mb-1">Live preview</label>
            <div class="sb-preview mb-1">
                <div class="sb-preview__bar">
                    <span class="sb-preview__mark" style="background: {{ $primary_color }};">S</span>
                    <span class="sb-preview__name">SIZE RUN</span>
                </div>
                <div class="sb-preview__body">
                    <span class="sb-preview__tag" style="color: {{ $primary_color }};">New Arrivals</span>
                    <button type="button" class="sb-preview__btn" style="background: {{ $primary_color }};">Shop now</button>
                    <button type="button" class="sb-preview__ghost"
                        style="color: {{ $primary_color }}; border: 1.5px solid {{ $primary_color }};">View details</button>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save"><i class="fa fa-save me-1"></i> Save</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
