<div class="modal" id="RoiModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.roi-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleRoiModal', event => {
            $('#RoiModal').modal('toggle');
        });
    </script>
@endpush
