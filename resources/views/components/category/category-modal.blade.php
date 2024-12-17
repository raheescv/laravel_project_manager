<div class="modal" id="CategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('category.page')
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
