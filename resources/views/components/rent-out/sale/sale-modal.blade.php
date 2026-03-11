<div class="modal" id="RentOutSaleModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @livewire('rent-out.sale.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleRentOutSaleModal', event => {
            $('#RentOutSaleModal').modal('toggle');
        });
    </script>
@endpush
