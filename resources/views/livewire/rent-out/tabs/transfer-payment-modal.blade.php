<div>
    {{-- Header --}}
    <div class="modal-header bg-primary text-white border-0 py-2 px-3">
        <h6 class="modal-title fw-bold mb-0 text-white">
            <i class="fa fa-exchange me-2"></i> Transfer Payment
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3">
        <div class="alert alert-info py-2 px-3 small mb-3">
            <i class="fa fa-info-circle me-1"></i>
            Transfers all or part of this receipt to another property (any customer). The receipt stays here;
            a <strong>Transfer Out</strong> (debit) is posted on this property and a <strong>Transfer In</strong>
            (credit) on the target.
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-building me-1 text-muted"></i> Transfer To <span class="text-danger">*</span>
                </label>

                @if ($form['to_rent_out_id'])
                    {{-- Selected target chip --}}
                    <div class="d-flex align-items-center justify-content-between border rounded px-2 py-1 bg-light">
                        <span class="small"><i class="fa fa-check-circle text-success me-1"></i>{{ $selectedTargetLabel }}</span>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-2">
                            @if ($selectedTargetUrl)
                                <a href="{{ $selectedTargetUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-link p-0 text-decoration-none" title="Open this agreement in a new tab">
                                    <i class="fa fa-external-link"></i> View
                                </a>
                            @endif
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" wire:click="clearTarget">
                                <i class="fa fa-times"></i> Change
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Searchable picker --}}
                    <div class="position-relative">
                        <input type="text" class="form-control form-control-sm"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by unit, customer name or agreement #...">
                        <div class="border rounded mt-1" style="max-height: 200px; overflow-y: auto;">
                            @forelse ($targets as $target)
                                @php
                                    $parts = array_filter([
                                        $target->group?->name,
                                        $target->building?->name,
                                        $target->type?->name,
                                        $target->property?->number ? 'Unit ' . $target->property->number : null,
                                    ]);
                                    $context = implode(' · ', $parts) ?: 'Property';
                                    $customer = $target->customer?->name;
                                    $label = '#' . $target->id . ' · ' . $context . ($customer ? ' · ' . $customer : '');
                                @endphp
                                <button type="button"
                                    class="btn btn-sm btn-light w-100 text-start border-0 border-bottom rounded-0 d-flex justify-content-between align-items-center py-2"
                                    wire:click="selectTarget({{ $target->id }}, @js($label))">
                                    <span class="small d-flex align-items-center">
                                        <i class="fa fa-building-o text-muted me-2"></i>
                                        <span>
                                            {{ $context }}
                                            @if ($customer)
                                                <span class="d-block text-muted"><i class="fa fa-user-o me-1"></i>{{ $customer }}</span>
                                            @endif
                                        </span>
                                    </span>
                                    <span class="badge bg-secondary bg-opacity-50 ms-2">#{{ $target->id }}</span>
                                </button>
                            @empty
                                <div class="text-muted small text-center py-2">
                                    <i class="fa fa-search me-1"></i>
                                    {{ $search !== '' ? 'No matching properties' : 'No other properties found' }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif
                @error('form.to_rent_out_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-money me-1 text-muted"></i> Amount <span class="text-danger">*</span>
                </label>
                <input type="number" step="0.01" min="0.01" max="{{ $available }}"
                    class="form-control form-control-sm text-end fw-bold @error('form.amount') is-invalid @enderror"
                    wire:model="form.amount">
                <div class="d-flex justify-content-between small text-muted mt-1">
                    <span>Available {{ number_format($available, 2) }}</span>
                    <a href="#" class="text-decoration-none" wire:click.prevent="$set('form.amount', {{ $available }})">Full amount</a>
                </div>
                @error('form.amount') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-list me-1 text-muted"></i> Apply To Payment Term <span class="text-muted">(optional)</span>
                </label>
                <select class="form-select form-select-sm" wire:model="form.to_term_id" @disabled(!$form['to_rent_out_id'])>
                    <option value="">Unapplied (reduce overall balance)</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term->id }}">
                            {{ $term->label ?: 'Term' }}
                            @if ($term->due_date) &middot; due {{ $term->due_date->format('d-m-Y') }} @endif
                            &middot; bal {{ number_format($term->balance, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-comment-o me-1 text-muted"></i> Remark
                </label>
                <textarea class="form-control form-control-sm" wire:model="form.remark" rows="2"
                    placeholder="Reason for the transfer..."></textarea>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-sm btn-primary" wire:click="transfer" wire:loading.attr="disabled"
            wire:target="transfer">
            <span wire:loading.remove wire:target="transfer"><i class="fa fa-exchange me-1"></i> Transfer Payment</span>
            <span wire:loading wire:target="transfer"><i class="fa fa-spinner fa-spin me-1"></i> Transferring...</span>
        </button>
    </div>
</div>
