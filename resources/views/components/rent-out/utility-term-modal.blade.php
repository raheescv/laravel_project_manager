<div class="modal" id="UtilityTermModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.utility-term-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleUtilityTermModal', event => {
            $('#UtilityTermModal').modal('toggle');
        });
    </script>
@endpush
