<div>
    <div class="content__header content__boxed rounded-0 mb-3 shadow-sm bg-white">
        <div class="content__wrap py-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users::index') }}" class="text-decoration-none">Users</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="content__boxed">
        <div class="content__wrap">
            <div class="row">
                <div class="col-lg-4 col-xl-3 mb-4 mb-lg-0">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center">
                            <img class="img-fluid rounded-circle border mb-3" src="{{ asset('assets/img/profile-photos/3.png') }}" alt="Profile Picture" loading="lazy"
                                style="width: 120px; height: 120px; object-fit: cover;">
                            <h4 class="card-title mb-0">{{ $user->name }}</h4>
                            <p class="text-muted mb-3">{{ getUserRoles($user) }}</p>
                            @if ($user->type == 'user')
                                <button class="btn btn-primary btn-sm text-nowrap" id="UserEdit"><i class="fa fa-edit me-1"></i>Edit Profile</button>
                            @else
                                <button class="btn btn-primary btn-sm text-nowrap" id="EmployeeEdit"><i class="fa fa-edit me-1"></i>Edit Profile</button>
                            @endif
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light py-3">
                            <h5 class="mb-0"><i class="fa fa-info-circle me-2"></i>About Me</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-user fa-fw me-2 text-muted"></i>Name</span>
                                    <span class="text-end">{{ $user->name }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-envelope fa-fw me-2 text-muted"></i>Email</span>
                                    <span class="text-end">{{ $user->email }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-phone fa-fw me-2 text-muted"></i>Mobile</span>
                                    <span class="text-end">{{ $user->mobile }}</span>
                                </li>
                                @if ($user->type == 'employee')
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fa fa-user fa-fw me-2 text-muted"></i>Code</span> {{-- Test with fa-user --}}
                                        <span class="text-end">{{ $user->code }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fa fa-map-marker fa-fw me-2 text-muted"></i>Place</span>
                                        <span class="text-end">{{ $user->place }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fa fa-flag fa-fw me-2 text-muted"></i>Nationality</span>
                                        <span class="text-end">{{ $user->nationality }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fa fa-calendar fa-fw me-2 text-muted"></i>DOB</span>
                                        <span class="text-end">{{ systemDate($user->dob) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fa fa-calendar fa-fw me-2 text-muted"></i>DOJ</span>
                                        <span class="text-end">{{ systemDate($user->doj) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fa fa-money fa-fw me-2 text-muted"></i>Allowance</span>
                                        <span class="text-end">{{ currency($user->allowance) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fa fa-credit-card fa-fw me-2 text-muted"></i>Salary</span>
                                        <span class="text-end">{{ currency($user->salary) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fa fa-home fa-fw me-2 text-muted"></i>HRA</span>
                                        <span class="text-end">{{ currency($user->hra) }}</span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-light py-3">
                            <h5 class="mb-0"><i class="fa fa-tags me-2"></i>Assigned Roles</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                @forelse ($role_names as $item)
                                    <span class="badge bg-secondary text-light-emphasis">{{ $item }}</span>
                                @empty
                                    <p class="text-muted mb-0">No roles assigned.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 col-xl-9">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light py-3">
                            <h5 class="mb-0"><i class="fa fa-shield me-2"></i>Manage Roles</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title mb-3">Assign User Roles</h6>
                            <div class="input-group mb-3" wire:ignore>
                                {{ html()->select('role_id', $roles)->value($role_names)->class('tomSelect')->multiple(true)->attribute('width', '100%')->attribute('wire:model', 'role_names')->id('roles_select') }}
                                <button class="btn btn-primary" type="button" wire:click="saveRoles" id="button-addon2">
                                    <i class="fa fa-save me-1"></i>Save
                                </button>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light py-3">
                            <h5 class="mb-0"><i class="fa fa-sitemap me-2"></i>Branches</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title mb-3">Assign User Branches</h6>
                            <div class="row">
                                <div class="col-md-10">
                                    <div class="mb-3" wire:ignore>
                                        {{ html()->select('branch_id', $branches)->value($branch_ids)->class('tomSelect')->multiple(true)->attribute('wire:model', 'branch_ids')->id('branch_ids_select') }}
                                        <label for="branch_ids_select">Branches</label>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <button class="btn btn-primary" type="button" wire:click="saveBranches" id="button-addon2">
                                        <i class="fa fa-save me-1"></i>Save
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div wire:ignore>
                                        {{ html()->select('default_branch_id', $default_branch)->value($default_branch_id)->class('select-assigned-branch_id-list')->attribute('width', '100%')->attribute('wire:model', 'default_branch_id')->id('default_branch_select') }}
                                        <label for="default_branch_select">Default Branch</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light py-3">
                            <h5 class="mb-0"><i class="fa fa-whatsapp me-2"></i>Whatsapp Settings</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title mb-3">Setup Whatsapp Notification</h6>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-0">
                                        <i class="fa fa-bell me-2"></i>Enable Whatsapp Notification:
                                        <span class="fw-bold">{{ $user->is_whatsapp_enabled ? 'Yes' : 'No' }}</span>
                                    </p>
                                </div>
                                <div class="form-check form-switch">
                                    {{ html()->checkbox('is_whatsapp_enabled')->value('')->checked($user->is_whatsapp_enabled)->class('form-check-input h5 m-0 position-relative')->attribute('wire:click', 'enabledWhatsapp')->id('whatsappSwitch') }}
                                    <label class="form-check-label" for="whatsappSwitch"></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-light py-3">
                            <h5 class="mb-0"><i class="fa fa-cogs me-2"></i>User Settings</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title mb-3">Manage User Status</h6>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted mb-0">
                                        <i class="fa fa-toggle-on me-2"></i>User Status:
                                        <span class="fw-bold">{{ $user->is_active ? 'Active' : 'Disabled' }}</span>
                                    </p>
                                </div>
                                <div class="form-check form-switch">
                                    {{ html()->checkbox('is_active')->value('')->checked($user->is_active)->class('form-check-input h5 m-0 position-relative')->attribute('wire:click', 'activeUser')->id('userStatusSwitch') }}
                                    <label class="form-check-label" for="userStatusSwitch"></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#UserEdit').click(function() {
                    Livewire.dispatch("User-Page-Update-Component", {
                        id: "{{ $user->id }}"
                    });
                });
                $('#EmployeeEdit').click(function() {
                    Livewire.dispatch("Employee-Page-Update-Component", {
                        id: "{{ $user->id }}"
                    });
                });
                window.addEventListener('RefreshUserPage', event => {
                    Livewire.dispatch("User-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
