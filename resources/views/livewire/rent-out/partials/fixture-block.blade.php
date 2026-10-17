{{-- Fixture Comments for one area of the unit, shown under that room's items on the
     Checklist tab. Expects: $a (index into $fixtureAreas), $area, $statusOptions and
     $canRemoveArea (false for areas the checklist items put there — removing one of those
     is pointless, it reappears on the next reload). --}}
@php
    use App\Enums\RentOut\FixtureStatus;

    $counts = collect($area['entries'])->groupBy(fn ($e) => $e['status'] ?: FixtureStatus::Pending->value)->map->count();
@endphp
<div class="clx-fx" wire:key="fx-area-{{ $a }}-{{ $area['category'] }}">
    <div class="clx-fx-head">
        <i class="fa fa-wrench clx-brand"></i>
        <span>Fixture comments <span class="clx-ar">ملاحظات التشطيبات</span></span>
        <span class="clx-grow"></span>
        @foreach ($statusOptions as $value => $label)
            @if (($counts[$value] ?? 0) > 0)
                <span class="clx-pill is-{{ FixtureStatus::from($value)->color() }}">{{ $counts[$value] }} {{ $label }}</span>
            @endif
        @endforeach
        @if ($area['signature_url'])
            <span class="clx-pill is-success" title="Signed {{ $area['owner_signed_at'] }}"><i class="fa fa-check"></i> Owner accepted</span>
        @endif
        @if ($canRemoveArea)
            <button type="button" class="clx-icon-btn" wire:click="removeFixtureArea({{ $a }})"
                wire:confirm="Remove the {{ $area['category'] }} area and its fixture comments?" title="Remove area">
                <i class="fa fa-trash-o"></i>
            </button>
        @endif
    </div>

    @forelse ($area['entries'] as $e => $entry)
        <div class="clx-fx-entry" wire:key="fx-entry-{{ $a }}-{{ $e }}-{{ $entry['id'] ?? 'new' }}">
            @foreach (['before' => 'Before', 'after' => 'After'] as $which => $label)
                <x-rent-out.checklist.photo class="clx-fx-photo" :label="$label" :url="$entry[$which.'_image_url'] ?? null"
                    model="newFixtureImages.{{ $a }}.{{ $e }}.{{ $which }}"
                    remove="removeFixtureImage({{ $a }}, {{ $e }}, '{{ $which }}')" />
            @endforeach

            <textarea class="clx-in clx-fx-comment" rows="3" placeholder="What needs fixing — tiles to refit, a switch to replace, paint damage…"
                wire:model.blur="fixtureAreas.{{ $a }}.entries.{{ $e }}.comments"></textarea>

            <div class="clx-fx-status">
                <span class="clx-lbl">Status</span>
                <div class="clx-chips">
                    @foreach ($statusOptions as $value => $label)
                        <button type="button" @class(['clx-chip', 'is-'.FixtureStatus::from($value)->color() => $entry['status'] === $value])
                            wire:click="$set('fixtureAreas.{{ $a }}.entries.{{ $e }}.status', '{{ $value }}')">{{ $label }}</button>
                    @endforeach
                </div>
                <span class="clx-lbl mt-1">Completion date</span>
                <div class="d-flex gap-2">
                    <input type="date" class="clx-in" wire:model.blur="fixtureAreas.{{ $a }}.entries.{{ $e }}.completed_date">
                    <button type="button" class="clx-icon-btn" wire:click="removeFixtureEntry({{ $a }}, {{ $e }})"
                        wire:confirm="Remove this fixture comment and its photos?" title="Remove entry">
                        <i class="fa fa-trash-o"></i>
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="clx-fx-empty">Nothing to fix in this area yet.</div>
    @endforelse

    <div class="clx-fx-foot">
        <button type="button" class="clx-btn" wire:click="addFixtureEntry({{ $a }})"><i class="fa fa-plus"></i> Add entry</button>
        <span class="clx-grow"></span>
        @if ($area['signature_url'])
            <span class="text-success"><i class="fa fa-pencil-square-o me-1"></i>Signed by {{ $area['owner_name'] ?: 'owner' }} · {{ $area['owner_signed_at'] }}</span>
        @else
            <span>The owner signs this area on the hand-over page</span>
        @endif
    </div>
</div>
