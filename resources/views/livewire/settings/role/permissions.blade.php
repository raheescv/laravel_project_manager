<div>
    <div class="card-header -4 mb-3">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
            </div>
            <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                <div class="form-group">
                    <input type="text" wire:model.live="search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <form wire:submit="save">
                <h3 class="mb-4">Assign Permissions to <b>{{ $role->name }}</b> </h3>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>
                                {{ html()->checkbox('select_all')->value('')->checked(0)->class('m-0 form-check-input h5 position-relative')->attribute('wire:model.live', 'select_all')->attribute('wire:click', 'selectAll') }}
                                <label for="select-all">All</label>
                            </th>
                            <th>Permissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permissions as $module => $actions)
                            <tr>
                                <td><strong>{{ ucFirst($module) }}</strong></td>
                                <td>
                                    {{ html()->checkbox('module')->value('')->checked(0)->class('m-0 form-check-input h5 position-relative')->attribute('wire:model.live', 'module.' . $module)->attribute('wire:click', "moduleSelect('$module')") }}
                                    <label for="select-{{ $module }}"> </label>
                                </td>
                                <td>
                                    @foreach ($actions as $key => $action)
                                        <div class="form-check form-check-inline">
                                            {{ html()->checkbox('selected.' . $key)->value('')->class('m-0 form-check-input h5 position-relative')->attribute('wire:model.live', 'selected.' . $key) }}
                                            <label for="selected.{{ $key }}" class="form-check-label">
                                                &nbsp; {{ ucFirst($action) }}
                                            </label>
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="text-center">
                    <button type="submit" class="btn btn-success">Update Permissions</button>
                </div>
            </form>
        </div>
    </div>
</div>
