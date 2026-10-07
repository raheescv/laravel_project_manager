{{-- Fixture Comments for one area — its rectification entries and the owner's
     acceptance signature, as part of the shared checklist document. Expects: $area,
     $colCount, $imageSrc (data URIs in the PDF, public URLs on screen), $ro, and
     $interactive (the sign page: offer the owner's pad once the area is ready). --}}
<tr class="fx">
    <td colspan="{{ $colCount }}">
        <table class="fx-table">
            <tr class="fx-head">
                <td colspan="6">Fixture Comments — {{ $area->category }}<span class="ar">ملاحظات التشطيبات</span></td>
            </tr>
            <tr class="fx-cols">
                <td style="width:22px;">#</td>
                <td style="width:62px;">Before</td>
                <td style="width:62px;">After</td>
                <td style="text-align:left;">Comments</td>
                <td style="width:66px;">Status</td>
                <td style="width:62px;">Completed</td>
            </tr>
            @foreach ($area->entries as $n => $entry)
                @php
                    $before = $imageSrc($entry->before_image_path);
                    $after = $imageSrc($entry->after_image_path);
                    $status = $entry->status ?? \App\Enums\RentOut\FixtureStatus::Pending;
                @endphp
                <tr>
                    <td class="c">{{ $n + 1 }}</td>
                    <td class="c">
                        @if ($before)<img src="{{ $before }}" class="fx-img zoomable" data-img="{{ $before }}" alt="">@else<span class="fx-noimg">—</span>@endif
                    </td>
                    <td class="c">
                        @if ($after)<img src="{{ $after }}" class="fx-img zoomable" data-img="{{ $after }}" alt="">@else<span class="fx-noimg">—</span>@endif
                    </td>
                    <td>{{ $entry->comments }}</td>
                    <td class="c" style="color:{{ $status->printColor() }}; font-weight:bold;">{{ $status->label() }}</td>
                    <td class="c">{{ $entry->completed_date?->format('d M Y') ?: '—' }}</td>
                </tr>
            @endforeach
            <tr class="fx-sign">
                <td colspan="4">Owner acceptance — {{ $area->category }}</td>
                <td colspan="2" class="c">
                    @php $sig = $imageSrc($area->owner_signature_path); @endphp
                    @if ($sig)
                        <img src="{{ $sig }}" class="fx-sig-img" alt="Owner signature">
                        <div class="fx-sig-line">{{ $area->owner_name ?: 'Owner' }} ·
                            {{ $area->owner_signed_at?->format('d M Y') }}</div>
                    @else
                        <div class="fx-sig-line" style="margin-top:16px;">{{ ! empty($interactive) && $area->isReadyForAcceptance() ? 'Sign below' : 'Not signed' }}</div>
                    @endif
                </td>
            </tr>
            {{-- On screen the owner signs right here once every entry is completed (the
                 same rule SignFixtureAction enforces). The pad is its own full-width row:
                 the signature cell above is far too narrow to draw in. --}}
            @if (! empty($interactive) && ! $area->isSigned() && $area->isReadyForAcceptance())
                <tr class="fx-pad">
                    <td colspan="6">
                        @livewire('rent-out.checklist.sign-fixture', ['rentOut' => $ro, 'area' => $area], key('fx-sign-' . $area->id))
                    </td>
                </tr>
            @endif
        </table>
    </td>
</tr>
