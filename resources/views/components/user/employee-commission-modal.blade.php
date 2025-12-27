<div class="modal" id="EmployeeCommissionModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @livewire('user.employee-commission.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleEmployeeCommissionModal', event => {
            $('#EmployeeCommissionModal').modal('toggle');
        });
    </script>
@endpush

