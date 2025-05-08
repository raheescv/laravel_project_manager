<div class="modal" id="ManagePackageModal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            @livewire('sale.package', ['sale_id' => $id ?? ''])
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleManagePackageModal', event => {
            $('#ManagePackageModal').modal('toggle');
        });
    </script>
@endpush
