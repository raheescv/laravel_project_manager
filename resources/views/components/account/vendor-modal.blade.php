<div class="modal" id="VendorModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('account.vendor.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleVendorModal', event => {
            $('#VendorModal').modal('toggle');
        });
    </script>
@endpush
