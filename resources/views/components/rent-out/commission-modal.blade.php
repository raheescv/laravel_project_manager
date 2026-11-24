<div class="modal" id="CommissionModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.commission-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleCommissionModal', event => {
            $('#CommissionModal').modal('toggle');
        });
    </script>
@endpush
