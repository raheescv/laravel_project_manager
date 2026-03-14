<div class="modal" id="ServiceModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.service-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleServiceModal', event => {
            $('#ServiceModal').modal('toggle');
        });
    </script>
@endpush
