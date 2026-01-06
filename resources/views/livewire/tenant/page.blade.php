<div>
    <div class="modal-header bg-light">
        <h1 class="modal-title fs-5">
            <i class="fa fa-building me-2 text-primary"></i>
            {{ isset($tenants['id']) ? 'Edit Tenant' : 'Add New Tenant' }}
        </h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger p-2 mb-3">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <strong>Please correct the following errors:</strong>
                    <ul class="mb-0 ps-3 mt-1">
                        @foreach ($this->getErrorBag()->toArray() as $field => $errors)
                            <li>{{ ucfirst($field) }}: {{ $errors[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0">
                        <i class="fa fa-building me-1 text-primary"></i>
                        Tenant Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name" class="form-label small fw-medium">
                                    <i class="fa fa-building me-1 text-muted"></i>
                                    Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-building"></i>
                                    </span>
                                    {{ html()->input('text')->value('')->class('form-control border-secondary-subtle shadow-sm')->required(true)->attribute('wire:model', 'tenants.name')->placeholder('Enter tenant name') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code" class="form-label small fw-medium">
                                    <i class="fa fa-code me-1 text-muted"></i>
                                    Code <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-code"></i>
                                    </span>
                                    {{ html()->input('text')->value('')->class('form-control border-secondary-subtle shadow-sm')->required(true)->attribute('wire:model', 'tenants.code')->placeholder('Enter tenant code') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="subdomain" class="form-label small fw-medium">
                                    <i class="fa fa-globe me-1 text-muted"></i>
                                    Subdomain <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-globe"></i>
                                    </span>
                                    {{ html()->input('text')->value('')->class('form-control border-secondary-subtle shadow-sm')->required(true)->attribute('wire:model', 'tenants.subdomain')->placeholder('Enter subdomain') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="domain" class="form-label small fw-medium">
                                    <i class="fa fa-link me-1 text-muted"></i>
                                    Domain
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-link"></i>
                                    </span>
                                    {{ html()->input('text')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'tenants.domain')->placeholder('Enter domain (optional)') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="description" class="form-label small fw-medium">
                                    <i class="fa fa-align-left me-1 text-muted"></i>
                                    Description
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-align-left"></i>
                                    </span>
                                    {{ html()->textarea('description')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'tenants.description')->placeholder('Enter description (optional)')->rows(3) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" wire:model="tenants.is_active">
                                <label class="form-check-label" for="is_active">
                                    <i class="fa fa-toggle-on me-1 text-muted"></i>
                                    Active Status
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer bg-light">
            <div class="d-flex justify-content-between w-100">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i>
                    Cancel
                </button>
                <div>
                    <button type="button" wire:click="save(1)" class="btn btn-success">
                        <i class="fa fa-save me-1"></i>
                        Save & Add New
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check me-1"></i>
                        {{ isset($tenants['id']) ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

