<div class="modal" id="PropertyBuildingModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('property.building.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('TogglePropertyBuildingModal', event => {
            $('#PropertyBuildingModal').modal('toggle');
        });
    </script>
@endpush
