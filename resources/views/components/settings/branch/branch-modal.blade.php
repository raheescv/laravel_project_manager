<div class="modal" id="BranchModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.branch.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleBranchModal', event => {
            $('#BranchModal').modal('toggle');
        });
    </script>
@endpush
