<div>
    @if($rentOut)
        @include('livewire.rent-out.partials.rent-out-view', [
            'indexRoute'     => 'property::sale::index',
            'indexLabel'     => 'Sale Agreements',
            'editPermission' => 'rent out lease.edit',
            'editRoute'      => 'property::sale::create',
            'bookingRoute'   => 'property::sale::booking.create',
        ])
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-exclamation-triangle text-warning fs-1 mb-3 d-block"></i>
                <p class="text-muted mb-0">Sale agreement not found.</p>
            </div>
        </div>
    @endif
</div>

{{-- Scripts --}}
@include('livewire.rent-out.partials.payment-term-scripts')
