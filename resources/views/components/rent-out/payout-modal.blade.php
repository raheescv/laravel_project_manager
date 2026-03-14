<div class="modal" id="PayoutModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.payout-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('TogglePayoutModal', event => {
            $('#PayoutModal').modal('toggle');
        });
    </script>
@endpush
