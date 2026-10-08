{{-- The Unit Handover & Snagging document — ONE layout for every place it is shown:
     the PDF (web "Download PDF" and the technician app's PDF button, both rendered by
     App\Actions\RentOut\Checklist\GeneratePdfAction) and the on-screen sign page.

     Expects:
       $rentOut      with account, group, building, property, type, checklistLines.item,
                     checklistSignatures, fixtureAreas.entries, facility/leasingCoordinator
       $imageSrc     fn (?string $storagePath): ?string — data URIs for the PDF (Browsershot
                     renders from a temp file), public URLs on screen
       $companyLogo  optional src for the title band
       $interactive  optional; true on the sign page — unsigned signature boxes become
                     signature pads and a ready area offers the owner's pad
       $terms        optional; RentOutHandoverTerms::forPrint($rentOut) when the caller
                     already resolved it --}}
@php
    $ro = $rentOut;
    $tenant = $ro?->account;
    $interactive = $interactive ?? false;
    $terms = $terms ?? \App\Support\RentOutHandoverTerms::forPrint($rentOut);
    $sigSrc = fn ($sig) => $imageSrc($sig?->signature_path);
    // Line image with master-item fallback.
    $imgSrc = fn ($line) => $imageSrc($line->image_path ?: $line->item?->image_path);
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
    $userIdFor = fn ($role) => match ($role) {
        'facility_coordinator' => $ro->facility_coordinator_id,
        'leasing_coordinator' => $ro->leasing_coordinator_id,
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

<div class="ck-doc">
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
                        <td class="c">@if ($img)<img src="{{ $img }}" class="zoomable" data-img="{{ $img }}" style="width:30px; height:30px; object-fit:cover;" alt="">@endif</td>
                        <td>{{ $line->item?->name }}</td>
                        <td class="c">@if ($line->move_in_status?->value === 'ok')<span class="ok">✓</span>@endif</td>
                        <td>{{ $line->move_in_comment }}</td>
                        @if ($showMoveOut)
                            @php $outImg = $imageSrc($line->move_out_image_path); @endphp
                            <td class="c">
                                @if ($line->move_out_status?->value === 'ok')<span class="ok">✓</span>
                                @elseif ($line->move_out_status?->value === 'not_ok')<span class="no">✗</span>@endif
                                @if ($outImg)<img src="{{ $outImg }}" class="mo-img zoomable" data-img="{{ $outImg }}" alt="Move-out photo">@endif
                            </td>
                            <td>{{ $line->move_out_comment }}</td>
                            <td class="r">{{ $line->damage_cost > 0 ? number_format((float) $line->damage_cost, 2) : '' }}</td>
                        @endif
                    </tr>
                @endforeach
                {{-- Fixture Comments for this area, directly under its items. --}}
                @php $fxArea = $ro->fixtureAreaFor($category); @endphp
                @if ($fxArea && ($fxArea->entries->isNotEmpty() || $fxArea->isSigned()))
                    @include('print.rentout.partials.fixture-block', ['area' => $fxArea])
                @endif
            @empty
                <tr><td colspan="{{ $colCount }}" class="c muted" style="padding:10px;">No items recorded.</td></tr>
            @endforelse
            @if ($showMoveOut)
                <tr class="total">
                    <td colspan="8" class="r">Total Damage Cost</td>
                    <td class="r">{{ number_format($ro->checklistDamageTotal(), 2) }}</td>
                </tr>
            @endif
            {{-- Areas recorded by hand — no items, so the grouping above never reached them.
                 They follow the inventory totals rather than interrupting them. --}}
            @foreach ($ro->fixtureAreas as $fxArea)
                @if (! $grouped->has($fxArea->category) && ($fxArea->entries->isNotEmpty() || $fxArea->isSigned()))
                    <tr class="cat"><td colspan="{{ $colCount }}">{{ $fxArea->category }}</td></tr>
                    @include('print.rentout.partials.fixture-block', ['area' => $fxArea])
                @endif
            @endforeach
        </tbody>
    </table>

    {{-- data-pdf-foot: the block WeasyPrint measures to pin it to the page foot. --}}
    <div class="accept-group" data-pdf-foot>
    @foreach ($phases as $phaseKey => $phase)
        <div class="accept">
            <div class="ph">{{ $phase['label'] }}</div>
            {{-- Sanitised in App\Support\RichText before it ever reaches here. --}}
            <div class="decl">{!! $phase['decl'] !!}</div>
            @include('print.rentout.partials.checklist-signatures', ['phaseKey' => $phaseKey, 'withPads' => $interactive])
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
            @include('print.rentout.partials.checklist-signatures', ['phaseKey' => array_key_first($phases), 'withPads' => false])
        </div>
    </div>
@endif
</div>
