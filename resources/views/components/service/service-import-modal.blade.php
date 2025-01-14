<div class="modal" id="ServiceImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('service.import')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleServiceImportModal', event => {
            $('#ServiceImportModal').modal('toggle');
        });
    </script>
@endpush
