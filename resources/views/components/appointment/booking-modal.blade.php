<div class="modal fade" id="AppointmentBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
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
