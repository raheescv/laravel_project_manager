<div>
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white p-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" wire:click="syncPermission">
                            <i class="fa fa-sync-alt"></i>
                            <span>Sync Permissions</span>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-search text-muted"></i>
                        </span>
                        <input type="text" wire:model.live="search" class="form-control border-start-0 ps-0" placeholder="Search permissions..." autofocus autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form wire:submit="save">
                <div class="d-flex align-items-center gap-3 mb-4 bg-light p-3 rounded-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa fa-user-shield fs-4 text-primary"></i>
                        <h5 class="mb-0">Configuring Permissions for Role:</h5>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 fw-semibold">
                        {{ $role->name }}
                    </span>
                </div>

                <!-- Selected Permissions Display -->
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fa fa-check-circle text-success"></i>
                        <h6 class="mb-0">Selected Permissions</h6>
                    </div>
                    <div class="bg-light bg-opacity-50 rounded-3 p-3">
                        <div class="row g-3">
                            @php
                                $selectedCount = 0;
                                $groupedPermissions = [];
                            @endphp
                            @foreach ($permissions as $module => $moduleActions)
                                @php
                                    $selectedModulePermissions = [];
                                    foreach ($moduleActions as $key => $action) {
                                        if (!empty($selected[$key] ?? false)) {
                                            $selectedModulePermissions[] = $action;
                                            $selectedCount++;
                                        }
                                    }
                                    if (!empty($selectedModulePermissions)) {
                                        $groupedPermissions[$module] = $selectedModulePermissions;
                                    }
                                @endphp
                            @endforeach

                            @if ($selectedCount > 0)
                                @foreach ($groupedPermissions as $module => $selectedActions)
                                    <div class="col-md-4">
                                        <div class="card h-100 border-0 shadow-sm">
                                            <div class="card-header bg-primary bg-opacity-10 py-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-folder text-primary"></i>
                                                    <span class="fw-medium">{{ ucFirst($module) }}</span>
                                                </div>
                                            </div>
                                            <div class="card-body p-2">
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach ($selectedActions as $action)
                                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">
                                                            <i class="fa fa-check-circle me-1"></i>
                                                            {{ ucFirst($action) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-12">
                                    <div class="text-center text-muted py-4">
                                        <i class="fa fa-info-circle fs-2 mb-2"></i>
                                        <p class="mb-0">No permissions selected yet</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle border mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-bottom py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-cubes text-primary"></i>
                                        <span class="fw-semibold">Module</span>
                                    </div>
                                </th>
                                <th class="border-bottom py-3" width="100">
                                    <div class="form-check mb-0">
                                        {{ html()->checkbox('select_all')->value('')->checked(0)->class('form-check-input')->attribute('wire:model.live', 'select_all')->attribute('wire:click', 'selectAll') }}
                                        <label class="form-check-label user-select-none" for="select-all">
                                            <i class="fa fa-check-double text-primary ms-1"></i> All
                                        </label>
                                    </div>
                                </th>
                                <th class="border-bottom py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-key text-primary"></i>
                                        <span class="fw-semibold">Permissions</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($permissions as $module => $actions)
                                <tr>
                                    <td class="py-3 text-nowrap">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa fa-folder text-warning"></i>
                                            <span class="fw-medium">{{ ucFirst($module) }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div class="form-check mb-0">
                                            {{ html()->checkbox('module')->value('')->checked(0)->class('form-check-input')->attribute('wire:model.live', 'module.' . $module)->attribute('wire:click', "moduleSelect('$module')") }}
                                            <label class="form-check-label" for="select-{{ $module }}">
                                                <i class="fa fa-check text-success ms-1"></i>
                                            </label>
                                        </div>
                                    </td>
                                    {{-- action area --}}
                                    <td class="py-3">
                                        <div class="d-flex flex-wrap gap-3">
                                            @foreach ($actions as $key => $action)
                                                <div class="form-check form-check-inline mb-0">
                                                    <div class="d-flex align-items-center gap-2 bg-light rounded-pill px-3 py-2">
                                                        {{ html()->checkbox('selected.' . $key)->value('')->class('form-check-input mt-0')->attribute('wire:model.live', 'selected.' . $key) }}
                                                        <label class="form-check-label user-select-none small fw-medium" for="selected.{{ $key }}">
                                                            {{ ucFirst($action) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2">
                        <i class="fa fa-save fs-5"></i>
                        <span>Update Permissions</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
