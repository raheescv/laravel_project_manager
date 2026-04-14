<div class="modal" id="UtilityModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.utility.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleUtilityModal', event => {
            $('#UtilityModal').modal('toggle');
        });
    </script>
@endpush
