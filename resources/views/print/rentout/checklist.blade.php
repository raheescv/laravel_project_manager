<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unit Handover & Snagging</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #2b2b2b; margin: 0; }
        .wrap { padding: 0; }
        .title-band { background: #7a6a2f; color: #fff; border-radius: 4px; width: 100%; }
        .title-band td { vertical-align: middle; padding: 8px 10px; }
        .title-band .tb-logo { width: 110px; }
        .title-band .tb-title { text-align: center; }
        .title-band .t { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
        .title-band .s { font-size: 9px; opacity: .9; }
        .logo-box { background: #fff; border-radius: 4px; padding: 4px; text-align: center; }
        .logo-box img { max-width: 96px; max-height: 46px; }
        .sec { background: #7a6a2f; color: #fff; font-size: 10px; font-weight: bold; letter-spacing: .4px;
               padding: 4px 8px; margin: 12px 0 6px; text-transform: uppercase; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 4px 7px; border: 1px solid #d8d4c4; font-size: 10px; }
        .meta .lbl { color: #6b6550; width: 17%; background: #f6f3e9; }
        .meta .val { width: 33%; font-weight: bold; }
        /* Fixed layout so the measured column widths are honoured instead of being
           re-guessed from content; break-word keeps long comments inside their cell. */
        .items { table-layout: fixed; }
        .items th { background: #efe9d6; color: #4a432b; border: 1px solid #ccc6b0; padding: 4px 6px; font-size: 9.5px; }
        .items td { border: 1px solid #ddd; padding: 3px 6px; font-size: 9.5px;
                    word-wrap: break-word; overflow-wrap: break-word; }
        .items tr.cat td { background: #f3efe2; color: #6a5f33; font-weight: bold; text-transform: uppercase; font-size: 9px; letter-spacing: .3px; }
        .items tr:nth-child(even) td { background: #fcfbf7; }
        .c { text-align: center; }
        .r { text-align: right; }
        .ok { color: #1d7a45; font-weight: bold; }
        .no { color: #bf2f2f; font-weight: bold; }
        .total td { background: #efe9d6; font-weight: bold; }
        .accept { border: 1px solid #d8d4c4; border-radius: 4px; padding: 8px 10px; margin-bottom: 8px; }
        .accept .ph { font-size: 11px; font-weight: bold; color: #7a6a2f; text-transform: uppercase; margin-bottom: 4px; }
        .accept .decl { font-size: 8.7px; color: #444; margin: 0 0 8px; line-height: 1.45; }
        .sign-cell { width: 33.33%; vertical-align: bottom; padding: 4px 6px; text-align: center; }
        .sign-img { height: 46px; margin-bottom: 2px; }
        .sign-line { border-top: 1px solid #555; padding-top: 3px; font-size: 8.5px; }
        .sign-name { font-weight: bold; font-size: 9px; }
        .muted { color: #8a8575; }
    </style>
</head>
<body>
@php
    $ro = $rentOut;
    $tenant = $ro?->account;
    $sigSrc = function ($sig) {
        if ($sig && $sig->signature_path) {
            $p = public_path('storage/' . $sig->signature_path);
            if (is_file($p)) {
                return $p; // dompdf renders local file paths reliably (data-URIs are dropped)
            }
        }
        return null;
    };
    // Line image with master-item fallback, resolved to a local path for dompdf.
    $imgSrc = function ($line) {
        $path = $line->image_path ?: $line->item?->image_path;
        if ($path) {
            $p = public_path('storage/' . $path);
            if (is_file($p)) {
                return $p;
            }
        }
        return null;
    };
    $fmt = fn ($d) => $d ? $d->format('d M Y') : '—';
    // Move-Out / damage tracking only applies to rentals — a lease/sale never hands the unit back.
    $showMoveOut = $ro?->agreement_type === \App\Enums\RentOut\AgreementType::Rental;
    $colCount = $showMoveOut ? 9 : 6;
    $phases = [
        'move_in' => ['label' => 'To be accomplished during Move-In',
            'decl' => 'I, ' . ($tenant?->name ?? 'the Lessee') . ', hereby confirm that the above mentioned furniture, kitchen appliances & accessories were physically checked and received by me in good condition.'],
    ];
    if ($showMoveOut) {
        $phases['move_out'] = ['label' => 'To be accomplished during Move-Out',
            'decl' => 'We hereby confirm that the above mentioned items were physically checked and received from ' . ($tenant?->name ?? 'the Lessee') . '. The Lessee confirms no further claim once the access card/s are returned.'];
    }
    $roles = ['lessee' => 'Lessee', 'facility_coordinator' => 'Facility Coordinator', 'leasing_coordinator' => 'Leasing Coordinator'];
    $nameFor = fn ($role) => match ($role) {
        'lessee' => $tenant?->name,
        'facility_coordinator' => $ro->facilityCoordinator?->name,
        'leasing_coordinator' => $ro->leasingCoordinator?->name,
        default => null,
    };
    $grouped = $ro->checklistLines->groupBy(fn ($l) => $l->item?->category ?: 'Others');
    $sn = 0;

    /**
     * Size the flexible columns from what they actually hold. dompdf can't reflow
     * content-aware, so we measure the text up front and split the free width
     * between Item Description and the Comments column(s) in proportion to it —
     * a checklist with no comments gives its width back to the descriptions,
     * and a wordy one borrows width from them.
     */
    $lengthOf = function ($values) {
        $lengths = collect($values)->map(fn ($v) => mb_strlen(trim((string) $v)))->filter()->sort()->values();
        if ($lengths->isEmpty()) {
            return 0;
        }
        // 90th percentile, so one rogue paragraph doesn't starve every other column.
        return (int) $lengths[(int) floor(($lengths->count() - 1) * 0.9)];
    };
    $lines = $ro->checklistLines;
    $lenDesc = $lengthOf($lines->map(fn ($l) => $l->item?->name));
    $lenIn = $lengthOf($lines->map(fn ($l) => $l->move_in_comment));
    $lenOut = $showMoveOut ? $lengthOf($lines->map(fn ($l) => $l->move_out_comment)) : 0;

    // Free width left after the fixed columns (Sn/Qty/Image/Move-In [+Move-Out/Damage]).
    $freeWidth = $showMoveOut ? 62.0 : 78.0;
    // An empty column still needs a usable header; a long one is capped so it can't run away.
    $weigh = fn ($len) => $len <= 0 ? 0.45 : max(0.8, min(3.2, $len / 26));
    $weights = ['desc' => $weigh($lenDesc) * 1.15, 'in' => $weigh($lenIn)];
    if ($showMoveOut) {
        $weights['out'] = $weigh($lenOut);
    }
    $weightTotal = array_sum($weights) ?: 1;
    $pct = fn ($k) => round($freeWidth * $weights[$k] / $weightTotal, 1) . '%';
@endphp

<div class="wrap">
    <table class="title-band">
        <tr>
            <td class="tb-logo">
                @if (!empty($companyLogo))
                    <div class="logo-box"><img src="{{ $companyLogo }}" alt="Logo"></div>
                @endif
            </td>
            <td class="tb-title">
                <div class="t">UNIT HANDOVER & SNAGGING</div>
                <div class="s">Property Handover — Inventory &amp; Condition Record</div>
            </td>
            <td class="tb-logo"></td>
        </tr>
    </table>

    <div class="sec">Property Details</div>
    @php
        $metaLeft = [
            ['Group / Project', $ro?->group?->name ?? '—'],
            ['Building', $ro?->building?->name ?? '—'],
            ['Property / Unit', $ro?->property?->number ?? '—'],
            ['Type', $ro?->type?->name ?? '—'],
            ['Tenant Name', $tenant?->name ?? '—'],
            ['Mobile No.', $tenant?->mobile ?? '—'],
        ];
        // A lease/sale has no tenancy period or utilities — it reports the handover
        // milestones and the unit's meter references instead.
        $metaRight = $showMoveOut
            ? [
                ['Lease Start', $fmt($ro?->start_date)],
                ['Actual Move-In', $fmt($ro->actual_move_in_date)],
                ['Lease End', $fmt($ro?->end_date)],
                ['Actual Move-Out', $fmt($ro->actual_move_out_date)],
                ['Utilities', $ro?->include_electricity_water ?: '—'],
                ['Internet', $ro?->include_wifi ?: '—'],
            ]
            : [
                ['Inspection Date', $fmt($ro?->start_date)],
                ['Hand Over Date', $fmt($ro->actual_move_in_date)],
                ['Kahrama Number', $ro?->property?->kahramaa ?: '—'],
                ['Gas Meter Number', $ro?->property?->gas_meter_number ?: '—'],
            ];
    @endphp
    <table class="meta">
        @foreach ($metaLeft as $r => $left)
            @php $right = $metaRight[$r] ?? null; @endphp
            <tr>
                <td class="lbl">{{ $left[0] }}</td><td class="val">{{ $left[1] }}</td>
                <td class="lbl">{{ $right[0] ?? '' }}</td><td class="val">{{ $right[1] ?? '' }}</td>
            </tr>
        @endforeach
    </table>

    <div class="sec">Inventory &amp; Condition</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:34px;">Sn.</th>
                <th style="width:30px;">Qty</th>
                <th style="width:42px;">Image</th>
                <th style="width:{{ $pct('desc') }};">Item Description</th>
                <th style="width:46px;">Move-In</th>
                <th style="width:{{ $pct('in') }};">Comments</th>
                @if ($showMoveOut)
                    <th style="width:46px;">Move-Out</th>
                    <th style="width:{{ $pct('out') }};">Comments</th>
                    <th style="width:60px;">Damage</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($grouped as $category => $lines)
                <tr class="cat"><td colspan="{{ $colCount }}">{{ $category ?: 'Others' }}</td></tr>
                @foreach ($lines as $line)
                    @php $sn++; $img = $imgSrc($line); @endphp
                    <tr>
                        <td class="c">{{ $sn }}</td>
                        <td class="c">{{ $line->qty }}</td>
                        <td class="c">@if ($img)<img src="{{ $img }}" style="width:30px; height:30px; object-fit:cover;">@endif</td>
                        <td>{{ $line->item?->name }}</td>
                        <td class="c">@if ($line->move_in_status?->value === 'ok')<span class="ok">✓</span>@endif</td>
                        <td>{{ $line->move_in_comment }}</td>
                        @if ($showMoveOut)
                            <td class="c">
                                @if ($line->move_out_status?->value === 'ok')<span class="ok">✓</span>
                                @elseif ($line->move_out_status?->value === 'not_ok')<span class="no">✗</span>@endif
                            </td>
                            <td>{{ $line->move_out_comment }}</td>
                            <td class="r">{{ $line->damage_cost > 0 ? number_format((float) $line->damage_cost, 2) : '' }}</td>
                        @endif
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="{{ $colCount }}" class="c muted" style="padding:10px;">No items recorded.</td></tr>
            @endforelse
            @if ($showMoveOut)
                <tr class="total">
                    <td colspan="8" class="r">Total Damage Cost</td>
                    <td class="r">{{ number_format($ro->checklistDamageTotal(), 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @foreach ($phases as $phaseKey => $phase)
        <div class="accept">
            <div class="ph">{{ $phase['label'] }}</div>
            <p class="decl">{{ $phase['decl'] }}</p>
            <table>
                <tr>
                    @foreach ($roles as $roleKey => $roleLabel)
                        @php $sig = $ro->checklistSignatureFor($phaseKey, $roleKey); $src = $sigSrc($sig); @endphp
                        <td class="sign-cell">
                            @if ($src)
                                <img class="sign-img" src="{{ $src }}" alt="signature">
                            @else
                                <div style="height:46px;"></div>
                            @endif
                            <div class="sign-line">
                                <span class="sign-name">{{ $sig?->signer_name ?: ($nameFor($roleKey) ?: '________________') }}</span><br>
                                {{ $roleLabel }}
                                @if ($sig?->signed_at)
                                    <br><span class="muted">{{ $sig->signed_at->format('d M Y') }}</span>
                                @endif
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endforeach
</div>
</body>
</html>
