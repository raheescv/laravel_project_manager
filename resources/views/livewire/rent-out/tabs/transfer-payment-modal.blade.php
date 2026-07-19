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
            Moves this receipt in full to another property. It is removed from the current
            property and applied to the target.
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-building me-1 text-muted"></i> Transfer To <span class="text-danger">*</span>
                    <span class="text-muted fw-normal">&mdash; {{ $customerName ?? 'Customer' }}'s properties</span>
                </label>

                @if ($form['to_rent_out_id'])
                    {{-- Selected target chip --}}
                    <div class="d-flex align-items-center justify-content-between border rounded px-2 py-1 bg-light">
                        <span class="small"><i class="fa fa-check-circle text-success me-1"></i>{{ $selectedTargetLabel }}</span>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" wire:click="clearTarget">
                            <i class="fa fa-times"></i> Change
                        </button>
                    </div>
                @else
                    {{-- Searchable picker --}}
                    <div class="position-relative">
                        <input type="text" class="form-control form-control-sm"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by property name or agreement #...">
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
                                    $label = '#' . $target->id . ' · ' . $context;
                                @endphp
                                <button type="button"
                                    class="btn btn-sm btn-light w-100 text-start border-0 border-bottom rounded-0 d-flex justify-content-between align-items-center py-2"
                                    wire:click="selectTarget({{ $target->id }}, @js($label))">
                                    <span class="small d-flex align-items-center">
                                        <i class="fa fa-building-o text-muted me-2"></i>
                                        <span>{{ $context }}</span>
                                    </span>
                                    <span class="badge bg-secondary bg-opacity-50 ms-2">#{{ $target->id }}</span>
                                </button>
                            @empty
                                <div class="text-muted small text-center py-2">
                                    <i class="fa fa-search me-1"></i>
                                    {{ $search !== '' ? 'No matching properties' : 'No other properties for this customer' }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif
                @error('form.to_rent_out_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="w-100 text-end">
                    <div class="form-label fw-semibold small mb-1 text-muted">Amount to move</div>
                    <div class="fs-5 fw-bold text-success">{{ number_format($amount, 2) }}</div>
                </div>
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
            <span wire:loading.remove wire:target="transfer"><i class="fa fa-exchange me-1"></i> Move Payment</span>
            <span wire:loading wire:target="transfer"><i class="fa fa-spinner fa-spin me-1"></i> Moving...</span>
        </button>
    </div>
</div>
