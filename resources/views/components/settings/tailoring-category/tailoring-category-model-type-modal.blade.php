<div class="modal fade" id="TailoringCategoryModelTypeModal" tabindex="-1" aria-labelledby="tailoringCategoryModelTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            @livewire('settings.tailoring-category-model-type.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleTailoringCategoryModelTypeModal', event => {
            $('#TailoringCategoryModelTypeModal').modal('toggle');
        });
    </script>
@endpush
