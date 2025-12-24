<div class="modal" id="MeasurementCategoryImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.measurement-category.import')
        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.addEventListener('ToggleMeasurementCategoryImportModal', event => {
            $('#MeasurementCategoryImportModal').modal('toggle');
        });
    </script>
@endpush
