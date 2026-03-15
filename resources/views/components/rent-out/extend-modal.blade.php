<div class="modal" id="ExtendModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.extend-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleExtendModal', event => {
            $('#ExtendModal').modal('toggle');
        });
    </script>
@endpush
