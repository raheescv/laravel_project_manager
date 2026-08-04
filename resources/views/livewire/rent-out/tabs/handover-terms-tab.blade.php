{{--
    Checklist tab → Handover Terms.

    Bilingual clauses printed under the inventory table of this booking's Unit
    Handover & Snagging checklist. Clause numbers are rendered from position
    (Latin digits on the English side, Arabic-Indic on the Arabic side) — the
    author types the heading only, so inserting a clause never means renumbering.
--}}
<div>
    <style>
        .ht-clause {
            border: 1px solid #e6e8ec;
            border-radius: 10px;
            background: #fff;
            padding: .7rem .8rem;
        }

        .ht-clause+.ht-clause {
            margin-top: .6rem;
        }

        .ht-clause-head {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: .6rem;
        }

        .ht-no {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 24px;
            padding: 0 .35rem;
            border-radius: 999px;
            background: var(--bs-primary, #0d6efd);
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
        }

        .ht-hint {
            font-size: .7rem;
            color: #8a9099;
        }

        .ht-side-t {
            display: block;
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #8a9099;
            margin-bottom: .25rem;
        }

        .ht-band {
            border: 1px solid #e6e8ec;
            border-radius: 10px;
            background: #f8fafc;
            padding: .7rem .8rem;
            margin-bottom: .6rem;
        }
    </style>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div class="small text-muted">
            <i class="fa fa-balance-scale me-1"></i>
            <strong>{{ count($clauses) }}</strong> clause(s) &middot; printed under the inventory table of this booking's handover form
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <button type="button" wire:click="loadSample"
                wire:confirm="Replace the clauses in this form with the sample warranty terms? Nothing is saved until you press Save."
                class="btn btn-outline-secondary d-inline-flex align-items-center"
                style="font-size:.7rem; padding:.2rem .5rem; border-radius:4px;">
                <i class="fa fa-magic me-1"></i> Load Sample
            </button>
            <button type="button" wire:click="addClause"
                class="btn btn-outline-primary d-inline-flex align-items-center"
                style="font-size:.7rem; padding:.2rem .5rem; border-radius:4px;">
                <i class="fa fa-plus me-1"></i> Add Clause
            </button>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="btn btn-outline-success d-inline-flex align-items-center"
                style="font-size:.7rem; padding:.2rem .5rem; border-radius:4px;">
                <i class="fa fa-save me-1"></i> Save
            </button>
        </div>
    </div>

    {{-- Section heading printed as the band above the clauses --}}
    <div class="ht-band">
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label small mb-1 text-muted">Section Heading (English)</label>
                <input type="text" class="form-control form-control-sm" wire:model="heading_en"
                    placeholder="{{ \App\Support\RentOutHandoverTerms::FALLBACK_HEADING }}">
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1 text-muted">Section Heading (Arabic)</label>
                <input type="text" class="form-control form-control-sm" dir="rtl" wire:model="heading_ar"
                    placeholder="شروط الضمان">
            </div>
        </div>
    </div>

    @forelse ($clauses as $key => $clause)
        <div class="ht-clause" wire:key="clause-{{ $key }}">
            <div class="ht-clause-head">
                <span class="ht-no">{{ $loop->iteration }}</span>
                <span class="ht-hint">
                    prints as <strong>{{ $loop->iteration }}.</strong> /
                    <strong>{{ \App\Support\RentOutHandoverTerms::arabicDigits($loop->iteration) }}.</strong>
                </span>
                <button type="button" wire:click="removeClause('{{ $key }}')"
                    class="btn btn-sm btn-outline-danger ms-auto" title="Remove clause" data-bs-toggle="tooltip">
                    <i class="fa fa-trash"></i>
                </button>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <span class="ht-side-t">English</span>
                    <input type="text" class="form-control form-control-sm mb-2"
                        wire:model="clauses.{{ $key }}.title_en" placeholder="Clause heading — e.g. Furniture">
                    <x-rich-text-editor wire:model="clauses.{{ $key }}.body_en"
                        id="rte-{{ $key }}-en" :height="160" placeholder="Clause text in English…" />
                </div>
                <div class="col-lg-6">
                    <span class="ht-side-t">العربية</span>
                    <input type="text" class="form-control form-control-sm mb-2" dir="rtl"
                        wire:model="clauses.{{ $key }}.title_ar" placeholder="عنوان البند — مثال: الأثاث">
                    <x-rich-text-editor wire:model="clauses.{{ $key }}.body_ar"
                        id="rte-{{ $key }}-ar" :height="160" rtl placeholder="نص البند بالعربية…" />
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="fa fa-balance-scale d-block mb-2" style="font-size: 2.5rem; opacity: .3;"></i>
            <p class="mb-1 small">No handover terms yet.</p>
            <p class="mb-0 small">Click <strong>"Add Clause"</strong> to write your own, or <strong>"Load Sample"</strong> to start from the standard warranty terms.</p>
        </div>
    @endforelse

    <p class="small text-muted mt-3 mb-0">
        <i class="fa fa-info-circle me-1"></i>
        A clause left completely empty is dropped on save. Use <strong>RTL</strong> on a paragraph for Arabic
        lines and <strong>HTML</strong> to edit the markup directly. Leave the Arabic side empty and the
        clauses print full width.
    </p>
</div>
