<div class="modal" id="ChequeStatusModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.cheque-status-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleChequeStatusModal', event => {
            $('#ChequeStatusModal').modal('toggle');
        });
    </script>
@endpush
