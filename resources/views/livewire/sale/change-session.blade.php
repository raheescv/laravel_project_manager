<div>
    <form wire:submit.prevent="save">
        <div class="modal-header">
            <h5 class="modal-title">Change Sale Day Session</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Current Session</label>
                <div class="form-control" readonly>
                    {{ $sale->sale_day_session_id ? '#' . $sale->sale_day_session_id : 'None' }}
                </div>
            </div>
            <div class="mb-3">
                <label for="sessionSelect" class="form-label">Select Open Session</label>
                <div wire:ignore>
                    {{ html()->select('selectedSessionId', $availableSessions)->value($selectedSessionId)->class('tomSelect form-select')->id('selectedSessionId')->placeholder('Select Session') }}
                </div>
                @error('selectedSessionId')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#selectedSessionId').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('selectedSessionId', value);
                });
            });
        </script>
    @endpush
</div>
