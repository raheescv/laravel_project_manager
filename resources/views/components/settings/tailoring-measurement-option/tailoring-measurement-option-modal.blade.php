<div class="modal fade" id="TailoringMeasurementOptionModal" tabindex="-1" aria-labelledby="tailoringMeasurementOptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            @livewire('settings.tailoring-measurement-option.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleTailoringMeasurementOptionModal', event => {
            $('#TailoringMeasurementOptionModal').modal('toggle');
        });
    </script>
@endpush
