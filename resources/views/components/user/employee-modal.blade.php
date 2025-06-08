<div class="modal" id="EmployeeModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
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
