<div class="modal" id="DocumentTypeModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.document-type.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleDocumentTypeModal', event => {
            $('#DocumentTypeModal').modal('toggle');
        });
    </script>
@endpush
