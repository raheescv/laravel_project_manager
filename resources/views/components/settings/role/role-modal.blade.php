<div class="modal" id="RoleModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.role.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleRoleModal', event => {
            $('#RoleModal').modal('toggle');
        });
    </script>
@endpush
