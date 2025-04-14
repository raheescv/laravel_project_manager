<div id="branch_selection_modal" class="modal fade" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            @livewire('general.branch-selection')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        $('#branch_selection').click(function() {
            $('#branch_selection_modal').modal('toggle');
        });
    </script>
@endpush
