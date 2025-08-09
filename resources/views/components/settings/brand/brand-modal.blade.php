<div class="modal" id="BrandModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.brand.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleBrandModal', event => {
            $('#BrandModal').modal('toggle');
        });
    </script>
@endpush

