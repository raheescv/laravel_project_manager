<div class="modal" id="SaleFeedbackModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('sale.feedback')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleSaleFeedbackModal', event => {
            $('#SaleFeedbackModal').modal('toggle');
        });
    </script>
@endpush
