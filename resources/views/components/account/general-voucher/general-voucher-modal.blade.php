<div class="modal" id="GeneralVoucherModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @livewire('account.general-voucher.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleGeneralVoucherModal', event => {
            $('#GeneralVoucherModal').modal('toggle');
        });
    </script>
@endpush

