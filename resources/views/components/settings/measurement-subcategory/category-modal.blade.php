<div class="modal" id="MeasurementCategoryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.measurement-sub-category.page')
        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.addEventListener('ToggleMeasurementCategoryModal', event => {
            $('#MeasurementCategoryModal').modal('toggle');
        });
    </script>
@endpush
