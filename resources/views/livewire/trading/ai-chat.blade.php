<div>
    {{-- Header --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center">
                <span class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center me-3"
                      style="width:42px;height:42px;">
                    <i class="fa fa-comments-o fs-5"></i>
                </span>
                <div>
                    <h5 class="mb-0 fw-semibold">AI Trade Analyst</h5>
                    <small class="text-muted">Ask anything about today's trades, decisions or rejections</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Chat input --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">
                <i class="fa fa-paper-plane text-primary me-2"></i> Ask the analyst
            </h6>
        </div>
        <div class="card-body">
            <textarea wire:model="question" rows="3" class="form-control"
                placeholder="e.g. Why did we sell RELIANCE at 09:12? What's the best decision today?"></textarea>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="small text-muted">
                    <i class="fa fa-info-circle me-1"></i>
                    Context includes the last 20 strategy runs and your open positions.
                </div>
                <button wire:click="ask" wire:loading.attr="disabled" wire:target="ask" class="btn btn-sm btn-primary">
                    <i class="fa fa-paper-plane me-1" wire:loading.class="fa-spin" wire:target="ask"></i>
                    <span wire:loading.remove wire:target="ask">Ask</span>
                    <span wire:loading wire:target="ask">Thinking…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Answer --}}
    @if ($answer)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0">
                    <i class="fa fa-lightbulb-o text-warning me-2"></i> Answer
                </h6>
            </div>
            <div class="card-body">
                <div class="bg-light border-start border-4 border-primary rounded-end p-3" style="white-space: pre-wrap;">{{ $answer }}</div>
            </div>
        </div>
    @endif

    {{-- History --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="fa fa-history text-primary me-2"></i> Recent chats
            </h6>
            <span class="badge bg-light text-dark">{{ $history->count() }}</span>
        </div>
        <div class="card-body">
            @forelse ($history as $h)
                <div class="border-bottom pb-3 mb-3 last-mb-0">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>{{ $h->created_at->diffForHumans() }}</span>
                        <span><i class="fa fa-microchip me-1"></i>{{ $h->model }} · {{ number_format($h->tokens_used) }} tokens</span>
                    </div>
                    <div class="small font-monospace text-truncate text-muted">{{ str($h->prompt)->limit(140) }}</div>
                    <div class="mt-1">{{ str($h->response)->limit(280) }}</div>
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="fa fa-comments fs-2 d-block mb-2 opacity-50"></i>
                    No chat history yet
                </div>
            @endforelse
        </div>
    </div>
</div>
