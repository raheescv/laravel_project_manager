<div class="modal" id="AttendanceModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content ">
            @livewire('user.attendance.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleAttendanceModal', event => {
            $('#AttendanceModal').modal('toggle');
        });
    </script>
@endpush
