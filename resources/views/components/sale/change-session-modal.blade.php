<div class="modal" id="ChangeSessionModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('sale.change-session', ['table_id' => $id])
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleChangeSessionModal', event => {
            $('#ChangeSessionModal').modal('toggle');
        });
    </script>
@endpush


