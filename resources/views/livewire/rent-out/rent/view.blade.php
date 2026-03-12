<div>
    @if($rentOut)
        @include('livewire.rent-out.partials.rent-out-view', [
            'indexRoute'     => 'property::rent::index',
            'indexLabel'     => 'Rental Agreements',
            'editPermission' => 'rent out.edit',
            'editRoute'      => 'property::rent::create',
            'bookingRoute'   => 'property::rent::booking.create',
        ])
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-exclamation-triangle text-warning fs-1 mb-3 d-block"></i>
                <p class="text-muted mb-0">Rental agreement not found.</p>
            </div>
        </div>
    @endif
</div>
