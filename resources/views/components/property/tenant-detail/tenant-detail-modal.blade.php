<div class="modal" id="TenantDetailModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @livewire('property.tenant-detail.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleTenantDetailModal', event => {
            $('#TenantDetailModal').modal('toggle');
        });
    </script>
@endpush
