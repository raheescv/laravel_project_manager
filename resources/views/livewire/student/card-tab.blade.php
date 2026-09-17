{{-- Student view → Card. Styled by the parent .svx system (components/student/view-premium). --}}
<div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="py-2 mb-3">
                <x-student.id-card :account="$account" :detail="$detail" :balance="$balance" large />
            </div>

            @if ($detail->card_uid)
                <p class="small text-body-secondary text-center mb-3">
                    Card number (UID) <span class="mono text-body-emphasis">{{ $detail->card_uid }}</span>
                    · <span @class(['text-danger-emphasis' => $detail->isCardBlocked(), 'text-success-emphasis' => !$detail->isCardBlocked()])>{{ $detail->isCardBlocked() ? 'Blocked' : 'Active' }}</span>
                </p>
            @endif

            @if ($detail->card_uid && $detail->isCardBlocked())
                <div class="act danger">
                    <h6><i class="fa fa-lock me-1"></i>Blocked {{ $detail->card_blocked_at?->diffForHumans() }}{{ $blockedBy ? ' by ' . $blockedBy : '' }}</h6>
                    <p>
                        {{ $detail->card_block_reason ? 'Reason: ' . $detail->card_block_reason . '. ' : '' }}QLOUD POS refuses this card until it is unblocked or replaced.
                    </p>
                    @can('student card.unblock')
                        <button type="button" class="btn btn-sm btn-success" wire:click="unblock" wire:loading.attr="disabled"
                            wire:confirm="Switch this card back on? Only do this if the card has been found by the family.">
                            <i class="fa fa-unlock me-1"></i>Unblock card
                        </button>
                    @endcan
                </div>
            @elseif ($detail->card_uid)
                @can('student card.block')
                    <div class="act">
                        <h6><i class="fa fa-lock me-1 text-danger"></i>Block card</h6>
                        <p>Use this when a card is lost or stolen. The canteen refuses it straight away.</p>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" wire:model="block_reason" maxlength="255" placeholder="Reason (e.g. reported lost)" aria-label="Reason for blocking">
                            <button type="button" class="btn btn-danger" wire:click="block" wire:loading.attr="disabled" wire:confirm="Block this card now? It will be refused at the canteen.">
                                <i class="fa fa-lock me-1"></i>Block card
                            </button>
                        </div>
                    </div>
                @endcan
            @endif

            @can('student card.assign')
                <form wire:submit="assign" class="act">
                    <h6><i class="fa fa-link me-1 text-primary"></i>{{ $detail->card_uid ? 'Replace card' : 'Link a card' }}</h6>
                    <p>The balance belongs to the student, not the card, so a replacement card spends the same money straight away.</p>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-body"><i class="fa fa-wifi" style="transform: rotate(90deg)"></i></span>
                        <input type="text" class="form-control font-monospace" wire:model="card_uid" maxlength="40" autocomplete="off"
                            placeholder="Tap the card on a USB reader or type its UID" aria-label="Card number">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"><i class="fa fa-link me-1"></i>{{ $detail->card_uid ? 'Replace' : 'Link' }}</button>
                    </div>
                    <div class="form-text">For an exact match with the till, link cards from QLOUD POS → Link card.</div>
                </form>
            @endcan
        </div>

        <div class="col-lg-6">
            <div class="ph">
                <span class="pi"><i class="fa fa-history"></i></span>
                <div>
                    <h6>Card history</h6>
                    <div class="hint">Every link, block and unblock</div>
                </div>
            </div>
            @if ($history->isEmpty())
                <div class="empty"><i class="fa fa-history"></i>No card changes recorded yet.</div>
            @else
                <div class="tl">
                    @foreach ($history as $entry)
                        <div class="it {{ $entry['kind'] }}">
                            <div class="t">{{ $entry['event'] }}</div>
                            <div class="m">{{ $entry['by'] ? $entry['by'] . ' · ' : '' }}{{ systemDateTime($entry['at']) }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
