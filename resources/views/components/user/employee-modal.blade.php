<div class="modal" id="EmployeeModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content modal-lg">
            @livewire('user.employee.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleEmployeeModal', event => {
            $('#EmployeeModal').modal('toggle');
        });
    </script>
@endpush
