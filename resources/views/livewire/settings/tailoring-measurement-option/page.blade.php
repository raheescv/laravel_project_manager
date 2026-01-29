<div>
    <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="tailoringMeasurementOptionModalLabel">
            <i class="demo-psi-ruler fs-4 me-2 text-primary"></i>
            {{ $options['id'] ?? null ? 'Edit Measurement Option' : 'Add Measurement Option' }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body py-4">
            @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="demo-pli-danger-2 fs-4 me-2"></i>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-12">
                    <div class="form-floating">
                        <select class="form-select @error('options.option_type') is-invalid @enderror" id="options_option_type" wire:model="options.option_type">
                            <option value="">-- Select Option Type --</option>
                            @foreach(\App\Models\TailoringMeasurementOption::OPTION_TYPES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <label for="options_option_type">Option Type</label>
                        @error('options.option_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('options.value') is-invalid @enderror" id="options_value" placeholder="e.g. Round, Square" required wire:model="options.value">
                        <label for="options_value">Value</label>
                        @error('options.value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <div class="ms-auto d-flex gap-2">
                <button type="button" wire:click="save(1)" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <i class="demo-psi-repeat-2 fs-5"></i>
                    <span>Save & Add Another</span>
                </button>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4">
                    <i class="demo-psi-save fs-5"></i>
                    <span>Save</span>
                </button>
            </div>
        </div>
    </form>
</div>
