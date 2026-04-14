<div class="modal" id="MultipleChequeModal" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.multiple-cheque-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleMultipleChequeModal', event => {
            $('#MultipleChequeModal').modal('toggle');
        });
    </script>
@endpush
