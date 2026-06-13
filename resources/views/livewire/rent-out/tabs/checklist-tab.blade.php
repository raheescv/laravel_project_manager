<div>
    <style>
        .cl-table { border: 1px solid #e6e8ec; border-radius: 10px; overflow: hidden; }
        .cl-table thead th { background: #f6f8fb; color: #5a626c; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; border-bottom: 1px solid #e6e8ec; vertical-align: middle; }
        .cl-table tbody td { vertical-align: middle; border-color: #f0f1f4; }
        .cl-table tbody tr:hover td { background: #fafbfc; }
        .cl-cat td { background: #eef2f8; border-left: 3px solid var(--bs-primary, #0d6efd); padding: .35rem .65rem; }
        .cl-cat-label { font-weight: 700; font-size: 11px; letter-spacing: .5px; text-transform: uppercase; color: #3a4250; }
        .cl-toggle { width: 30px; height: 30px; border-radius: 50%; border: 1.5px solid #d3d8de; background: #fff; color: #cbd1d8; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; transition: all .15s ease; cursor: pointer; }
        .cl-toggle:hover { border-color: #9aa1a9; color: #8a929b; }
        .cl-toggle.on-ok { background: #198754; border-color: #198754; color: #fff; }
        .cl-toggle.on-no { background: #dc3545; border-color: #dc3545; color: #fff; }
        .cl-inp { height: 28px; font-size: 12.5px; border-color: #e3e6ea; padding: .1rem .3rem; }
        .cl-inp[type=number] { -moz-appearance: textfield; appearance: textfield; }
        .cl-inp[type=number]::-webkit-outer-spin-button,
        .cl-inp[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .cl-qty { width: 46px; text-align: center; padding-left: .15rem; padding-right: .15rem; }
        .cl-dmg { width: 86px; text-align: right; }
        .cl-trash { color: #c0c5cc; border: none; background: transparent; padding: .2rem .4rem; }
        .cl-trash:hover { color: #dc3545; }
        .cl-legend .dot { width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; color: #fff; }
        .cl-img-cell { display: inline-flex; align-items: center; gap: .25rem; }
        .cl-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #e3e6ea; background: #fff; }
        .cl-thumb-empty { display: inline-flex; align-items: center; justify-content: center; color: #cbd1d8; font-size: 15px; }
        .cl-thumb-wrap { position: relative; display: inline-flex; }
        .cl-thumb-badge { position: absolute; top: -5px; right: -5px; width: 15px; height: 15px; border-radius: 50%; background: #6c757d; color: #fff; font-size: 8px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; line-height: 1; }
        .cl-img-btn { border: 1px solid #e3e6ea; background: #fff; color: #6c757d; border-radius: 6px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; cursor: pointer; padding: 0; margin: 0; }
        .cl-img-btn:hover { border-color: #9aa1a9; color: #495057; }
    </style>

    {{-- Header: count summary + actions --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div class="small text-muted">
            <i class="fa fa-list-alt me-1"></i>
            <strong>{{ count($lines) }}</strong> item(s)
            &middot; Damage: <strong class="text-primary">QAR {{ number_format($damageTotal, 2) }}</strong>
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
        <div class="col-md-3 col-6">
            <label class="form-label small mb-1 text-muted">Actual Move-In Date</label>
            <input type="date" class="form-control form-control-sm" wire:model="actualMoveInDate">
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label small mb-1 text-muted">Actual Move-Out Date</label>
            <input type="date" class="form-control form-control-sm" wire:model="actualMoveOutDate">
        </div>
        <div class="col-md-3 col-6" wire:ignore>
            <label class="form-label small mb-1 text-muted">Facility Coordinator</label>
            <select id="facilityCoord" class="select-employee_id-list" style="width:100%"
                placeholder="Select Employee">
                @if ($facilityCoordinatorId)
                    <option value="{{ $facilityCoordinatorId }}" selected>{{ $facilityCoordinatorName }}</option>
                @endif
            </select>
        </div>
        <div class="col-md-3 col-6" wire:ignore>
            <label class="form-label small mb-1 text-muted">Leasing Coordinator</label>
            <select id="leasingCoord" class="select-employee_id-list" style="width:100%"
                placeholder="Select Employee">
                @if ($leasingCoordinatorId)
                    <option value="{{ $leasingCoordinatorId }}" selected>{{ $leasingCoordinatorName }}</option>
                @endif
            </select>
        </div>
    </div>

    {{-- Bulk action bar (only when rows selected) --}}
    @if (count($selected) > 0)
        <div class="d-flex align-items-center flex-wrap gap-2 px-2 py-1 mb-2 rounded"
            style="background:#eaf2ff; border:1px solid #c9defc;">
            <span class="small fw-semibold">{{ count($selected) }} selected</span>
            <span class="vr"></span>
            <span class="small text-muted">Move-In:</span>
            <button class="btn btn-success" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="bulkMoveIn(true)"><i class="fa fa-check me-1"></i>Present</button>
            <button class="btn btn-outline-secondary" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="bulkMoveIn(false)">Clear</button>
            <span class="vr"></span>
            <span class="small text-muted">Move-Out:</span>
            <button class="btn btn-success" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="bulkMoveOut('ok')"><i class="fa fa-check me-1"></i>Good</button>
            <button class="btn btn-danger" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="bulkMoveOut('not_ok')"><i class="fa fa-times me-1"></i>Damaged</button>
            <button class="btn btn-outline-secondary" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="bulkMoveOut(null)">Clear</button>
            <button class="btn btn-outline-danger ms-auto" style="font-size:.7rem; padding:.15rem .5rem;" wire:click="deleteSelected"
                wire:confirm="Remove the {{ count($selected) }} selected item(s) from this checklist?">
                <i class="fa fa-trash me-1"></i>Delete Selected
            </button>
        </div>
    @else
        <div class="cl-legend small text-muted mb-2 d-flex align-items-center gap-3 flex-wrap">
            <span class="d-inline-flex align-items-center gap-1"><span class="dot bg-success"><i class="fa fa-check"></i></span> Good / Present</span>
            <span class="d-inline-flex align-items-center gap-1"><span class="dot bg-danger"><i class="fa fa-times"></i></span> Damaged / Missing</span>
            <span class="text-muted">Move-In = present? &middot; Move-Out = good / damaged (tap to toggle)</span>
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
                    <th class="py-2">Item Description</th>
                    <th class="py-2 text-center" style="width:78px;">Move-In</th>
                    <th class="py-2" style="width:170px;">Comments</th>
                    <th class="py-2 text-center" style="width:84px;">Move-Out</th>
                    <th class="py-2" style="width:170px;">Comments</th>
                    <th class="py-2" style="width:106px;">Damage Cost</th>
                    <th class="py-2 text-center" style="width:42px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grouped as $category => $rows)
                    <tr class="cl-cat">
                        <td colspan="10">
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
                                <input type="number" min="0" class="form-control form-control-sm cl-inp cl-qty" wire:model.blur="lines.{{ $i }}.qty">
                            </td>
                            <td class="text-center">
                                <div class="cl-img-cell">
                                    @if (!empty($line['resolved_image_url']))
                                        <span class="cl-thumb-wrap">
                                            <img src="{{ $line['resolved_image_url'] }}" class="cl-thumb zoomable" alt=""
                                                data-img="{{ $line['resolved_image_url'] }}"
                                                style="cursor:zoom-in;" title="Click to enlarge">
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
                                        <button type="button" class="cl-img-btn text-danger" title="Remove image (use master)" wire:click="removeLineImage({{ $i }})">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    @endif
                                </div>
                                <div wire:loading wire:target="newImages.{{ $i }}" class="small text-muted mt-1"><i class="fa fa-spinner fa-spin"></i></div>
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
                            <td><input class="form-control form-control-sm cl-inp" placeholder="—" wire:model.blur="lines.{{ $i }}.move_in_comment"></td>
                            <td class="text-center">
                                <button type="button" wire:click="cycleStatus({{ $i }}, 'move_out')"
                                    class="cl-toggle {{ $mo === 'ok' ? 'on-ok' : ($mo === 'not_ok' ? 'on-no' : '') }}"
                                    title="Good / Damaged / Clear">
                                    <i class="fa {{ $mo === 'ok' ? 'fa-check' : ($mo === 'not_ok' ? 'fa-times' : 'fa-minus') }}"></i>
                                </button>
                            </td>
                            <td><input class="form-control form-control-sm cl-inp" placeholder="—" wire:model.blur="lines.{{ $i }}.move_out_comment"></td>
                            <td>
                                <div class="input-group input-group-sm cl-dmg-grp">
                                    <input type="number" min="0" step="0.01"
                                        class="form-control form-control-sm cl-inp cl-dmg {{ $hasDamage ? 'text-danger fw-semibold' : '' }}"
                                        placeholder="0.00" wire:model.blur="lines.{{ $i }}.damage_cost">
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm cl-trash" wire:click="removeLine({{ $i }})" title="Remove">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="fa fa-list-alt fa-2x mb-2 d-block opacity-25"></i>
                            <div>No items yet</div>
                            <div class="small">Click <strong>Add Items</strong> to choose what's in this unit.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($lines) > 0)
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

    {{-- Remarks --}}
    <div class="row g-2 mt-3">
        <div class="col-md-6">
            <label class="form-label small fw-semibold text-uppercase text-muted" style="font-size:11px;">Move-In Remarks</label>
            <textarea class="form-control form-control-sm" rows="2" wire:model="moveInRemarks" placeholder="Move-in remarks…"></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold text-uppercase text-muted" style="font-size:11px;">Move-Out Remarks</label>
            <textarea class="form-control form-control-sm" rows="2" wire:model="moveOutRemarks" placeholder="Move-out remarks…"></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-primary btn-sm" wire:click="save" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save"><i class="fa fa-floppy-o me-1"></i> Save Checklist</span>
            <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin me-1"></i> Saving…</span>
        </button>
    </div>
</div>


@script
    <script>
        // Sync the two coordinator employee pickers into Livewire on change.
        $('#facilityCoord').on('change', function () {
            $wire.set('facilityCoordinatorId', $(this).val() || null);
        });
        $('#leasingCoord').on('change', function () {
            $wire.set('leasingCoordinatorId', $(this).val() || null);
        });
    </script>
@endscript
