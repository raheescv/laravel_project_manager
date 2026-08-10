@php
    use App\Enums\RentOut\AgreementType;
    use App\Enums\RentOut\ChecklistSignatoryRole;
    // Move-Out / damage tracking only applies to rentals — a lease/sale never hands the unit back.
    $showMoveOut = $agreement_type == AgreementType::Rental;
    $colCount = $showMoveOut ? 10 : 7;

    // Split the free width between Item Description and Comments in proportion to the
    // text they actually hold, so neither column sits half empty while the other wraps.
    $lengthOf = function ($values) {
        $lengths = collect($values)->map(fn ($v) => mb_strlen(trim((string) $v)))->filter()->sort()->values();
        if ($lengths->isEmpty()) {
            return 0;
        }
        // 90th percentile, so one rogue paragraph doesn't starve every other column.
        return (int) $lengths[(int) floor(($lengths->count() - 1) * 0.9)];
    };
    // Free width left after the fixed columns (select/qty/image/move-in/trash [+move-out/damage]).
    $freeWidth = $showMoveOut ? 58.0 : 74.0;
    $weigh = fn ($len) => $len <= 0 ? 0.45 : max(0.8, min(3.2, $len / 26));
    $weights = [
        'desc' => $weigh($lengthOf(array_column($lines, 'name'))) * 1.15,
        'in' => $weigh($lengthOf(array_column($lines, 'move_in_comment'))),
    ];
    if ($showMoveOut) {
        $weights['out'] = $weigh($lengthOf(array_column($lines, 'move_out_comment')));
    }
    $weightTotal = array_sum($weights) ?: 1;
    $pct = fn ($k) => round($freeWidth * $weights[$k] / $weightTotal, 1) . '%';
@endphp
{{-- `sub` splits this screen in two on a lease/sale: the inventory grid, and the
     bilingual clauses printed under it on the handover form. A rental has no
     handover terms, so it never sees the rail and the screen is unchanged. --}}
<div class="cl-wrap {{ $showMoveOut ? '' : 'cl-no-mo' }}" x-data="{ sub: 'items' }">
    <style>
        .cl-table {
            border: 1px solid #e6e8ec;
            border-radius: 10px;
            overflow: hidden;
        }

        .cl-table thead th {
            background: #f6f8fb;
            color: #5a626c;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .3px;
            border-bottom: 1px solid #e6e8ec;
            vertical-align: middle;
        }

        .cl-table tbody td {
            vertical-align: middle;
            border-color: #f0f1f4;
        }

        .cl-table tbody tr:hover td {
            background: #fafbfc;
        }

        .cl-cat td {
            background: #eef2f8;
            border-left: 3px solid var(--bs-primary, #0d6efd);
            padding: .35rem .65rem;
        }

        .cl-cat-label {
            font-weight: 700;
            font-size: 11px;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: #3a4250;
        }

        .cl-toggle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1.5px solid #d3d8de;
            background: #fff;
            color: #cbd1d8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all .15s ease;
            cursor: pointer;
        }

        .cl-toggle:hover {
            border-color: #9aa1a9;
            color: #8a929b;
        }

        .cl-toggle.on-ok {
            background: #198754;
            border-color: #198754;
            color: #fff;
        }

        .cl-toggle.on-no {
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }

        .cl-inp {
            height: 28px;
            font-size: 12.5px;
            border-color: #e3e6ea;
            padding: .1rem .3rem;
        }

        /* A textarea sized by rows can't also be locked to the 28px input height. */
        textarea.cl-inp,
        .cl-fx-photo-cell .cl-inp {
            height: auto;
        }

        /* ---- Fixture Comments: one block per area, printed under that area's items ---- */
        .cl-fx-row td {
            background: #fbfcfe !important;
            border-top: 2px solid #e6e8ec;
        }

        .cl-fx-standalone .cl-fx {
            border: 1px solid #e6e8ec;
            border-radius: 10px;
            overflow: hidden;
        }

        .cl-fx-head {
            display: flex;
            align-items: center;
            gap: .4rem;
            flex-wrap: wrap;
            padding: .45rem .7rem;
            background: #f4f7fb;
            border-bottom: 1px solid #e6e8ec;
        }

        .cl-fx-title {
            font-weight: 700;
            font-size: 11.5px;
            letter-spacing: .3px;
            color: #3a4250;
        }

        .cl-fx-ar {
            font-weight: 400;
            color: #8a929b;
            margin-inline-start: .35rem;
        }

        .cl-fx-entry {
            display: grid;
            grid-template-columns: 104px 104px minmax(180px, 1fr) 132px 128px 34px;
            gap: .6rem;
            align-items: start;
            padding: .6rem .7rem;
            border-bottom: 1px solid #eef0f3;
        }

        .cl-fx-entry:last-of-type {
            border-bottom: 0;
        }

        .cl-fx-lbl {
            display: block;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #98a0aa;
            margin-bottom: .15rem;
        }

        .cl-fx-photo {
            position: relative;
            width: 104px;
            height: 76px;
            border-radius: 7px;
            border: 1px solid #e3e6ea;
            background: #f6f8fb;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccd2d9;
        }

        .cl-fx-photo.is-empty {
            border-style: dashed;
        }

        .cl-fx-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cl-fx-btn {
            position: absolute;
            bottom: 3px;
            right: 3px;
            width: 21px;
            height: 21px;
            border-radius: 6px;
            border: 1px solid #e3e6ea;
            background: #fff;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            cursor: pointer;
            padding: 0;
        }

        .cl-fx-btn:hover {
            color: var(--bs-primary, #0d6efd);
            border-color: var(--bs-primary, #0d6efd);
        }

        .cl-fx-btn-del {
            right: 27px;
        }

        .cl-fx-btn-del:hover {
            color: #dc3545;
            border-color: #dc3545;
        }

        .cl-fx-del {
            padding-top: 1.1rem;
        }

        .cl-fx-empty {
            padding: .7rem;
            font-size: 12px;
            font-style: italic;
            color: #98a0aa;
        }

        .cl-fx-foot {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            padding: .4rem .7rem;
            background: #f8fafc;
            border-top: 1px solid #eef0f3;
        }

        .cl-fx-add {
            font-size: .72rem;
            padding: .15rem .55rem;
            color: var(--bs-primary, #0d6efd);
            border: 1px solid #dbe3ee;
            background: #fff;
        }

        .cl-fx-add:hover {
            background: var(--bs-primary, #0d6efd);
            border-color: var(--bs-primary, #0d6efd);
            color: #fff;
        }

        .cl-fx-addarea {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            padding: .55rem .7rem;
            border: 1px dashed #dbe0e6;
            border-radius: 10px;
            background: #fafbfc;
            color: #6c757d;
        }

        @media (max-width: 900px) {
            .cl-fx-entry {
                grid-template-columns: 104px 104px 1fr;
            }

            .cl-fx-cmt {
                grid-column: 1 / -1;
            }
        }

        .cl-inp[type=number] {
            -moz-appearance: textfield;
            appearance: textfield;
        }

        .cl-inp[type=number]::-webkit-outer-spin-button,
        .cl-inp[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .cl-qty {
            width: 46px;
            text-align: center;
            padding-left: .15rem;
            padding-right: .15rem;
        }

        .cl-dmg {
            width: 86px;
            text-align: right;
        }

        .cl-trash {
            color: #c0c5cc;
            border: none;
            background: transparent;
            padding: .2rem .4rem;
        }

        .cl-trash:hover {
            color: #dc3545;
        }

        .cl-legend .dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: #fff;
        }

        .cl-img-cell {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .cl-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e3e6ea;
            background: #fff;
        }

        .cl-thumb-empty {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #cbd1d8;
            font-size: 15px;
        }

        .cl-thumb-wrap {
            position: relative;
            display: inline-flex;
        }

        .cl-thumb-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #6c757d;
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .cl-img-btn {
            border: 1px solid #e3e6ea;
            background: #fff;
            color: #6c757d;
            border-radius: 6px;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            cursor: pointer;
            padding: 0;
            margin: 0;
        }

        .cl-img-btn:hover {
            border-color: #9aa1a9;
            color: #495057;
        }

        /* Rail that splits this screen into Items / Handover Terms (lease & sale only). */
        /* There is no global x-cloak rule, so the hidden pane needs its own. */
        .cl-wrap [x-cloak] {
            display: none !important;
        }

        .cl-sub {
            display: flex;
            gap: .3rem;
            flex-wrap: wrap;
        }

        .cl-sub-btn {
            border: 1px solid #e3e6ea;
            background: #fff;
            color: #6c757d;
            border-radius: 999px;
            padding: .25rem .8rem;
            font-size: .72rem;
            font-weight: 600;
            line-height: 1.4;
            transition: color .14s ease, border-color .14s ease, background-color .14s ease;
        }

        .cl-sub-btn:hover {
            border-color: var(--bs-primary, #0d6efd);
            color: var(--bs-primary, #0d6efd);
        }

        .cl-sub-btn.is-active {
            background: var(--bs-primary, #0d6efd);
            border-color: var(--bs-primary, #0d6efd);
            color: #fff;
        }

        /* Keep the grid usable on small screens: scroll instead of crushing the inputs. */
        .cl-table>table {
            min-width: 680px;
        }

        .cl-wrap.cl-no-mo .cl-table>table {
            min-width: 560px;
        }

        /* Widths are computed per checklist from the content — these are only floors. */
        .cl-col-desc {
            min-width: 150px;
        }

        .cl-col-cmt {
            min-width: 150px;
        }

        @media (max-width: 767.98px) {

            .cl-table thead th,
            .cl-table tbody td {
                padding-left: .35rem;
                padding-right: .35rem;
            }

            .cl-thumb,
            .cl-thumb-empty {
                width: 32px;
                height: 32px;
            }

            .cl-col-desc,
            .cl-col-cmt {
                min-width: 130px;
            }
        }
    </style>

    @if (! $showMoveOut)
        <div class="cl-sub mb-2">
            <button type="button" class="cl-sub-btn" :class="{ 'is-active': sub === 'items' }" x-on:click="sub = 'items'">
                <i class="fa fa-list-alt me-1"></i>Items
            </button>
            <button type="button" class="cl-sub-btn" :class="{ 'is-active': sub === 'terms' }" x-on:click="sub = 'terms'">
                <i class="fa fa-balance-scale me-1"></i>Handover Terms
            </button>
        </div>
    @endif

    <div x-show="sub === 'items'">
    {{-- Header: count summary + actions --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div class="small text-muted">
            <i class="fa fa-list-alt me-1"></i>
            <strong>{{ count($lines) }}</strong> item(s)
            @if ($showMoveOut)
                &middot; Damage: <strong class="text-primary">QAR {{ number_format($damageTotal, 2) }}</strong>
            @endif
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <a href="{{ route('print::rentout::checklist', $rentOutId) }}" target="_blank"
                class="btn btn-outline-secondary d-inline-flex align-items-center" style="font-size:.7rem; padding:.2rem .5rem; border-radius:4px;">
                <i class="fa fa-file-pdf-o me-1"></i> Download PDF
            </a>
            <a href="{{ route('property::rent_out::checklist::print', $rentOutId) }}" target="_blank"
                class="btn btn-outline-secondary d-inline-flex align-items-center" style="font-size:.7rem; padding:.2rem .5rem; border-radius:4px;">
                <i class="fa fa-pencil-square-o me-1"></i> Open Handover (Sign)
            </a>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center"
                style="font-size:.7rem; padding:.2rem .5rem; border-radius:4px;" wire:click="openAddItems">
                <i class="fa fa-plus me-1"></i> Add Items
            </button>
        </div>
    </div>

    {{-- Handover dates + signatories --}}
    <div class="row g-2 mb-2">
        <div class="col-lg-2 col-md-6 col-12">
            {{-- On a lease/sale the inspection and the handover are the same visit, so one date
                 drives both the Inspection Date and Hand Over Date lines on the printed form. --}}
            <label class="form-label small mb-1 text-muted">{{ $showMoveOut ? 'Actual Move-In Date' : 'Inspection Date' }}</label>
            <input type="date" class="form-control form-control-sm" wire:model="actualMoveInDate">
        </div>
        <div class="col-lg-2 col-md-6 col-12">
            <label class="form-label small mb-1 text-muted">{{ $showMoveOut ? 'Actual Move-Out Date' : 'Handover Date' }}</label>
            <input type="date" class="form-control form-control-sm" wire:model="actualMoveOutDate">
        </div>
        <div class="col-lg-4 col-md-6 col-12" wire:ignore>
            <label class="form-label small mb-1 text-muted">{{ ChecklistSignatoryRole::FacilityCoordinator->labelFor($agreement_type) }}</label>
            <select id="facilityCoord" class="select-employee_id-list" style="width:100%" placeholder="Select Employee">
                @if ($facilityCoordinatorId)
                    <option value="{{ $facilityCoordinatorId }}" selected>{{ $facilityCoordinatorName }}</option>
                @endif
            </select>
        </div>
        <div class="col-lg-4 col-md-6 col-12" wire:ignore>
            <label class="form-label small mb-1 text-muted">{{ ChecklistSignatoryRole::LeasingCoordinator->labelFor($agreement_type) }}</label>
            <select id="leasingCoord" class="select-employee_id-list" style="width:100%" placeholder="Select Employee">
                @if ($leasingCoordinatorId)
                    <option value="{{ $leasingCoordinatorId }}" selected>{{ $leasingCoordinatorName }}</option>
                @endif
            </select>
        </div>
    </div>

    {{-- Bulk action bar (only when rows selected) --}}
    @if (count($selected) > 0)
        <div class="d-flex align-items-center flex-wrap gap-2 px-2 py-1 mb-2 rounded" style="background:#eaf2ff; border:1px solid #c9defc;">
            <span class="small fw-semibold">{{ count($selected) }} selected</span>
            <span class="vr"></span>
            <span class="small text-muted">Move-In:</span>
            <button class="btn btn-success" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="bulkMoveIn(true)"><i
                    class="fa fa-check me-1"></i>Present</button>
            <button class="btn btn-outline-secondary" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="bulkMoveIn(false)">Clear</button>
            @if ($showMoveOut)
                <span class="vr"></span>
                <span class="small text-muted">Move-Out:</span>
                <button class="btn btn-success" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="bulkMoveOut('ok')"><i
                        class="fa fa-check me-1"></i>Good</button>
                <button class="btn btn-danger" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="bulkMoveOut('not_ok')"><i
                        class="fa fa-times me-1"></i>Damaged</button>
                <button class="btn btn-outline-secondary" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="bulkMoveOut(null)">Clear</button>
            @endif
            <button class="btn btn-outline-danger ms-auto" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="deleteSelected"
                wire:confirm="Remove the {{ count($selected) }} selected item(s) from this checklist?">
                <i class="fa fa-trash me-1"></i>Delete Selected
            </button>
        </div>
    @else
        <div class="cl-legend small text-muted mb-2 d-flex align-items-center gap-3 flex-wrap">
            <span class="d-inline-flex align-items-center gap-1"><span class="dot bg-success"><i class="fa fa-check"></i></span> Good / Present</span>
            @if ($showMoveOut)
                <span class="d-inline-flex align-items-center gap-1"><span class="dot bg-danger"><i class="fa fa-times"></i></span> Damaged /
                    Missing</span>
                <span class="text-muted">Move-In = present? &middot; Move-Out = good / damaged (tap to toggle)</span>
            @else
                <span class="text-muted">Move-In = present? (tap to toggle)</span>
            @endif
        </div>
    @endif

    {{-- Lines --}}
    <div class="table-responsive cl-table">
        <table class="table table-hover align-middle mb-0 table-sm">
            <thead>
                <tr>
                    <th class="py-2 text-center" style="width:34px;">
                        <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                    </th>
                    <th class="py-2" style="width:54px;">Qty</th>
                    <th class="py-2 text-center" style="width:96px;">Image</th>
                    <th class="py-2 cl-col-desc" style="width:{{ $pct('desc') }};">Item Description</th>
                    <th class="py-2 text-center" style="width:78px;">Move-In</th>
                    <th class="py-2 cl-col-cmt" style="width:{{ $pct('in') }};">Comments</th>

                    @if ($showMoveOut)
                        <th class="py-2 text-center" style="width:84px;">Move-Out</th>
                        <th class="py-2 cl-col-cmt" style="width:{{ $pct('out') }};">Comments</th>
                        <th class="py-2" style="width:106px;">Damage Cost</th>
                    @endif

                    <th class="py-2 text-center" style="width:42px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grouped as $category => $rows)
                    <tr class="cl-cat">
                        <td colspan="{{ $colCount }}">
                            <span class="cl-cat-label"><i class="fa fa-folder-open-o me-2 text-primary opacity-75"></i>{{ $category }}</span>
                            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis ms-1">{{ count($rows) }}</span>
                        </td>
                    </tr>
                    @foreach ($rows as $row)
                        @php
                            $i = $row['i'];
                            $line = $row['line'];
                            $mo = $line['move_out_status'] ?? null;
                            $hasDamage = (float) ($line['damage_cost'] ?? 0) > 0;
                        @endphp
                        <tr wire:key="line-{{ $i }}-{{ $line['id'] ?? 'new' }}">
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input" value="{{ $i }}" wire:model.live="selected">
                            </td>
                            <td>
                                <input type="number" min="0" class="form-control form-control-sm cl-inp cl-qty"
                                    wire:model.blur="lines.{{ $i }}.qty">
                            </td>
                            <td class="text-center">
                                <div class="cl-img-cell">
                                    @if (!empty($line['resolved_image_url']))
                                        <span class="cl-thumb-wrap">
                                            <img src="{{ $line['resolved_image_url'] }}" class="cl-thumb zoomable" alt=""
                                                data-img="{{ $line['resolved_image_url'] }}" style="cursor:zoom-in;" title="Click to enlarge">
                                            @if (empty($line['image_path']))
                                                <span class="cl-thumb-badge" title="Inherited from master item">M</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="cl-thumb cl-thumb-empty"><i class="fa fa-picture-o"></i></span>
                                    @endif
                                    <label class="cl-img-btn" title="{{ !empty($line['image_path']) ? 'Replace image' : 'Upload image' }}">
                                        <i class="fa fa-camera"></i>
                                        <input type="file" class="d-none" accept="image/*" wire:model="newImages.{{ $i }}">
                                    </label>
                                    @if (!empty($line['image_path']))
                                        <button type="button" class="cl-img-btn text-danger" title="Remove image (use master)"
                                            wire:click="removeLineImage({{ $i }})">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    @endif
                                </div>
                                <div wire:loading wire:target="newImages.{{ $i }}" class="small text-muted mt-1"><i
                                        class="fa fa-spinner fa-spin"></i></div>
                            </td>
                            <td>
                                <span class="fw-medium">{{ $line['name'] }}</span>
                            </td>
                            <td class="text-center">
                                <button type="button" wire:click="toggleMoveIn({{ $i }})"
                                    class="cl-toggle {{ ($line['move_in_status'] ?? null) === 'ok' ? 'on-ok' : '' }}"
                                    title="{{ ($line['move_in_status'] ?? null) === 'ok' ? 'Present' : 'Mark present' }}">
                                    <i class="fa fa-check"></i>
                                </button>
                            </td>
                            <td><input class="form-control form-control-sm cl-inp" placeholder="—"
                                    wire:model.blur="lines.{{ $i }}.move_in_comment"></td>
                            @if ($showMoveOut)
                                <td class="text-center">
                                    <button type="button" wire:click="cycleStatus({{ $i }}, 'move_out')"
                                        class="cl-toggle {{ $mo === 'ok' ? 'on-ok' : ($mo === 'not_ok' ? 'on-no' : '') }}"
                                        title="Good / Damaged / Clear">
                                        <i class="fa {{ $mo === 'ok' ? 'fa-check' : ($mo === 'not_ok' ? 'fa-times' : 'fa-minus') }}"></i>
                                    </button>
                                </td>
                                <td><input class="form-control form-control-sm cl-inp" placeholder="—"
                                        wire:model.blur="lines.{{ $i }}.move_out_comment"></td>
                                <td>
                                    <div class="input-group input-group-sm cl-dmg-grp">
                                        <input type="number" min="0" step="0.01"
                                            class="form-control form-control-sm cl-inp cl-dmg {{ $hasDamage ? 'text-danger fw-semibold' : '' }}"
                                            placeholder="0.00" wire:model.blur="lines.{{ $i }}.damage_cost">
                                    </div>
                                </td>
                            @endif
                            <td class="text-center">
                                <button type="button" class="btn btn-sm cl-trash" wire:click="removeLine({{ $i }})" title="Remove">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    {{-- Fixture Comments for this area, directly under its items. --}}
                    @if (isset($fixtureIndex[$category]))
                        <tr class="cl-fx-row">
                            <td colspan="{{ $colCount }}" class="p-0">
                                @include('livewire.rent-out.partials.fixture-block', [
                                    'a' => $fixtureIndex[$category],
                                    'area' => $fixtureAreas[$fixtureIndex[$category]],
                                    'canRemoveArea' => false,
                                ])
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ $colCount }}" class="text-center text-muted py-5">
                            <i class="fa fa-list-alt fa-2x mb-2 d-block opacity-25"></i>
                            <div>No items yet</div>
                            <div class="small">Click <strong>Add Items</strong> to choose what's in this unit.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($showMoveOut && count($lines) > 0)
                <tfoot>
                    <tr class="fw-semibold small" style="background:#f6f8fb;">
                        <td colspan="8" class="py-2 text-end text-muted">Total Damage</td>
                        <td class="py-2 text-end text-danger">{{ number_format($damageTotal, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- Areas added by hand — they have no items, so nothing above put them on screen. --}}
    @foreach ($fixtureAreas as $a => $area)
        @if (! isset($grouped[$area['category']]))
            <div class="cl-fx-standalone mt-3">
                @include('livewire.rent-out.partials.fixture-block', [
                    'a' => $a,
                    'area' => $area,
                    'canRemoveArea' => true,
                ])
            </div>
        @endif
    @endforeach

    {{-- Add an area for work done somewhere with no inventory items of its own. --}}
    <div class="cl-fx-addarea mt-3">
        <i class="fa fa-plus-circle me-1 opacity-75"></i>
        <span class="small">Work done in an area with no checklist items?</span>
        @if (count($availableCategories))
            {{-- Click-and-go: picking an area adds it, no separate confirm step. --}}
            <select class="form-select form-select-sm cl-inp" style="max-width:190px;" wire:change="addFixtureArea($event.target.value)">
                <option value="">Choose an area…</option>
                @foreach ($availableCategories as $category)
                    <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
            </select>
            <span class="small text-muted">or</span>
        @endif
        <input class="form-control form-control-sm cl-inp" style="max-width:190px;" placeholder="Type a new area…"
            wire:model="newAreaCategory" wire:keydown.enter.prevent="addFixtureArea">
        <button type="button" class="btn btn-sm cl-fx-add" wire:click="addFixtureArea"><i class="fa fa-plus me-1"></i>Add area</button>
    </div>

    {{-- Remarks --}}
    <div class="row g-2 mt-3">
        <div class="{{ $showMoveOut ? 'col-md-6' : 'col-12' }}">
            <label class="form-label small fw-semibold text-uppercase text-muted" style="font-size:11px;">Move-In Remarks</label>
            <textarea class="form-control form-control-sm" rows="2" wire:model="moveInRemarks" placeholder="Move-in remarks…"></textarea>
        </div>
        @if ($showMoveOut)
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-uppercase text-muted" style="font-size:11px;">Move-Out Remarks</label>
                <textarea class="form-control form-control-sm" rows="2" wire:model="moveOutRemarks" placeholder="Move-out remarks…"></textarea>
            </div>
        @endif
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-primary btn-sm" wire:click="save" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save"><i class="fa fa-floppy-o me-1"></i> Save Checklist</span>
            <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i> Saving…</span>
        </button>
    </div>
    </div>{{-- /Items --}}

    @if (! $showMoveOut)
        <div x-show="sub === 'terms'" x-cloak>
            @livewire('rent-out.tabs.handover-terms-tab', ['rentOutId' => $rentOutId], key('handover-terms-tab-' . $rentOutId))
        </div>
    @endif
</div>


@script
    <script>
        // Sync the two coordinator employee pickers into Livewire on change.
        $('#facilityCoord').on('change', function() {
            $wire.set('facilityCoordinatorId', $(this).val() || null);
        });
        $('#leasingCoord').on('change', function() {
            $wire.set('leasingCoordinatorId', $(this).val() || null);
        });
    </script>
@endscript
