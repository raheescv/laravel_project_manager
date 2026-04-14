<div class="modal" id="ServiceChargeModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.service-charge-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleServiceChargeModal', event => {
            $('#ServiceChargeModal').modal('toggle');
        });
    </script>
@endpush
