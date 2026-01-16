<div class="modal" id="GeneralVoucherModal" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" id="GeneralVoucherModalContent">
            <!-- Vue component will be mounted here -->
        </div>
    </div>
</div>
@push('scripts')
    @vite('resources/js/general-voucher-modal.js')
    <script>
        // Store branch_id in window for Vue component access
        @if(session('branch_id'))
            window.branch_id = {{ session('branch_id') }};
        @endif
    </script>
@endpush

