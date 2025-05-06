<div class="modal" id="AppointmentBookingModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @livewire('appointment.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleAppointmentBookingModal', event => {
            $('#AppointmentBookingModal').modal('toggle');
        });
        window.addEventListener('CloseAppointmentBookingModal', event => {
            $('#AppointmentBookingModal').modal('hide');
        });
    </script>
@endpush
