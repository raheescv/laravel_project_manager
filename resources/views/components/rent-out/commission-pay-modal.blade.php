<div class="modal" id="CommissionPayModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.commission-pay-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleCommissionPayModal', event => {
            $('#CommissionPayModal').modal('toggle');
        });
    </script>
@endpush
