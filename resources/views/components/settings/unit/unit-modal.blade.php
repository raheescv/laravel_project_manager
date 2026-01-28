<div class="modal fade" id="UnitModal" tabindex="-1" aria-labelledby="unitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
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
