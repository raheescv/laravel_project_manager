<div class="modal" id="PackageCategoryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('package-category.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('TogglePackageCategoryModal', event => {
            $('#PackageCategoryModal').modal('toggle');
        });
    </script>
@endpush

