<div class="modal fade" id="DesignationModal" tabindex="-1" aria-labelledby="designationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            @livewire('settings.designation.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleDesignationModal', event => {
            $('#DesignationModal').modal('toggle');
        });
    </script>
@endpush
