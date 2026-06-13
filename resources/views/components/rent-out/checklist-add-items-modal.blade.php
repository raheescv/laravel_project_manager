<div class="modal fade" id="ChecklistAddItemsModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        @livewire('rent-out.checklist.add-items-modal')
    </div>
</div>

@push('scripts')
    <script>
        window.addEventListener('ToggleChecklistAddItemsModal', function () {
            $('#ChecklistAddItemsModal').modal('toggle');
        });
    </script>
@endpush
