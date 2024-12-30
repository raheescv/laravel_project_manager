<div class="modal" id="DepartmentModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.department.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleDepartmentModal', event => {
            $('#DepartmentModal').modal('toggle');
        });
    </script>
@endpush
