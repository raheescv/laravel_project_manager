<div class="modal" id="MandatoryDocumentModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.mandatory-document-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleMandatoryDocumentModal', event => {
            $('#MandatoryDocumentModal').modal('toggle');
        });
    </script>
@endpush
