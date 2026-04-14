<div>
    @php
        $selectedCount = 0;
        $totalCount = 0;
        $groupedPermissions = [];
        foreach ($permissions as $module => $moduleActions) {
            $totalCount += count($moduleActions);
            $selectedModulePermissions = [];
            foreach ($moduleActions as $key => $action) {
                if (!empty($selected[$key] ?? false)) {
                    $selectedModulePermissions[$key] = $action;
                    $selectedCount++;
                }
            }
            if (!empty($selectedModulePermissions)) {
                $groupedPermissions[$module] = $selectedModulePermissions;
            }
        }
    @endphp

    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 fs-6">
                        <i class="fa fa-user-shield me-1"></i> {{ $role->name }}
                    </span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-2">
                        <i class="fa fa-check-circle me-1"></i> {{ $selectedCount }} / {{ $totalCount }}
                    </span>
                    <button class="btn btn-primary btn-sm d-flex align-items-center shadow-sm" wire:click="syncPermission">
                        <i class="fa fa-sync-alt me-md-1"></i>
                        <span class="d-none d-md-inline">Sync</span>
                    </button>
                </div>
                <div class="col-md-6">
                    <div class="row g-2 align-items-center justify-content-end">
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" wire:model.live="search" autofocus placeholder="Search permissions..."
                                    class="form-control form-control-sm border-secondary-subtle shadow-sm" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            {{-- Tabs --}}
            <ul class="nav nav-tabs mb-0 border-0" id="permissionTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active d-flex align-items-center gap-2 fw-semibold" id="tab-selection" data-bs-toggle="tab"
                        data-bs-target="#pane-selection" type="button" role="tab" aria-controls="pane-selection" aria-selected="true">
                        <i class="fa fa-th-list text-primary"></i>
                        Permission Selection
                        <span class="badge bg-primary rounded-pill">{{ count($permissions) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2 fw-semibold" id="tab-selected" data-bs-toggle="tab"
                        data-bs-target="#pane-selected" type="button" role="tab" aria-controls="pane-selected" aria-selected="false">
                        <i class="fa fa-check-circle text-success"></i>
                        Selected Permissions
                        <span class="badge bg-success rounded-pill">{{ $selectedCount }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <form wire:submit="save">
                <div class="tab-content">
                    {{-- Tab 1: Permission Selection Table --}}
                    <div class="tab-pane fade show active" id="pane-selection" role="tabpanel" aria-labelledby="tab-selection">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                                <thead class="bg-light text-muted">
                                    <tr class="text-capitalize small">
                                        <th class="fw-semibold py-2 ps-3" style="min-width: 180px;">
                                            <i class="fa fa-cubes me-1 text-primary opacity-75"></i> Module
                                        </th>
                                        <th class="fw-semibold py-2" style="width: 80px;">
                                            <div class="form-check ms-1 mb-0">
                                                {{ html()->checkbox('select_all')->value('')->checked(0)->class('form-check-input shadow-sm')->attribute('wire:model.live', 'select_all')->attribute('wire:click', 'selectAll')->id('selectAllPerm') }}
                                                <label class="form-check-label user-select-none" for="selectAllPerm">All</label>
                                            </div>
                                        </th>
                                        <th class="fw-semibold py-2">
                                            <i class="fa fa-key me-1 text-primary opacity-75"></i> Permissions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($permissions as $module => $actions)
                                        <tr>
                                            <td class="ps-3 text-nowrap">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="d-inline-flex align-items-center justify-content-center rounded-2 bg-primary bg-opacity-10"
                                                        style="width: 30px; height: 30px;">
                                                        <i class="fa fa-cube text-primary small"></i>
                                                    </span>
                                                    <span class="fw-medium">{{ ucFirst($module) }}</span>
                                                    <span class="badge bg-light text-muted border small">{{ count($actions) }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-check ms-1 mb-0">
                                                    {{ html()->checkbox('module_' . $module)->value('')->checked(0)->class('form-check-input shadow-sm')->attribute('wire:model.live', 'module.' . $module)->attribute('wire:click', "moduleSelect('$module')")->id('module_' . $module) }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach ($actions as $key => $action)
                                                        <label for="perm_{{ $key }}" class="form-check d-inline-flex align-items-center gap-1 mb-0 px-2 py-1 rounded-pill border user-select-none
                                                            {{ !empty($selected[$key] ?? false) ? 'bg-primary bg-opacity-10 border-primary border-opacity-25' : 'bg-white border-secondary-subtle' }}"
                                                            style="cursor: pointer; transition: all 0.15s ease;">
                                                            {{ html()->checkbox('selected.' . $key)->value('')->class('form-check-input shadow-sm mt-0 me-1')->attribute('wire:model.live', 'selected.' . $key)->id('perm_' . $key) }}
                                                            <span class="form-check-label small fw-medium {{ !empty($selected[$key] ?? false) ? 'text-primary' : '' }}">{{ ucFirst($action) }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tab 2: Selected Permissions --}}
                    <div class="tab-pane fade" id="pane-selected" role="tabpanel" aria-labelledby="tab-selected">
                        @if ($selectedCount > 0)
                            <div class="p-3">
                                <div class="row g-3">
                                    @foreach ($groupedPermissions as $module => $selectedActions)
                                        <div class="col-xl-3 col-lg-4 col-md-6">
                                            <div class="card h-100 border shadow-sm">
                                                <div class="card-header bg-light py-2 px-3">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="d-inline-flex align-items-center justify-content-center rounded-2 bg-primary bg-opacity-10"
                                                                style="width: 26px; height: 26px;">
                                                                <i class="fa fa-cube text-primary" style="font-size: 0.75rem;"></i>
                                                            </span>
                                                            <span class="fw-semibold small">{{ ucFirst($module) }}</span>
                                                        </div>
                                                        <span class="badge bg-primary rounded-pill" style="font-size: 0.7rem;">{{ count($selectedActions) }}</span>
                                                    </div>
                                                </div>
                                                <div class="card-body p-2">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach ($selectedActions as $key => $action)
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-15 rounded-pill px-2 py-1 small fw-medium">
                                                                <i class="fa fa-check me-1" style="font-size: 0.6rem;"></i>{{ ucFirst($action) }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="fa fa-shield fa-3x mb-3 opacity-25 d-block"></i>
                                <h6 class="text-muted">No Permissions Selected</h6>
                                <p class="small mb-0">Go to <strong>Permission Selection</strong> tab to assign permissions to this role.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Save Footer --}}
                <div class="p-3 border-top bg-light text-end">
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm px-4">
                        <i class="fa fa-save"></i>
                        Update Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
