<div>
    <div class="modal-header bg-light">
        <h1 class="modal-title fs-5">
            <i class="fa fa-user-circle me-2 text-primary"></i>
            {{ isset($users['id']) ? 'Edit User' : 'Add New User' }}
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
                        <i class="fa fa-address-card me-1 text-primary"></i>
                        User Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="name" class="form-label small fw-medium">
                                    <i class="fa fa-user me-1 text-muted"></i>
                                    Full Name
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    {{ html()->input('name')->value('')->class('form-control border-secondary-subtle shadow-sm')->required(true)->attribute('wire:model', 'users.name')->placeholder('Enter full name') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label small fw-medium">
                                    <i class="fa fa-envelope me-1 text-muted"></i>
                                    Email Address
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-envelope"></i>
                                    </span>
                                    {{ html()->email('email')->value('')->class('form-control border-secondary-subtle shadow-sm')->required(true)->attribute('wire:model', 'users.email')->placeholder('example@company.com') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="mobile" class="form-label small fw-medium">
                                    <i class="fa fa-phone me-1 text-muted"></i>
                                    Mobile Number
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-phone"></i>
                                    </span>
                                    {{ html()->input('mobile')->value('')->class('form-control border-secondary-subtle shadow-sm')->required(true)->attribute('wire:model', 'users.mobile')->placeholder('Enter mobile number') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0">
                        <i class="fa fa-lock me-1 text-danger"></i>
                        Authentication
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="password" class="form-label small fw-medium">
                            <i class="fa fa-key me-1 text-muted"></i>
                            Password
                            @if (isset($users['id']))
                                <span class="text-muted small">(leave blank to keep current password)</span>
                            @endif
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-secondary-subtle">
                                <i class="fa fa-lock"></i>
                            </span>
                            {{ html()->password('password')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'users.password')->placeholder('Enter password') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-0">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0">
                        <i class="fa fa-users me-1 text-primary"></i>
                        Role Assignment
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-0">
                        <label class="form-label small fw-medium">
                            <i class="fa fa-lock me-1 text-muted"></i>
                            Assign Role
                        </label>
                        <div id="role-assignment">
                            @if (isset($roles) && count($roles) > 0)
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($roles as $role)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="{{ $role->name }}" id="role-{{ $role->id }}" wire:model="selectedRoles">
                                            <label class="form-check-label" for="role-{{ $role->id }}">
                                                {{ $role->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info py-2">
                                    <i class="fa fa-info-circle me-2"></i>
                                    No roles are available to assign. Please create roles first.
                                </div>
                            @endif
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
                        {{ isset($users['id']) ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
