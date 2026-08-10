@php
    // Warranty / handover clauses written on this booking's Checklist tab — an
    // annex printed on its own page after the signatures. Empty ([]) on a rental,
    // and on any booking that has none. Resolved up here because the page-body
    // height in <style> below depends on it.
    $terms = \App\Support\RentOutHandoverTerms::forPrint($rentOut);
    // The stretched body has to cover every page EXCEPT the clause page, otherwise
    // it runs a page long and PrintController throws the stretched pass away.
    // Clauses longer than one page do exactly that, and the honest unstretched
    // first pass is printed instead: correct, just without the acknowledgment
    // glued to the foot of its page.
    $bodyPages = max(1, (int) ($pages ?? 1) - (empty($terms) ? 0 : 1));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unit Handover & Snagging</title>
    <style>
        * { box-sizing: border-box; }
        /* Rendered by Chrome (Browsershot) — the Arabic faces are what shape and
           order RTL clauses in the declaration correctly. */
        body { font-family: 'DejaVu Sans', 'Noto Naskh Arabic', 'Noto Sans Arabic', 'Geeza Pro', Arial, sans-serif;
               font-size: 10px; color: #2b2b2b; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        /* The acknowledgment signs off the document, so it sits at the FOOT of the last
           page rather than trailing the inventory table. Chrome has no "footer on the
           last page" rule, so the page body is stretched to a whole number of pages
           (counted by PrintController from a first pass) and the block is pushed down
           by the auto margin.
           277mm = A4's 297mm less the 10mm top and bottom margins PrintController
           prints with — vh can't be used here, it measures the whole page, margins
           included, and would spill onto an extra page. 2mm is shaved off so rounding
           can't do the same. */
        .wrap { padding: 0; display: flex; flex-direction: column;
                min-height: calc({{ $bodyPages }} * 277mm - 2mm); }
        .accept-group { margin-top: auto; }
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
        /* Handover terms — bilingual clauses written on the booking's Checklist tab.
           They are an annex to the signed form, so they open a page of their own
           after the acknowledgment. Two columns so a clause's English and Arabic
           wording sit side by side on the same line; the pair never splits across a
           page break. The bodies are the same rich text as the declaration, so they
           borrow .decl. */
        .terms-page { page-break-before: always; break-before: page; }
        .terms-page .sec-split { margin-top: 0; }
        .sec-split { margin: 12px 0 6px; background: #7a6a2f; color: #fff; border-radius: 3px; table-layout: fixed; }
        .sec-split td { padding: 4px 8px; font-size: 10px; font-weight: bold; letter-spacing: .4px; text-transform: uppercase; }
        .sec-split .ar { text-align: right; direction: rtl; text-transform: none; letter-spacing: 0; }
        .terms { table-layout: fixed; }
        .terms td { border: 1px solid #ddd; padding: 5px 7px; vertical-align: top;
                    word-wrap: break-word; overflow-wrap: break-word; }
        .terms tr:nth-child(even) td { background: #fcfbf7; }
        .terms tr { page-break-inside: avoid; break-inside: avoid; }
        .terms-t { font-size: 9.4px; font-weight: bold; color: #7a6a2f; margin-bottom: 2px; }
        .terms .ar, .terms .ar .decl { direction: rtl; text-align: right; }
        .terms .decl { margin: 0; }
        /* Signatures repeated under the clauses. Kept whole so a signature never
           lands on a page of its own away from the terms it signs. */
        .terms-sign { margin-top: 14px; page-break-inside: avoid; break-inside: avoid; }
        .accept { border: 1px solid #d8d4c4; border-radius: 4px; padding: 8px 10px; margin-bottom: 8px; }
        .accept .ph { font-size: 11px; font-weight: bold; color: #7a6a2f; text-transform: uppercase; margin-bottom: 4px; }
        /* The declaration is rich text edited in Settings → Rent Out Settings →
           Checklist Notes, so it can carry headings, clause lists and RTL (Arabic)
           paragraphs. These rules keep whatever it holds inside the printed block. */
        .decl { font-size: 8.7px; color: #444; margin: 0 0 8px; line-height: 1.5; }
        .decl > *:first-child { margin-top: 0; }
        .decl > *:last-child { margin-bottom: 0; }
        .decl p { margin: 0 0 5px; }
        /* A blank line the author left stays a breather, not a whole empty line box. */
        .decl p:empty, .decl p:has(> br:only-child) { margin: 0; height: 4px; }
        .decl h1, .decl h2, .decl h3, .decl h4, .decl h5, .decl h6 {
            font-size: 9.4px; font-weight: bold; color: #7a6a2f; margin: 7px 0 3px; text-transform: none; }
        .decl h1, .decl h2 { font-size: 10px; text-transform: uppercase; letter-spacing: .3px; }
        .decl ul, .decl ol { margin: 0 0 5px; padding-left: 14px; }
        .decl ul[dir="rtl"], .decl ol[dir="rtl"] { padding-left: 0; padding-right: 14px; }
        .decl li { margin-bottom: 1px; }
        /* Indented, not quoted: a browser's indent command wraps the line in a
           blockquote, so a quote bar here would mark text the author only indented. */
        .decl blockquote { margin: 0 0 5px; padding-inline-start: 18px; }
        .decl a { color: #444; text-decoration: none; }
        .decl [dir="rtl"] { direction: rtl; text-align: right; }
        .decl table { margin-bottom: 5px; }
        .decl td, .decl th { border: 1px solid #ddd; padding: 2px 4px; }
        /* Fixed layout keeps the three signature cells at a third each — otherwise a long
           signer name widens its cell and pushes the table past the page edge, clipping
           the last signatory. break-word wraps the name inside its third instead. */
        /* The acknowledgment reads as one statement: its declaration and the signatures
           under it move to the next page together rather than splitting across the
           break. (A declaration taller than a whole page still has to break — nothing
           can keep that together.) */
        .accept { page-break-inside: avoid; break-inside: avoid; }
        .items tr, .decl li { page-break-inside: avoid; }
        .decl h1, .decl h2, .decl h3, .decl h4 { page-break-after: avoid; }
        .sign-table { table-layout: fixed; width: 100%; page-break-inside: avoid; }
        /* Top-aligned so every signature rule sits on the same line — a name that wraps
           to two lines grows downwards instead of lifting its own rule. */
        .sign-cell { width: 33.33%; vertical-align: top; padding: 4px 6px; text-align: center;
                     word-wrap: break-word; overflow-wrap: break-word; }
        .sign-img { max-width: 100%; height: 46px; margin-bottom: 2px; }
        .sign-line { border-top: 1px solid #555; padding-top: 3px; font-size: 8.5px; }
        .sign-name { font-weight: bold; font-size: 9px; }
        .muted { color: #8a8575; }
    </style>
</head>
<body>
@php
    $ro = $rentOut;
    $tenant = $ro?->account;
    // Browsershot renders the page from a temp file, so images have to travel with
    // it — every stored path is inlined as a data URI.
    $dataUri = function (?string $relative) {
        if (! $relative) {
            return null;
        }
        $path = public_path('storage/' . $relative);
        if (! is_file($path)) {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'png';

        return 'data:image/' . ($ext === 'svg' ? 'svg+xml' : $ext) . ';base64,' . base64_encode(file_get_contents($path));
    };
    $sigSrc = fn ($sig) => $dataUri($sig?->signature_path);
    // Line image with master-item fallback.
    $imgSrc = fn ($line) => $dataUri($line->image_path ?: $line->item?->image_path);
    $fmt = fn ($d) => $d ? $d->format('d M Y') : '—';
    // Move-Out / damage tracking only applies to rentals — a lease/sale never hands the unit back.
    $showMoveOut = $ro?->agreement_type === \App\Enums\RentOut\AgreementType::Rental;
    $colCount = $showMoveOut ? 9 : 6;
    // Headings + declarations are editable per agreement type in
    // Settings → Rent Out Settings → Checklist Notes.
    $phases = \App\Support\RentOutChecklistNotes::phasesFor($ro);
    $roles = collect(\App\Enums\RentOut\ChecklistSignatoryRole::cases())
        ->mapWithKeys(fn ($role) => [$role->value => $role->labelFor($ro?->agreement_type)])
        ->all();
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
                // The lease/sale inspection and handover are one and the same visit — both
                // lines report the single date captured on the checklist.
                ['Inspection Date', $fmt($ro->actual_move_in_date)],
                ['Hand Over Date', $fmt($ro->actual_move_out_date)],
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

    <div class="accept-group">
    @foreach ($phases as $phaseKey => $phase)
        <div class="accept">
            <div class="ph">{{ $phase['label'] }}</div>
            {{-- Sanitised in App\Support\RichText before it ever reaches here. --}}
            <div class="decl">{!! $phase['decl'] !!}</div>
            @include('print.rentout.partials.checklist-signatures', ['phaseKey' => $phaseKey])
        </div>
    @endforeach
    </div>
</div>

{{-- The warranty / handover clauses are an annex to the signed form, so they follow
     the signatures on a page of their own rather than pushing them down the sheet. --}}
@if (! empty($terms))
    @php $bilingual = $terms['has_arabic']; @endphp
    <div class="terms-page">
        <table class="sec-split">
            <tr>
                <td @if ($bilingual) style="width:50%" @endif>{{ $terms['heading_en'] }}</td>
                @if ($bilingual)
                    <td class="ar" style="width:50%" dir="rtl">{{ $terms['heading_ar'] }}</td>
                @endif
            </tr>
        </table>
        <table class="terms">
            <tbody>
                @foreach ($terms['clauses'] as $clause)
                    <tr>
                        <td @if ($bilingual) style="width:50%" @endif>
                            <div class="terms-t">{{ trim($clause['no_en'].' '.$clause['title_en']) }}</div>
                            {{-- Sanitised in App\Support\RichText before it ever reaches here. --}}
                            <div class="decl">{!! $clause['body_en'] !!}</div>
                        </td>
                        @if ($bilingual)
                            <td class="ar" style="width:50%" dir="rtl">
                                <div class="terms-t">{{ trim($clause['no_ar'].' '.$clause['title_ar']) }}</div>
                                <div class="decl">{!! $clause['body_ar'] !!}</div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{-- The annex is signed against the same signatures as the handover block it
             annexes, so they are printed again under the clauses rather than leaving
             the page unsigned. Terms only print on a lease/sale, which has the single
             handover phase — hence the first (and only) phase key. --}}
        <div class="terms-sign">
            @include('print.rentout.partials.checklist-signatures', ['phaseKey' => array_key_first($phases)])
        </div>
    </div>
@endif
</body>
</html>
