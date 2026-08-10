{{-- Read-only Fixture Comments for one area, with the owner's acceptance pad. Shown on
     the checklist screen under that area's items. Expects: $rentOut, $area. --}}
@php
    $signed = $area->isSigned();
    $ready = $area->isReadyForAcceptance();
@endphp
<div class="fx-block">
    <div class="fx-head">
        <span class="fx-title">Fixture Comments — {{ $area->category }}<span class="fx-ar">ملاحظات التشطيبات</span></span>
        <span class="flex-grow-1"></span>
        @foreach ($area->statusCounts() as $value => $count)
            @php $status = \App\Enums\RentOut\FixtureStatus::tryFrom($value); @endphp
            @if ($status)
                <span class="badge rounded-pill bg-{{ $status->color() }}-subtle text-{{ $status->color() }}-emphasis">
                    {{ $count }} {{ $status->label() }}
                </span>
            @endif
        @endforeach
        @if ($signed)
            <span class="badge rounded-pill bg-success-subtle text-success-emphasis"><i class="fa fa-check me-1"></i>Accepted</span>
        @endif
    </div>

    @foreach ($area->entries as $entry)
        <div class="fx-entry">
            @foreach (['before' => 'Before', 'after' => 'After'] as $which => $label)
                @php $url = $entry->{$which . '_image_url'}; @endphp
                <div>
                    <span class="fx-lbl">{{ $label }}</span>
                    <div class="fx-photo {{ $url ? '' : 'is-empty' }}">
                        @if ($url)
                            <img src="{{ $url }}" alt="{{ $label }}" class="zoomable" data-img="{{ $url }}"
                                style="cursor:zoom-in;" title="Click to enlarge">
                        @else
                            <i class="fa fa-picture-o"></i>
                        @endif
                    </div>
                </div>
            @endforeach
            <div class="fx-cmt">
                {{ $entry->comments ?: '—' }}
                {{-- Labelled as the completion date rather than "Completed <date>": the date is
                     optional and independent of status, so an in-progress entry can carry one. --}}
                <div class="fx-date">
                    Completion date — {{ $entry->completed_date?->format('d M Y') ?: 'not set' }}
                </div>
            </div>
            <div class="text-center">
                @php $status = $entry->status ?? \App\Enums\RentOut\FixtureStatus::Pending; @endphp
                <span class="badge rounded-pill bg-{{ $status->color() }}-subtle text-{{ $status->color() }}-emphasis">
                    {{ $status->label() }}
                </span>
            </div>
        </div>
    @endforeach

    <div class="fx-sign">
        @if ($signed)
            <p class="section-title mb-1">Owner acceptance — {{ $area->category }}</p>
            <div class="signed-box">
                <img src="{{ $area->owner_signature_url }}" alt="Owner signature" style="max-height:60px;">
                <div class="small text-muted mt-1 border-top pt-1">
                    {{ $area->owner_name ?: 'Owner' }} · signed {{ $area->owner_signed_at?->format('d M Y, H:i') }}
                </div>
            </div>
            @can('rent out checklist.edit')
                <div class="no-print mt-2">
                    @livewire('rent-out.checklist.sign-fixture', ['rentOut' => $rentOut, 'area' => $area], key('fx-sign-' . $area->id))
                </div>
            @endcan
        @elseif ($ready)
            @can('rent out checklist.edit')
                @livewire('rent-out.checklist.sign-fixture', ['rentOut' => $rentOut, 'area' => $area], key('fx-sign-' . $area->id))
            @else
                <p class="section-title mb-1">Owner acceptance — {{ $area->category }}</p>
                <div class="signed-box text-muted small">Not signed</div>
            @endcan
        @else
            <p class="section-title mb-1">Owner acceptance — {{ $area->category }}</p>
            <div class="text-muted small">
                Available once every entry in this area is <b>Completed</b>.
            </div>
        @endif
    </div>
</div>
