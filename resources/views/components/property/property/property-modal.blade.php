<div class="modal" id="PropertyModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @livewire('property.property.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('TogglePropertyModal', event => {
            $('#PropertyModal').modal('toggle');
        });
    </script>
@endpush
