<div class="modal" id="ProductPriceModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('product.prices', ['product_id' => $product_id])
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleProductPriceModal', event => {
            $('#ProductPriceModal').modal('toggle');
        });
    </script>
@endpush
