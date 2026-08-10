{{-- Fixture Comments for one area of the unit. Rendered under that area's item rows on
     the Checklist tab. Expects: $a (index into $fixtureAreas), $area, $statusOptions and
     $canRemoveArea (false for areas the checklist items put there — removing one of those
     is pointless, it reappears on the next reload). --}}
@php
    $counts = collect($area['entries'])->groupBy(fn($e) => $e['status'] ?: 'pending')->map->count();
@endphp
<div class="cl-fx" wire:key="fx-area-{{ $a }}-{{ $area['category'] }}">
    <div class="cl-fx-head">
        <span class="cl-fx-title">
            <i class="fa fa-wrench me-2 opacity-75"></i>Fixture Comments — {{ $area['category'] }}
            <span class="cl-fx-ar">ملاحظات التشطيبات</span>
        </span>
        @if (count($area['entries']))
            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">{{ count($area['entries']) }}
                {{ Str::plural('entry', count($area['entries'])) }}</span>
        @endif
        <span class="flex-grow-1"></span>
        @foreach ($statusOptions as $value => $label)
            @if (($counts[$value] ?? 0) > 0)
                <span class="badge rounded-pill bg-{{ \App\Enums\RentOut\FixtureStatus::from($value)->color() }}-subtle
                    text-{{ \App\Enums\RentOut\FixtureStatus::from($value)->color() }}-emphasis">
                    {{ $counts[$value] }} {{ $label }}
                </span>
            @endif
        @endforeach
        @if ($area['signature_url'])
            <span class="badge rounded-pill bg-success-subtle text-success-emphasis" title="Signed {{ $area['owner_signed_at'] }}">
                <i class="fa fa-check me-1"></i>Owner accepted
            </span>
        @endif
        @if ($canRemoveArea)
            <button type="button" class="btn btn-sm cl-trash" wire:click="removeFixtureArea({{ $a }})"
                wire:confirm="Remove the {{ $area['category'] }} fixture block and its entries?" title="Remove area">
                <i class="fa fa-trash"></i>
            </button>
        @endif
    </div>

    @forelse ($area['entries'] as $e => $entry)
        <div class="cl-fx-entry" wire:key="fx-entry-{{ $a }}-{{ $e }}-{{ $entry['id'] ?? 'new' }}">
            @foreach (['before' => 'Before', 'after' => 'After'] as $which => $label)
                @php $url = $entry[$which . '_image_url'] ?? null; @endphp
                <div class="cl-fx-photo-cell">
                    <span class="cl-fx-lbl">{{ $label }}</span>
                    <div class="cl-fx-photo {{ $url ? '' : 'is-empty' }}">
                        @if ($url)
                            <img src="{{ $url }}" class="zoomable" data-img="{{ $url }}" alt="{{ $label }}"
                                style="cursor:zoom-in;" title="Click to enlarge">
                        @else
                            <i class="fa fa-picture-o"></i>
                        @endif
                        <label class="cl-fx-btn" title="{{ $url ? 'Replace photo' : 'Upload photo' }}">
                            <i class="fa fa-camera"></i>
                            <input type="file" class="d-none" accept="image/*"
                                wire:model="newFixtureImages.{{ $a }}.{{ $e }}.{{ $which }}">
                        </label>
                        @if ($url)
                            <button type="button" class="cl-fx-btn cl-fx-btn-del" title="Remove photo"
                                wire:click="removeFixtureImage({{ $a }}, {{ $e }}, '{{ $which }}')">
                                <i class="fa fa-times"></i>
                            </button>
                        @endif
                    </div>
                    <div wire:loading wire:target="newFixtureImages.{{ $a }}.{{ $e }}.{{ $which }}" class="small text-muted">
                        <i class="fa fa-spinner fa-spin"></i>
                    </div>
                </div>
            @endforeach

            <div class="cl-fx-cmt">
                <span class="cl-fx-lbl">Comments</span>
                <textarea class="form-control form-control-sm cl-inp" rows="3" placeholder="Tiles need to fit, switch to replace, paint damage…"
                    wire:model.blur="fixtureAreas.{{ $a }}.entries.{{ $e }}.comments"></textarea>
            </div>

            <div class="cl-fx-status">
                <span class="cl-fx-lbl">Status</span>
                <select class="form-select form-select-sm cl-inp" wire:model.live="fixtureAreas.{{ $a }}.entries.{{ $e }}.status">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="cl-fx-date">
                <span class="cl-fx-lbl">Completed</span>
                <input type="date" class="form-control form-control-sm cl-inp"
                    wire:model.blur="fixtureAreas.{{ $a }}.entries.{{ $e }}.completed_date">
            </div>

            <div class="cl-fx-del">
                <button type="button" class="btn btn-sm cl-trash" wire:click="removeFixtureEntry({{ $a }}, {{ $e }})"
                    title="Remove entry">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    @empty
        <div class="cl-fx-empty">No fixture comments recorded for this area.</div>
    @endforelse

    <div class="cl-fx-foot">
        <button type="button" class="btn btn-sm cl-fx-add" wire:click="addFixtureEntry({{ $a }})">
            <i class="fa fa-plus me-1"></i>Add entry
        </button>
        <span class="flex-grow-1"></span>
        @if ($area['signature_url'])
            <span class="small text-success"><i class="fa fa-pencil-square-o me-1"></i>Signed by
                {{ $area['owner_name'] ?: 'owner' }} · {{ $area['owner_signed_at'] }}</span>
        @else
            <span class="small text-muted">Owner signs this area on the checklist print page</span>
        @endif
    </div>
</div>
