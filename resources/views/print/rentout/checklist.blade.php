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
        .items th { background: #efe9d6; color: #4a432b; border: 1px solid #ccc6b0; padding: 4px 6px; font-size: 9.5px; }
        .items td { border: 1px solid #ddd; padding: 3px 6px; font-size: 9.5px; }
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
    $phases = [
        'move_in' => ['label' => 'To be accomplished during Move-In',
            'decl' => 'I, ' . ($tenant?->name ?? 'the Lessee') . ', hereby confirm that the above mentioned furniture, kitchen appliances & accessories were physically checked and received by me in good condition.'],
        'move_out' => ['label' => 'To be accomplished during Move-Out',
            'decl' => 'We hereby confirm that the above mentioned items were physically checked and received from ' . ($tenant?->name ?? 'the Lessee') . '. The Lessee confirms no further claim once the access card/s are returned.'],
    ];
    $roles = ['lessee' => 'Lessee', 'facility_coordinator' => 'Facility Coordinator', 'leasing_coordinator' => 'Leasing Coordinator'];
    $nameFor = fn ($role) => match ($role) {
        'lessee' => $tenant?->name,
        'facility_coordinator' => $ro->facilityCoordinator?->name,
        'leasing_coordinator' => $ro->leasingCoordinator?->name,
        default => null,
    };
    $grouped = $ro->checklistLines->groupBy(fn ($l) => $l->item?->category ?: 'Others');
    $sn = 0;
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
    <table class="meta">
        <tr>
            <td class="lbl">Group / Project</td><td class="val">{{ $ro?->group?->name ?? '—' }}</td>
            <td class="lbl">Lease Start</td><td class="val">{{ $fmt($ro?->start_date) }}</td>
        </tr>
        <tr>
            <td class="lbl">Building</td><td class="val">{{ $ro?->building?->name ?? '—' }}</td>
            <td class="lbl">Actual Move-In</td><td class="val">{{ $fmt($ro->actual_move_in_date) }}</td>
        </tr>
        <tr>
            <td class="lbl">Property / Unit</td><td class="val">{{ $ro?->property?->number ?? '—' }}</td>
            <td class="lbl">Lease End</td><td class="val">{{ $fmt($ro?->end_date) }}</td>
        </tr>
        <tr>
            <td class="lbl">Type</td><td class="val">{{ $ro?->type?->name ?? '—' }}</td>
            <td class="lbl">Actual Move-Out</td><td class="val">{{ $fmt($ro->actual_move_out_date) }}</td>
        </tr>
        <tr>
            <td class="lbl">Tenant Name</td><td class="val">{{ $tenant?->name ?? '—' }}</td>
            <td class="lbl">Utilities</td><td class="val">{{ $ro?->include_electricity_water ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Mobile No.</td><td class="val">{{ $tenant?->mobile ?? '—' }}</td>
            <td class="lbl">Internet</td><td class="val">{{ $ro?->include_wifi ?: '—' }}</td>
        </tr>
    </table>

    <div class="sec">Inventory &amp; Condition</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:34px;">Sn.</th>
                <th style="width:30px;">Qty</th>
                <th style="width:42px;">Image</th>
                <th>Item Description</th>
                <th style="width:46px;">Move-In</th>
                <th style="width:120px;">Comments</th>
                <th style="width:46px;">Move-Out</th>
                <th style="width:120px;">Comments</th>
                <th style="width:60px;">Damage</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($grouped as $category => $lines)
                <tr class="cat"><td colspan="9">{{ $category ?: 'Others' }}</td></tr>
                @foreach ($lines as $line)
                    @php $sn++; $img = $imgSrc($line); @endphp
                    <tr>
                        <td class="c">{{ $sn }}</td>
                        <td class="c">{{ $line->qty }}</td>
                        <td class="c">@if ($img)<img src="{{ $img }}" style="width:30px; height:30px; object-fit:cover;">@endif</td>
                        <td>{{ $line->item?->name }}</td>
                        <td class="c">@if ($line->move_in_status?->value === 'ok')<span class="ok">✓</span>@endif</td>
                        <td>{{ $line->move_in_comment }}</td>
                        <td class="c">
                            @if ($line->move_out_status?->value === 'ok')<span class="ok">✓</span>
                            @elseif ($line->move_out_status?->value === 'not_ok')<span class="no">✗</span>@endif
                        </td>
                        <td>{{ $line->move_out_comment }}</td>
                        <td class="r">{{ $line->damage_cost > 0 ? number_format((float) $line->damage_cost, 2) : '' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="9" class="c muted" style="padding:10px;">No items recorded.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="8" class="r">Total Damage Cost</td>
                <td class="r">{{ number_format($ro->checklistDamageTotal(), 2) }}</td>
            </tr>
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
