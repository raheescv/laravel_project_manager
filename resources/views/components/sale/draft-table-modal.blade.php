<div class="modal" id="DraftTableModal" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="width:150% !important%">
        <div class="modal-content ">
            <div class="modal-header p-4">
                <h5 class="modal-title">Draft List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @livewire('sale.draft-table')
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleDraftTableModal', event => {
            $('#DraftTableModal').modal('toggle');
        });
    </script>
@endpush
