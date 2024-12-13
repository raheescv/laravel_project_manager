<div class="modal" id="ProductTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('product-type.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleProductTypeModal', event => {
            $('#ProductTypeModal').modal('toggle');
        });
    </script>
@endpush
