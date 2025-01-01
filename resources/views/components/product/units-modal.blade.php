<div class="modal" id="ProductUnitModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('product.units', ['product_id' => $product_id])
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleProductUnitModal', event => {
            $('#ProductUnitModal').modal('toggle');
        });
    </script>
@endpush
