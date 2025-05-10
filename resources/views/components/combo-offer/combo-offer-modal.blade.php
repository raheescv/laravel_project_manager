<div class="modal" id="ComboOfferModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('combo-offer.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleComboOfferModal', event => {
            $('#ComboOfferModal').modal('toggle');
        });
    </script>
@endpush
