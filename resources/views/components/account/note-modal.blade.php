<div class="modal" id="AccountNoteModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('account.note.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleAccountNoteModal', event => {
            $('#AccountNoteModal').modal('toggle');
        });
    </script>
@endpush
