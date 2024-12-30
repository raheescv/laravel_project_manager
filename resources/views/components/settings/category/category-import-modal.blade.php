<div class="modal" id="CategoryImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.category.import')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleCategoryImportModal', event => {
            $('#CategoryImportModal').modal('toggle');
        });
    </script>
@endpush
