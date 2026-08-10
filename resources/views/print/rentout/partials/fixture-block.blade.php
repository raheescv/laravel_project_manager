{{-- Printed Fixture Comments for one area — its rectification entries and the owner's
     acceptance signature. Expects: $area, $colCount, $dataUri (the view's image inliner:
     Browsershot renders from a temp file, so every stored path travels as a data URI). --}}
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
                    $before = $dataUri($entry->before_image_path);
                    $after = $dataUri($entry->after_image_path);
                    $status = $entry->status ?? \App\Enums\RentOut\FixtureStatus::Pending;
                @endphp
                <tr>
                    <td class="c">{{ $n + 1 }}</td>
                    <td class="c">
                        @if ($before)<img src="{{ $before }}" class="fx-img" alt="">@else<span class="fx-noimg">—</span>@endif
                    </td>
                    <td class="c">
                        @if ($after)<img src="{{ $after }}" class="fx-img" alt="">@else<span class="fx-noimg">—</span>@endif
                    </td>
                    <td>{{ $entry->comments }}</td>
                    <td class="c" style="color:{{ $status->printColor() }}; font-weight:bold;">{{ $status->label() }}</td>
                    <td class="c">{{ $entry->completed_date?->format('d M Y') ?: '—' }}</td>
                </tr>
            @endforeach
            <tr class="fx-sign">
                <td colspan="4">Owner acceptance — {{ $area->category }}</td>
                <td colspan="2" class="c">
                    @php $sig = $dataUri($area->owner_signature_path); @endphp
                    @if ($sig)
                        <img src="{{ $sig }}" class="fx-sig-img" alt="Owner signature">
                        <div class="fx-sig-line">{{ $area->owner_name ?: 'Owner' }} ·
                            {{ $area->owner_signed_at?->format('d M Y') }}</div>
                    @else
                        <div class="fx-sig-line" style="margin-top:16px;">Not signed</div>
                    @endif
                </td>
            </tr>
        </table>
    </td>
</tr>
