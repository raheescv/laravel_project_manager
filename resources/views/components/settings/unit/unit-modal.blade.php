<div class="modal" id="UnitModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.unit.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleUnitModal', event => {
            $('#UnitModal').modal('toggle');
        });
    </script>
@endpush
