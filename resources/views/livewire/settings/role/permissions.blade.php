<div>
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-light py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <button class="btn btn-primary d-inline-flex align-items-center gap-2" wire:click="syncPermission">
                        <i class="bi bi-arrow-repeat"></i>
                        Sync Permission
                    </button>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fa fa-search"></i>
                        </span>
                        <input type="text" wire:model.live="search" autofocus placeholder="Search permissions..." class="form-control border-start-0 ps-0" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form wire:submit="save">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <h3 class="card-title mb-0">Assign Permissions to</h3>
                    <span class="badge bg-primary fs-6">{{ $role->name }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold">Module</th>
                                <th width="100">
                                    <div class="form-check mb-0">
                                        {{ html()->checkbox('select_all')->value('')->checked(0)->class('form-check-input')->attribute('wire:model.live', 'select_all')->attribute('wire:click', 'selectAll') }}
                                        <label class="form-check-label user-select-none" for="select-all">All</label>
                                    </div>
                                </th>
                                <th>Permissions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach ($permissions as $module => $actions)
                                <tr>
                                    <td class="fw-medium">{{ ucFirst($module) }}</td>
                                    <td>
                                        <div class="form-check mb-0">
                                            {{ html()->checkbox('module')->value('')->checked(0)->class('form-check-input')->attribute('wire:model.live', 'module.' . $module)->attribute('wire:click', "moduleSelect('$module')") }}
                                            <label class="form-check-label" for="select-{{ $module }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-3">
                                            @foreach ($actions as $key => $action)
                                                <div class="form-check mb-0">
                                                    {{ html()->checkbox('selected.' . $key)->value('')->class('form-check-input')->attribute('wire:model.live', 'selected.' . $key) }}
                                                    <label class="form-check-label user-select-none" for="selected.{{ $key }}">
                                                        {{ ucFirst($action) }}
                                                    </label>
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
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        <i class="bi bi-check2-all me-2"></i>Update Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
