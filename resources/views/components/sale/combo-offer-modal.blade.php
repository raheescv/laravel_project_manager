<div class="modal" id="ManageSaleComboOfferModal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            @livewire('sale.combo-offer', ['sale_id' => $id ?? ''])
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleManageSaleComboOfferModal', event => {
            $('#ManageSaleComboOfferModal').modal('toggle');
        });
    </script>
@endpush
