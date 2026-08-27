<div class="modal fade" id="BranchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
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
