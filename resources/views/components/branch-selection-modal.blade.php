<div id="branch_selection_modal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
        <div class="modal-content" style="border: 0; border-radius: 18px; overflow: hidden;">
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
