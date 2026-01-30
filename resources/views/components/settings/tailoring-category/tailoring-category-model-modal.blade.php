<div class="modal fade" id="TailoringCategoryModelModal" tabindex="-1" aria-labelledby="tailoringCategoryModelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            @livewire('settings.tailoring-category-model.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleTailoringCategoryModelModal', event => {
            $('#TailoringCategoryModelModal').modal('toggle');
        });
    </script>
@endpush
