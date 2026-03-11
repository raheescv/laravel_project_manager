<div class="modal" id="PropertyGroupModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('property.group.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('TogglePropertyGroupModal', event => {
            $('#PropertyGroupModal').modal('toggle');
        });
    </script>
@endpush
