<div class="modal" id="TenantModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('tenant.page', ['table_id' => $id ?? null])
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleTenantModal', event => {
            $('#TenantModal').modal('toggle');
        });
    </script>
@endpush

