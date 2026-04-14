<div>
    <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">
            <i class="demo-psi-gear fs-4 me-2 text-primary"></i>
            {{ isset($formData['id']) ? 'Edit Tenant Detail' : 'Create New Tenant Detail' }}
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
                <div class="col-md-12">
                    <div class="form-floating">
                        <select class="form-select @error('formData.property_id') is-invalid @enderror" id="property_id" wire:model="formData.property_id" required>
                            <option value="">Select...</option>
                            @foreach(\App\Models\Property::all() as $property)
                                <option value="{{ $property->id }}">{{ $property->name }}</option>
                            @endforeach
                        </select>
                        <label for="property_id">Property</label>
                        @error('formData.property_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.name') is-invalid @enderror" id="name" placeholder="Tenant Name" required wire:model="formData.name">
                        <label for="name">Name</label>
                        @error('formData.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.mobile') is-invalid @enderror" id="mobile" placeholder="Mobile" wire:model="formData.mobile">
                        <label for="mobile">Mobile</label>
                        @error('formData.mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="email" class="form-control @error('formData.email') is-invalid @enderror" id="email" placeholder="Email" wire:model="formData.email">
                        <label for="email">Email</label>
                        @error('formData.email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.emirates_id') is-invalid @enderror" id="emirates_id" placeholder="Emirates ID" wire:model="formData.emirates_id">
                        <label for="emirates_id">Emirates ID</label>
                        @error('formData.emirates_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.passport_no') is-invalid @enderror" id="passport_no" placeholder="Passport No" wire:model="formData.passport_no">
                        <label for="passport_no">Passport No</label>
                        @error('formData.passport_no')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.nationality') is-invalid @enderror" id="nationality" placeholder="Nationality" wire:model="formData.nationality">
                        <label for="nationality">Nationality</label>
                        @error('formData.nationality')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-floating">
                        <textarea class="form-control @error('formData.address') is-invalid @enderror" id="address" placeholder="Address" wire:model="formData.address" style="height: 100px"></textarea>
                        <label for="address">Address</label>
                        @error('formData.address')
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
                    <span>Save Tenant Detail</span>
                </button>
            </div>
        </div>
    </form>
</div>
