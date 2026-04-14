<div class="modal" id="ChequeTermSelectorModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.cheque-term-selector-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleChequeTermSelectorModal', event => {
            $('#ChequeTermSelectorModal').modal('toggle');
        });
    </script>
@endpush
