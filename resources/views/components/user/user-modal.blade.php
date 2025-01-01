<div class="modal" id="UserModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('user.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleUserModal', event => {
            $('#UserModal').modal('toggle');
        });
    </script>
@endpush
