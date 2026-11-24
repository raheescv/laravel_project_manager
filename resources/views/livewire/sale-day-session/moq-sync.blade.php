<div style="display: contents" x-data="{ open: false }" x-on:moq-synced.window="open = false" x-on:keydown.escape.window="open = false">
    <button type="button" class="hbtn" x-on:click="$wire.prepare().then(() => { open = true; $nextTick(() => $refs.amount?.focus()) })"
        title="{{ $session->synced_at ? 'Synced '.currency($session->sync_amount, 0).' on '.systemDateTime($session->synced_at) : 'Submit this day\'s revenue to MOQ' }}">
        <i class="fa fa-refresh"></i>
        {{ $session->synced_at ? 'Re-sync MOQ' : 'Sync to MOQ' }}
        @if ($session->synced_at)
            <i class="fa fa-check-circle text-success"></i>
        @endif
    </button>

    @teleport('body')
        <div>
            <div class="modal fade" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="moq-sync-title" x-bind:class="open && 'show d-block'" x-effect="document.body.classList.toggle('overflow-hidden', open)" x-on:click.self="open = false">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
                    <form class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" wire:submit="sync">
                        <div class="d-flex align-items-start gap-3 p-4 pb-3">
                            <div class="d-flex align-items-center justify-content-center flex-shrink-0 rounded-3 bg-primary-subtle text-primary-emphasis" style="width: 46px; height: 46px;">
                                <i class="fa fa-refresh fa-lg"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <h5 class="mb-1 fw-semibold" id="moq-sync-title">Sync revenue to MOQ</h5>
                                <div class="small text-body-secondary">
                                    Session #{{ $session->id }} · {{ $session->branch->name }}
                                </div>
                            </div>
                            <button type="button" class="btn-close" aria-label="Close" x-on:click="open = false"></button>
                        </div>

                        <div class="px-4">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="bg-body-tertiary rounded-3 px-3 py-2 h-100">
                                        <div class="small text-body-secondary">Business date</div>
                                        <div class="fw-semibold">{{ systemDate($session->opened_at) }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-body-tertiary rounded-3 px-3 py-2 h-100">
                                        <div class="small text-body-secondary">Status</div>
                                        @if ($session->synced_at)
                                            <div class="fw-semibold text-success-emphasis"><i class="fa fa-check-circle me-1"></i>Synced</div>
                                        @else
                                            <div class="fw-semibold text-body-secondary"><i class="fa fa-circle-o me-1"></i>Not synced</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if ($session->synced_at)
                                <div class="alert alert-warning d-flex gap-2 small py-2 px-3 mb-3">
                                    <i class="fa fa-exclamation-triangle mt-1"></i>
                                    <div>
                                        <b>{{ currency($session->sync_amount, 0) }}</b> was sent on {{ systemDateTime($session->synced_at) }} by {{ $session->syncer?->name ?? 'Unknown' }}.
                                        Submitting again posts another entry to MOQ.
                                    </div>
                                </div>
                            @endif

                            <label for="moq-sync-amount" class="form-label small fw-semibold text-body-secondary text-uppercase mb-1">Revenue amount</label>
                            <input id="moq-sync-amount" x-ref="amount" type="number" inputmode="numeric" min="0" step="1"
                                class="form-control form-control-lg fs-2 fw-bold text-center @error('amount') is-invalid @enderror"
                                wire:model="amount" placeholder="0" autocomplete="off">
                            @error('amount')
                                <div class="invalid-feedback d-block text-center">{{ $message }}</div>
                            @else
                                <div class="form-text text-center">Whole numbers only — MOQ ignores decimals.</div>
                            @enderror

                            @if ($suggestions)
                                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                                    @foreach ($suggestions as $label => $value)
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" wire:click="$set('amount', {{ (int) $value }})">
                                            <span class="text-body-secondary">{{ $label }}</span>
                                            <b class="ms-1">{{ currency($value, 0) }}</b>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2 px-4 py-3 mt-4 bg-body-tertiary border-top">
                            <button type="button" class="btn btn-light flex-fill" x-on:click="open = false">Cancel</button>
                            <button type="submit" class="btn btn-primary flex-fill" wire:loading.attr="disabled" wire:target="sync">
                                <span wire:loading.remove wire:target="sync"><i class="fa fa-paper-plane me-1"></i> Submit to MOQ</span>
                                <span wire:loading wire:target="sync"><span class="spinner-border spinner-border-sm me-1"></span> Sending…</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-backdrop fade show" x-show="open" style="display: none;"></div>
        </div>
    @endteleport
</div>
