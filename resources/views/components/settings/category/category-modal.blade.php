<div class="modal" id="CategoryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.category.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleCategoryModal', event => {
            $('#CategoryModal').modal('toggle');
        });
    </script>
@endpush
