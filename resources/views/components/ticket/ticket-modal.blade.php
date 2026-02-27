<div class="modal fade ticket-modal" id="TicketModal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog" style="max-width: 1400px; width: 95vw;">
        <div class="modal-content">
            @livewire('ticket.modal')
        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.addEventListener('TicketModalControl', event => {
            const modalElement = document.getElementById('TicketModal');
            if (!modalElement) return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            const detail = event?.detail || {};
            const payload = Array.isArray(detail) ? (detail[0] || {}) : detail;
            const action = payload.action || 'show';

            if (action === 'hide') {
                modal.hide();
            } else {
                modal.show();
            }
        });
    </script>
@endpush
