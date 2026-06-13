<div class="modal" id="ChecklistItemModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.checklist-item.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleChecklistItemModal', event => {
            $('#ChecklistItemModal').modal('toggle');
        });
    </script>
@endpush
