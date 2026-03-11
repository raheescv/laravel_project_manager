<div class="modal" id="PropertyTypeModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.property-type.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('TogglePropertyTypeModal', event => {
            $('#PropertyTypeModal').modal('toggle');
        });
    </script>
@endpush
