<div>
    <div class="modal-header border-bottom pb-3">
        <h1 class="modal-title fs-5 fw-semibold">Package Category</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body py-4">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($this->getErrorBag()->toArray() as $field => $errors)
                            @foreach ($errors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name" class="form-label fw-medium">
                            Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="name" class="form-control @error('package_categories.name') is-invalid @enderror" wire:model="package_categories.name"
                            placeholder="Enter package category name">
                        @error('package_categories.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="price" class="form-label fw-medium">
                            Price <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" id="price" class="form-control @error('package_categories.price') is-invalid @enderror" wire:model="package_categories.price" step="0.01"
                                min="0" placeholder="0.00">
                            @error('package_categories.price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="frequency" class="form-label fw-medium">
                            Frequency
                        </label>
                        {{ html()->select('frequency', packageFrequency())->value('')->class('form-control border-secondary-subtle shadow-sm')->id('frequency')->attribute('wire:model.live', 'package_categories.frequency') }}
                        @error('package_categories.frequency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="no_of_visits" class="form-label fw-medium">
                            Number of Visits
                        </label>
                        <input type="number" id="no_of_visits" class="form-control @error('package_categories.no_of_visits') is-invalid @enderror" wire:model="package_categories.no_of_visits"
                            min="1" placeholder="Enter number of visits">
                        @error('package_categories.no_of_visits')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer border-top pt-3">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Close </button>
            <button type="button" wire:click="save('completed')" class="btn btn-success"> Save & Add New </button>
            <button type="submit" class="btn btn-primary"> Save </button>
        </div>
    </form>
</div>
