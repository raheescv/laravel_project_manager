<div class="modal" id="SecurityModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.security-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleSecurityModal', event => {
            $('#SecurityModal').modal('toggle');
        });
    </script>
@endpush
