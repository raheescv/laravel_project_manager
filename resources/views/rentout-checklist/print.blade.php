<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Unit Handover & Snagging</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <script src="{{ asset('assets/js/signature_pdf.js') }}"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f2f5;
            padding: 2rem;
        }

        .checklist-card {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            max-width: 1100px;
            margin: auto;
        }

        .form-heading {
            font-size: 1.8rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.25rem;
        }

        .form-subtitle {
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }

        .meta-table th {
            background-color: #f8f9fa;
            color: #495057;
        }

        .table th,
        .table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .category-row th {
            background-color: #eef1f4;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.03em;
        }

        .acceptance-block {
            border: 1px solid #e0e3e7;
            border-radius: 10px;
            padding: 1rem;
            height: 100%;
        }

        .phase-heading {
            font-size: 1.1rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 1rem;
        }

        .signed-box {
            border: 1px dashed #ccc;
            padding: 8px;
            border-radius: 8px;
            text-align: center;
        }

        @media (max-width: 767.98px) {
            body {
                padding: .75rem;
            }

            .checklist-card {
                padding: 1.25rem;
            }

            .form-heading {
                font-size: 1.35rem;
            }

            .table th,
            .table td {
                font-size: .82rem;
            }
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }

            .checklist-card {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }

            .no-print,
            [class^="sigpad-container-"],
            [class^="sigpad-actions-"] {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="checklist-card">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <div class="form-heading">Unit Handover & Snagging</div>
                <div class="form-subtitle">Property Handover Inventory &amp; Condition Record</div>
            </div>
            <a class="btn btn-primary no-print" href="{{ route('print::rentout::checklist', $rentOut->id) }}" target="_blank"><i class="fa fa-download"></i> Download PDF</a>
        </div>

        @php
            $tenant = $rentOut?->account;
            // Move-Out / damage tracking only applies to rentals — a lease/sale never hands the unit back.
            $showMoveOut = $rentOut?->agreement_type === \App\Enums\RentOut\AgreementType::Rental;
            $colCount = $showMoveOut ? 9 : 6;

            // Split the free width between Item and Comments in proportion to the text
            // they actually hold — see the PDF view for the same sizing rationale.
            $lengthOf = function ($values) {
                $lengths = collect($values)->map(fn ($v) => mb_strlen(trim((string) $v)))->filter()->sort()->values();
                if ($lengths->isEmpty()) {
                    return 0;
                }
                return (int) $lengths[(int) floor(($lengths->count() - 1) * 0.9)];
            };
            $checklistLines = $rentOut->checklistLines;
            $freeWidth = $showMoveOut ? 60.0 : 76.0;
            $weigh = fn ($len) => $len <= 0 ? 0.45 : max(0.8, min(3.2, $len / 26));
            $weights = [
                'desc' => $weigh($lengthOf($checklistLines->map(fn ($l) => $l->item?->name))) * 1.15,
                'in' => $weigh($lengthOf($checklistLines->map(fn ($l) => $l->move_in_comment))),
            ];
            if ($showMoveOut) {
                $weights['out'] = $weigh($lengthOf($checklistLines->map(fn ($l) => $l->move_out_comment)));
            }
            $weightTotal = array_sum($weights) ?: 1;
            $pct = fn ($k) => round($freeWidth * $weights[$k] / $weightTotal, 1) . '%';
        @endphp

        {{-- (a) Property Details --}}
        <table class="table table-bordered table-sm meta-table mb-4">
            <tbody>
                <tr>
                    <th style="width:18%">Tenant</th>
                    <td style="width:32%"><b>{{ $tenant?->name ?? '-' }}</b></td>
                    <th style="width:18%">Mobile</th>
                    <td style="width:32%">{{ $tenant?->mobile ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Group</th>
                    <td>{{ $rentOut?->group?->name ?? '-' }}</td>
                    <th>Building</th>
                    <td>{{ $rentOut?->building?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Unit</th>
                    <td>{{ $rentOut?->property?->number ?? ($rentOut?->property?->unit_no ?? '-') }}</td>
                    <th>Type</th>
                    <td>{{ $rentOut?->type?->name ?? '-' }}</td>
                </tr>
                @if ($showMoveOut)
                    <tr>
                        <th>Actual Move-In Date</th>
                        <td>{{ $rentOut->actual_move_in_date ? systemDate($rentOut->actual_move_in_date) : '-' }}</td>
                        <th>Actual Move-Out Date</th>
                        <td>{{ $rentOut->actual_move_out_date ? systemDate($rentOut->actual_move_out_date) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Electricity &amp; Water</th>
                        <td>{{ $rentOut?->include_electricity_water ? 'Included' : 'Not Included' }}</td>
                        <th>Air Conditioning</th>
                        <td>{{ $rentOut?->include_ac ? 'Included' : 'Not Included' }}</td>
                    </tr>
                    <tr>
                        <th>Internet / WiFi</th>
                        <td>{{ $rentOut?->include_wifi ? 'Included' : 'Not Included' }}</td>
                        <th></th>
                        <td></td>
                    </tr>
                @else
                    {{-- A lease/sale reports handover milestones and meter references. --}}
                    <tr>
                        <th>Inspection Date</th>
                        <td>{{ $rentOut->actual_move_in_date ? systemDate($rentOut->actual_move_in_date) : '-' }}</td>
                        <th>Hand Over Date</th>
                        <td>{{ $rentOut->actual_move_in_date ? systemDate($rentOut->actual_move_out_date) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Kahrama Number</th>
                        <td>{{ $rentOut?->property?->kahramaa ?: '-' }}</td>
                        <th>Gas Meter Number</th>
                        <td>{{ $rentOut?->property?->gas_meter_number ?: '-' }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- (b) Inventory & Condition --}}
        <p class="section-title">Inventory &amp; Condition</p>
        <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr>
                    <th style="width:40px;">Sn</th>
                    <th class="text-center" style="width:50px;">Qty</th>
                    <th class="text-center" style="width:60px;">Image</th>
                    <th style="width:{{ $pct('desc') }}; min-width:140px;">Item</th>
                    <th class="text-center" style="width:70px;">Move-In</th>
                    <th style="width:{{ $pct('in') }}; min-width:140px;">Comments</th>
                    @if ($showMoveOut)
                        <th class="text-center" style="width:70px;">Move-Out</th>
                        <th style="width:{{ $pct('out') }}; min-width:140px;">Move-Out Comments</th>
                        <th class="text-end" style="width:80px;">Damage</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @php
                    $grouped = $rentOut->checklistLines->groupBy(fn ($l) => $l->item?->category ?: 'Uncategorized');
                    $sn = 0;
                @endphp
                @forelse ($grouped as $category => $lines)
                    <tr class="category-row">
                        <th colspan="{{ $colCount }}">{{ $category ?: 'Uncategorized' }}</th>
                    </tr>
                    @foreach ($lines as $line)
                        @php $sn++; @endphp
                        <tr>
                            <td>{{ $sn }}</td>
                            <td class="text-center">{{ $line->qty }}</td>
                            <td class="text-center">
                                @if ($line->resolved_image_url)
                                    <img src="{{ $line->resolved_image_url }}" alt="" class="zoomable"
                                        data-img="{{ $line->resolved_image_url }}"
                                        style="width:34px; height:34px; object-fit:cover; border-radius:4px; cursor:zoom-in;"
                                        title="Click to enlarge">
                                @endif
                            </td>
                            <td>{{ $line->item?->name }}</td>
                            <td class="text-center">{{ $line->move_in_status?->symbol() }}</td>
                            <td>{{ $line->move_in_comment }}</td>
                            @if ($showMoveOut)
                                <td class="text-center">{{ $line->move_out_status?->symbol() }}</td>
                                <td>{{ $line->move_out_comment }}</td>
                                <td class="text-end">{{ number_format((float) $line->damage_cost, 2) }}</td>
                            @endif
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="{{ $colCount }}" class="text-center text-muted">No items recorded.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($showMoveOut)
                <tfoot>
                    <tr>
                        <th colspan="8" class="text-end">Total Damage</th>
                        <th class="text-end"><b>{{ number_format($rentOut->checklistDamageTotal(), 2) }}</b></th>
                    </tr>
                </tfoot>
            @endif
        </table>
        </div>

        {{-- (c) Acceptance Blocks: Move-In then Move-Out --}}
        @php
            $phases = $showMoveOut
                ? [\App\Enums\RentOut\ChecklistPhase::MoveIn, \App\Enums\RentOut\ChecklistPhase::MoveOut]
                : [\App\Enums\RentOut\ChecklistPhase::MoveIn];
            $roles = [
                \App\Enums\RentOut\ChecklistSignatoryRole::Lessee,
                \App\Enums\RentOut\ChecklistSignatoryRole::FacilityCoordinator,
                \App\Enums\RentOut\ChecklistSignatoryRole::LeasingCoordinator,
            ];
        @endphp

        <div class="row mt-4">
            @foreach ($phases as $phase)
                <div class="{{ count($phases) > 1 ? 'col-md-6' : 'col-12' }}">
                    <div class="acceptance-block mb-4">
                        <div class="phase-heading">{{ $phase->label() }} Acceptance</div>
                        <div class="row">
                            @foreach ($roles as $role)
                                @php
                                    $resolvedName = match ($role->value) {
                                        'lessee' => $tenant?->name,
                                        'facility_coordinator' => $rentOut->facilityCoordinator?->name,
                                        'leasing_coordinator' => $rentOut->leasingCoordinator?->name,
                                        default => null,
                                    };
                                    $sig = $rentOut->checklistSignatureFor($phase, $role);
                                    $userId = match ($role->value) {
                                        'facility_coordinator' => $rentOut->facility_coordinator_id,
                                        'leasing_coordinator' => $rentOut->leasing_coordinator_id,
                                        default => null,
                                    };
                                @endphp
                                <div class="col-12 mb-3">
                                    @if ($sig && $sig->signature_path)
                                        <p class="section-title mb-1">
                                            {{ $resolvedName ?: '-' }}
                                            <small class="text-muted">({{ $role->label() }})</small>
                                        </p>
                                        <div class="signed-box">
                                            <img src="{{ asset('storage/' . $sig->signature_path) }}"
                                                alt="Signature" style="max-height:80px">
                                            <div class="mt-1">
                                                <b>{{ $sig->signer_name ?: ($resolvedName ?: '-') }}</b>
                                            </div>
                                            <div class="text-muted" style="font-size:0.8rem">
                                                {{ $sig->signed_at ? systemDateTime($sig->signed_at) : '' }}
                                            </div>
                                        </div>
                                    @else
                                        @livewire('rent-out.checklist.sign', [
                                            'rentOut' => $rentOut,
                                            'phase' => $phase->value,
                                            'role' => $role->value,
                                            'signerName' => $resolvedName,
                                            'userId' => $userId,
                                        ], key('sign-' . $phase->value . '-' . $role->value . '-' . $rentOut->id))
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Self-contained image lightbox (no Bootstrap JS dependency on this standalone page) --}}
    <div id="imgZoomOverlay" class="no-print"
        style="display:none; position:fixed; inset:0; z-index:1080; background:rgba(0,0,0,.85);
               align-items:center; justify-content:center; cursor:zoom-out;">
        <span id="imgZoomClose" style="position:absolute; top:18px; right:26px; color:#fff; font-size:34px; line-height:1; cursor:pointer;"
            aria-label="Close">&times;</span>
        <img id="imgZoomTarget" src="" alt="Preview"
            style="max-width:92vw; max-height:88vh; object-fit:contain; border-radius:8px; box-shadow:0 12px 40px rgba(0,0,0,.5);">
    </div>
    <script>
        (function () {
            var overlay = document.getElementById('imgZoomOverlay');
            var target = document.getElementById('imgZoomTarget');
            if (!overlay || !target) return;

            document.addEventListener('click', function (e) {
                var img = e.target.closest && e.target.closest('img.zoomable');
                if (img) {
                    target.src = img.getAttribute('data-img') || img.src;
                    overlay.style.display = 'flex';
                    return;
                }
                if (e.target === overlay || e.target.id === 'imgZoomClose') {
                    overlay.style.display = 'none';
                    target.src = '';
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && overlay.style.display === 'flex') {
                    overlay.style.display = 'none';
                    target.src = '';
                }
            });
        })();
    </script>
</body>

</html>
