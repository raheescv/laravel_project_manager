@php
    use App\Enums\RentOut\AgreementType;
    use App\Enums\RentOut\ChecklistSignatoryRole;

    // Move-Out / damage tracking only applies to rentals — a lease/sale never hands the unit back.
    $showMoveOut = $agreement_type == AgreementType::Rental;
    $phases = $showMoveOut ? ['move_in' => 'Move-In', 'move_out' => 'Move-Out'] : ['move_in' => 'Hand-over'];
    $checkedFor = fn (string $phase) => collect($lines)->filter(fn ($l) => filled($l[$phase.'_status'] ?? null))->count();
    $percent = fn (int $done, int $total) => $total ? (int) round($done / $total * 100) : 0;
    $doneWord = ['move_in' => 'present', 'move_out' => 'inspected'];
    $allWord = ['move_in' => 'All present', 'move_out' => 'All good'];
@endphp
{{-- Walkthrough Split: the rooms on the left, the open room's items on the right, worked
     one phase at a time. The room and phase switch on the device (entangled, so a
     re-render keeps them); anything marked data-only="<phase>" shows in that phase only.
     `sub` splits a lease/sale into the items and the bilingual handover terms. --}}
<div class="clx" x-data="{ sub: 'items', room: $wire.entangle('room'), phase: $wire.entangle('phase') }" data-phase="{{ $phase }}"
    x-bind:data-phase="phase">
    @include('livewire.rent-out.partials.checklist-styles')

    @if (! $showMoveOut)
        <div class="clx-seg mb-3">
            <button type="button" :class="{ 'on': sub === 'items' }" x-on:click="sub = 'items'"><i class="fa fa-list-alt"></i> Items</button>
            <button type="button" :class="{ 'on': sub === 'terms' }" x-on:click="sub = 'terms'"><i class="fa fa-balance-scale"></i> Handover Terms</button>
        </div>
    @endif

    <div x-show="sub === 'items'">
        {{-- Hero: what this is, the phase being worked, and the document actions --}}
        <div class="clx-hero">
            <div>
                <h3><i class="fa fa-check-square-o clx-brand"></i> Hand-over checklist</h3>
                <div class="clx-meta">
                    {{ count($lines) }} {{ Str::plural('item', count($lines)) }} in {{ count($rooms) }} {{ Str::plural('room', count($rooms)) }}
                    @if ($showMoveOut)
                        · Damage <b class="text-danger">QAR {{ number_format($damageTotal, 2) }}</b>
                    @endif
                </div>
                @if ($showMoveOut)
                    <div class="clx-seg mt-2">
                        @foreach ($phases as $key => $label)
                            <button type="button" @class(['on' => $phase === $key]) :class="{ 'on': phase === '{{ $key }}' }" x-on:click="phase = '{{ $key }}'">
                                <i class="fa {{ $key === 'move_in' ? 'fa-sign-in' : 'fa-sign-out' }}"></i>{{ $label }}
                                <span class="clx-seg-n">{{ $checkedFor($key) }}/{{ count($lines) }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="clx-actions">
                <a href="{{ route('print::rentout::checklist', $rentOutId) }}" target="_blank" class="clx-btn"><i class="fa fa-file-pdf-o"></i> PDF</a>
                <a href="{{ route('property::rent_out::checklist::print', $rentOutId) }}" target="_blank" class="clx-btn"><i class="fa fa-pencil-square-o"></i> Sign hand-over</a>
                <button type="button" class="clx-btn is-primary" wire:click="openAddItems"><i class="fa fa-plus"></i> Add items</button>
            </div>
        </div>

        {{-- Hand-over status — one card per phase: items checked, the three signatures, sealed or not.
             The technician app works the same phases; sealing here or there is the same step. --}}
        @if (count($lines) > 0)
            <div class="clx-phases">
                @foreach ($handover as $h)
                    @php $signed = collect($h['signatures'])->where('signed', true)->count(); @endphp
                    <div class="clx-phase">
                        <div class="clx-phase-top">
                            <span class="clx-phase-title"><i class="fa fa-key clx-brand"></i>{{ $h['title'] }}</span>
                            @if ($h['sealed'])
                                <span class="clx-pill is-success"><i class="fa fa-lock"></i> Sealed {{ $h['sealed_on'] }}</span>
                            @elseif ($h['ready'])
                                <span class="clx-pill is-warning"><i class="fa fa-unlock"></i> Ready to seal</span>
                            @else
                                <span class="clx-pill is-muted">{{ $signed }}/3 signed</span>
                            @endif
                        </div>
                        <div class="clx-phase-count"><span>Items checked</span><b>{{ $h['checked'] }}/{{ $h['total'] }}</b></div>
                        <div class="clx-meter"><i style="width: {{ $percent($h['checked'], $h['total']) }}%"></i></div>
                        <div class="clx-sigs">
                            @foreach ($h['signatures'] as $sig)
                                <span @class(['clx-sig', 'on' => $sig['signed']]) title="{{ $sig['signed'] ? 'Signed '.$sig['at'] : 'Not signed yet' }}">
                                    <span class="clx-sig-av"><i class="fa {{ $sig['signed'] ? 'fa-check' : 'fa-pencil' }}"></i></span>
                                    {{ $sig['label'] }}@if ($sig['signed'] && $sig['name']) · {{ $sig['name'] }}@endif
                                </span>
                            @endforeach
                        </div>
                        @if ($h['ready'] && ! $h['sealed'])
                            <div class="d-flex justify-content-end mt-2">
                                <button type="button" class="clx-btn is-success" wire:click="seal('{{ $h['phase'] }}')"
                                    wire:confirm="Seal the {{ strtolower($h['title']) }}? It records the hand-over date and remarks shown on this tab (today if the date is blank).">
                                    <i class="fa fa-lock"></i> Seal {{ strtolower($h['title']) }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Handover dates + signatories --}}
        <div class="clx-people">
            {{-- On a lease/sale the inspection and the handover are the same visit, so one date
                 drives both the Inspection Date and Hand Over Date lines on the printed form. --}}
            <label class="clx-field">
                <span class="clx-lbl">{{ $showMoveOut ? 'Actual move-in date' : 'Inspection date' }}</span>
                <input type="date" class="clx-in" wire:model="actualMoveInDate">
            </label>
            <label class="clx-field">
                <span class="clx-lbl">{{ $showMoveOut ? 'Actual move-out date' : 'Handover date' }}</span>
                <input type="date" class="clx-in" wire:model="actualMoveOutDate">
            </label>
            <div class="clx-field" wire:ignore>
                <span class="clx-lbl">{{ ChecklistSignatoryRole::FacilityCoordinator->labelFor($agreement_type) }}</span>
                <select id="facilityCoord" class="select-employee_id-list" style="width:100%" placeholder="Select Employee">
                    @if ($facilityCoordinatorId)
                        <option value="{{ $facilityCoordinatorId }}" selected>{{ $facilityCoordinatorName }}</option>
                    @endif
                </select>
            </div>
            <div class="clx-field" wire:ignore>
                <span class="clx-lbl">{{ ChecklistSignatoryRole::LeasingCoordinator->labelFor($agreement_type) }}</span>
                <select id="leasingCoord" class="select-employee_id-list" style="width:100%" placeholder="Select Employee">
                    @if ($leasingCoordinatorId)
                        <option value="{{ $leasingCoordinatorId }}" selected>{{ $leasingCoordinatorName }}</option>
                    @endif
                </select>
            </div>
        </div>

        @if (count($rooms) === 0)
            <div class="clx-empty">
                <i class="fa fa-list-alt"></i>
                <b>No items yet</b>
                <span>Choose what's in this unit to start the hand-over checklist.</span>
                <button type="button" class="clx-btn is-primary mt-2" wire:click="openAddItems"><i class="fa fa-plus"></i> Add items</button>
            </div>
        @endif

        <div class="clx-split">
            <nav class="clx-nav">
                @foreach ($rooms as $room)
                    <button type="button" @class(['clx-nav-item', 'on' => $room['name'] === $this->room]) :class="{ 'on': room === @js($room['name']) }" x-on:click="room = @js($room['name'])"
                        wire:key="room-nav-{{ $loop->index }}-{{ $room['name'] }}">
                        @foreach ($phases as $key => $label)
                            <span class="clx-ring" data-only="{{ $key }}" style="--v: {{ $percent($room['checked'][$key], count($room['rows'])) }}">{{ $room['checked'][$key] }}</span>
                        @endforeach
                        <span class="clx-nav-text">
                            {{ $room['name'] }}
                            <small>
                                {{ count($room['rows']) }} {{ Str::plural('item', count($room['rows'])) }}
                                @if ($room['fixture'] !== null && count($fixtureAreas[$room['fixture']]['entries']))
                                    · {{ count($fixtureAreas[$room['fixture']]['entries']) }} to fix
                                @endif
                            </small>
                        </span>
                        <i class="fa fa-angle-right"></i>
                    </button>
                @endforeach
                <div class="clx-nav-add">@include('livewire.rent-out.partials.checklist-add-area')</div>
            </nav>

            <div class="clx-pane">
                @if (count($rooms))
                    <div class="clx-room-select">
                        <select class="clx-in" x-model="room">
                            @foreach ($rooms as $room)
                                <option value="{{ $room['name'] }}">{{ $room['name'] }} — {{ count($room['rows']) }} {{ Str::plural('item', count($room['rows'])) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @foreach ($rooms as $room)
                    @php
                        $previous = $rooms[$loop->index - 1]['name'] ?? null;
                        $next = $rooms[$loop->index + 1]['name'] ?? null;
                    @endphp
                    <section x-show="room === @js($room['name'])" @if ($room['name'] !== $this->room) style="display:none" @endif
                        wire:key="room-pane-{{ $loop->index }}-{{ $room['name'] }}">
                        <div class="clx-pane-head">
                            <h4>{{ $room['name'] }}</h4>
                            @foreach ($phases as $key => $label)
                                <span class="clx-pill is-muted" data-only="{{ $key }}">{{ $room['checked'][$key] }}/{{ count($room['rows']) }} {{ $doneWord[$key] }}</span>
                            @endforeach
                            <span class="clx-grow"></span>
                            @if (count($room['rows']))
                                @foreach ($phases as $key => $label)
                                    <button type="button" class="clx-btn" data-only="{{ $key }}" wire:click="markRoom(@js($room['name']), '{{ $key }}')">
                                        <i class="fa fa-check-circle-o"></i> {{ $allWord[$key] }}
                                    </button>
                                @endforeach
                            @endif
                        </div>

                        @foreach ($room['rows'] as $row)
                            @php
                                $i = $row['i'];
                                $line = $row['line'];
                                $moveOut = $line['move_out_status'] ?? null;
                            @endphp
                            <div @class(['clx-item', 'is-damaged' => $moveOut === 'not_ok']) wire:key="line-{{ $i }}-{{ $line['id'] ?? 'new' }}">
                                <div class="clx-item-photo">
                                    <x-rent-out.checklist.photo data-only="move_in" label="Photo" :url="$line['resolved_image_url'] ?? null"
                                        :badge="empty($line['image_path']) ? 'Master' : null" model="newImages.{{ $i }}"
                                        :remove="empty($line['image_path']) ? null : 'removeLineImage('.$i.')'" />
                                    @if ($showMoveOut)
                                        {{-- Saved straight to the line, same as the technician app — so only a saved line takes one. --}}
                                        <x-rent-out.checklist.photo data-only="move_out" :label="empty($line['id']) ? 'Save first' : 'Move-out'"
                                            :url="$line['move_out_image_url'] ?? null" :model="empty($line['id']) ? null : 'newMoveOutImages.'.$i"
                                            remove="removeMoveOutImage({{ $i }})" confirm="Remove this move-out photo?" />
                                    @endif
                                </div>
                                <div class="clx-item-body">
                                    <div class="clx-item-top">
                                        <span class="clx-item-name">{{ $line['name'] }}</span>
                                        <span class="clx-qty" title="Quantity">
                                            <button type="button" wire:click="stepQty({{ $i }}, -1)" aria-label="Less"><i class="fa fa-minus"></i></button>
                                            <input type="number" min="0" inputmode="numeric" wire:model.blur="lines.{{ $i }}.qty">
                                            <button type="button" wire:click="stepQty({{ $i }}, 1)" aria-label="More"><i class="fa fa-plus"></i></button>
                                        </span>
                                        <button type="button" class="clx-icon-btn" wire:click="removeLine({{ $i }})"
                                            wire:confirm="Remove {{ $line['name'] }} from this checklist?" title="Remove item">
                                            <i class="fa fa-trash-o"></i>
                                        </button>
                                    </div>
                                    <div class="clx-item-ctl">
                                        <button type="button" data-only="move_in" @class(['clx-tick', 'on' => ($line['move_in_status'] ?? null) === 'ok'])
                                            wire:click="toggleMoveIn({{ $i }})">
                                            <i class="fa fa-check"></i> Present
                                        </button>
                                        <input class="clx-in" data-only="move_in" placeholder="{{ $showMoveOut ? 'Move-in comment…' : 'Comment…' }}"
                                            wire:model.blur="lines.{{ $i }}.move_in_comment">
                                        @if ($showMoveOut)
                                            <span class="clx-tri" data-only="move_out">
                                                <button type="button" @class(['is-good', 'on' => $moveOut === 'ok']) wire:click="setMoveOut({{ $i }}, 'ok')">
                                                    <i class="fa fa-check"></i> Good
                                                </button>
                                                <button type="button" @class(['is-bad', 'on' => $moveOut === 'not_ok']) wire:click="setMoveOut({{ $i }}, 'not_ok')">
                                                    <i class="fa fa-times"></i> Damaged
                                                </button>
                                            </span>
                                            <input class="clx-in" data-only="move_out" placeholder="Move-out comment…"
                                                wire:model.blur="lines.{{ $i }}.move_out_comment">
                                            <label class="clx-damage" data-only="move_out">
                                                <span>QAR</span>
                                                <input type="number" min="0" step="0.01" inputmode="decimal" placeholder="0.00"
                                                    wire:model.blur="lines.{{ $i }}.damage_cost">
                                            </label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if ($room['fixture'] !== null)
                            @include('livewire.rent-out.partials.fixture-block', [
                                'a' => $room['fixture'],
                                'area' => $fixtureAreas[$room['fixture']],
                                'canRemoveArea' => count($room['rows']) === 0,
                            ])
                        @endif

                        <div class="clx-pager">
                            @if ($previous)
                                <button type="button" class="clx-btn" x-on:click="room = @js($previous)"><i class="fa fa-arrow-left"></i> {{ $previous }}</button>
                            @endif
                            <span class="clx-grow"></span>
                            @if ($next)
                                <button type="button" class="clx-btn is-primary" x-on:click="room = @js($next)">{{ $next }} <i class="fa fa-arrow-right"></i></button>
                            @endif
                        </div>
                    </section>
                @endforeach

                <div class="clx-pane-add">@include('livewire.rent-out.partials.checklist-add-area')</div>
            </div>
        </div>

        {{-- Remarks --}}
        @foreach ($phases as $key => $label)
            <label class="clx-field mt-3" data-only="{{ $key }}">
                <span class="clx-lbl">{{ $label }} remarks</span>
                <textarea class="clx-in" rows="2" wire:model="{{ $key === 'move_in' ? 'moveInRemarks' : 'moveOutRemarks' }}"
                    placeholder="{{ $label }} remarks…"></textarea>
            </label>
        @endforeach

        {{-- Save dock: stays in reach at the bottom of the screen while walking the unit --}}
        <div class="clx-dock">
            @foreach ($phases as $key => $label)
                <span class="clx-dock-sum" data-only="{{ $key }}"><b>{{ $checkedFor($key) }}/{{ count($lines) }}</b> {{ $doneWord[$key] }}</span>
            @endforeach
            @if ($showMoveOut)
                <span class="clx-dock-sum">Damage <b class="text-danger">QAR {{ number_format($damageTotal, 2) }}</b></span>
            @endif
            <span class="clx-dock-hint"><i class="fa fa-cloud-upload"></i> Photos save as soon as they upload</span>
            <span class="clx-grow"></span>
            <button type="button" class="clx-btn is-primary is-lg" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save"><i class="fa fa-floppy-o"></i> Save checklist</span>
                <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin"></i> Saving…</span>
            </button>
        </div>
    </div>{{-- /Items --}}

    @if (! $showMoveOut)
        <div x-show="sub === 'terms'" x-cloak>
            @livewire('rent-out.tabs.handover-terms-tab', ['rentOutId' => $rentOutId], key('handover-terms-tab-' . $rentOutId))
        </div>
    @endif
</div>

@script
    <script>
        // Sync the two coordinator employee pickers into Livewire on change.
        $('#facilityCoord').on('change', function() {
            $wire.set('facilityCoordinatorId', $(this).val() || null);
        });
        $('#leasingCoord').on('change', function() {
            $wire.set('leasingCoordinatorId', $(this).val() || null);
        });
    </script>
@endscript
