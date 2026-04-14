<div class="modal" id="UtilityPaySelectedModal" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            @livewire('rent-out.tabs.utility-pay-selected-modal', ['rentOutId' => $rentOutId])
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleUtilityPaySelectedModal', event => {
            $('#UtilityPaySelectedModal').modal('toggle');
        });
    </script>
@endpush
