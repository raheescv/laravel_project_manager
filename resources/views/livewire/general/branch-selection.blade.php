<div>
    <div class="modal-header bg-primary">
        <h5 class="modal-title text-white">
            <i class="fa fa-building me-2"></i> Branch Selection
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading"><i class="fa fa-exclamation-triangle me-2"></i>Please correct the following errors:</h6>
                    <ul class="mb-0">
                        @foreach ($this->getErrorBag()->toArray() as $key => $value)
                            <li>{{ $value[0] }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="mb-3">
                <label for="branch_id" class="form-label fw-bold">Select Branch</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-building"></i></span>
                    {{ html()->select('branch_id', $assigned_branches)->class(['form-select', 'is-invalid' => $errors->has('branch_id')])->id('branch_id')->attributes(['wire:model.live' => 'branch_id'])->placeholder('Please Select Branch') }}
                </div>
                @error('branch_id')
                    <span class="text-danger small mt-1">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <div class="modal-footer border-top-0">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                <i class="fa fa-times me-2"></i>Close
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-2"></i>Save
            </button>
        </div>
    </form>
</div>
