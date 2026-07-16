<div class="modal" id="EmployeeCommissionBulkModal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            @livewire('user.employee-commission.bulk-page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleEmployeeCommissionBulkModal', event => {
            $('#EmployeeCommissionBulkModal').modal('toggle');
        });
    </script>
@endpush
