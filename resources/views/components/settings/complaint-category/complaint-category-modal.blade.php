<div class="modal fade" id="ComplaintCategoryModal" tabindex="-1" aria-labelledby="complaintCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            @livewire('settings.complaint-category.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleComplaintCategoryModal', event => {
            $('#ComplaintCategoryModal').modal('toggle');
        });
    </script>
@endpush
