<div class="modal" id="DocumentModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.document-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleDocumentModal', event => {
            $('#DocumentModal').modal('toggle');
        });
                window.addEventListener('ClearDocumentModal', event => {
                    var ts = document.querySelector('#docModalDocumentTypeSelect')?.tomselect;
                    if (ts) {
                        ts.clear();
                    }
                });
    </script>
@endpush
