<div class="modal fade" id="TailoringCategoryModal" tabindex="-1" aria-labelledby="tailoringCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            @livewire('settings.tailoring-category.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleTailoringCategoryModal', event => {
            $('#TailoringCategoryModal').modal('toggle');
        });
    </script>
@endpush
