@php
    // No branch in the session: ask for one before anything else.
    $branchRequired = \App\Livewire\General\BranchSelection::isRequired();
@endphp
<div id="branch_selection_modal" class="modal fade" tabindex="-1" aria-hidden="true" @if ($branchRequired) data-bs-backdrop="static" data-bs-keyboard="false" @endif>
    <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
        <div class="modal-content" style="border: 0; border-radius: 18px; overflow: hidden;">
            @livewire('general.branch-selection', ['required' => $branchRequired])
        </div>
    </div>
</div>
@push('scripts')
    <script>
        $('#branch_selection').click(function() {
            $('#branch_selection_modal').modal('toggle');
        });
        @if ($branchRequired)
            $('#branch_selection_modal').modal('show');
        @endif
    </script>
@endpush
