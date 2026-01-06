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
                <label class="form-label fw-bold mb-3">Select Branch</label>
                <div class="row g-3">
                    @foreach ($assigned_branches as $branch)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 cursor-pointer border-2 transition-all {{ $branch_id == $branch->id ? 'border-primary shadow-sm bg-light' : 'border-light' }}"
                                wire:click="$set('branch_id', {{ $branch->id }})" style="cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.transform='scale(1.02)'"
                                onmouseout="this.style.transform='scale(1)'">
                                <div class="card-body d-flex align-items-center p-3">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fa fa-building fa-lg"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold {{ $branch_id == $branch->id ? 'text-primary' : '' }}">{{ $branch->name }}</h6>
                                        <p class="mb-0 text-muted small">
                                            <i class="fa fa-code me-1"></i>{{ $branch->code }}
                                            @if ($branch->location)
                                                <br><i class="fa fa-map-marker-alt me-1"></i>{{ $branch->location }}
                                            @endif
                                        </p>
                                    </div>
                                    @if ($branch_id == $branch->id)
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-check-circle text-primary fa-lg"></i>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('branch_id')
                    <span class="text-danger small mt-2 d-block">{{ $message }}</span>
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
