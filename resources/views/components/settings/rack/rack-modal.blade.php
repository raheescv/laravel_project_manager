<div class="modal fade" id="RackModal" tabindex="-1" aria-labelledby="rackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            @livewire('settings.rack.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleRackModal', event => {
            $('#RackModal').modal('toggle');
        });
    </script>
@endpush
