<div class="modal" id="SingleChequeModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.single-cheque-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleSingleChequeModal', event => {
            $('#SingleChequeModal').modal('toggle');
        });
    </script>
@endpush
