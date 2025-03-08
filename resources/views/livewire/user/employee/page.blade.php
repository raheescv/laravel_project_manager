<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Employee Modal</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    @if ($this->getErrorBag()->count())
                        <ol>
                            <?php foreach ($this->getErrorBag()->toArray() as $value): ?>
                            <li style="color:red">* {{ $value[0] }}</li>
                            <?php endforeach; ?>
                        </ol>
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="code" class="text-capitalize">Code</label>
                        {{ html()->input('code')->value('')->class('form-control')->attribute('wire:model', 'users.code') }}
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name" class="text-capitalize">name</label>
                        {{ html()->input('name')->value('')->class('form-control')->autofocus()->required(true)->attribute('wire:model', 'users.name') }}
                    </div>
                </div>
            </div>
            <div class="row my-2">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="email" class="text-capitalize">email</label>
                        {{ html()->email('email')->value('')->class('form-control')->required(true)->attribute('wire:model', 'users.email') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="mobile" class="text-capitalize">mobile</label>
                        {{ html()->input('mobile')->value('')->class('form-control')->attribute('wire:model', 'users.mobile') }}
                    </div>
                </div>
            </div>
            <div class="row my-2">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="place" class="text-capitalize">place</label>
                        {{ html()->input('place')->value('')->class('form-control')->attribute('wire:model', 'users.place') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="nationality" class="text-capitalize">nationality</label>
                        {{ html()->input('nationality')->value('')->class('form-control')->attribute('wire:model', 'users.nationality') }}
                    </div>
                </div>
            </div>
            <div class="row my-2">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="allowance" class="text-capitalize">allowance</label>
                        {{ html()->number('allowance')->value('')->class('form-control number')->attribute('wire:model', 'users.allowance') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="salary" class="text-capitalize">salary</label>
                        {{ html()->number('salary')->value('')->class('form-control number')->attribute('wire:model', 'users.salary') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="hra" class="text-capitalize">hra</label>
                        {{ html()->number('hra')->value('')->class('form-control number')->attribute('wire:model', 'users.hra') }}
                    </div>
                </div>
            </div>
            <div class="row my-2">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="dob" class="text-capitalize">dob</label>
                        {{ html()->date('dob')->value('')->class('form-control')->attribute('wire:model', 'users.dob') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="doj" class="text-capitalize">doj</label>
                        {{ html()->date('doj')->value('')->class('form-control')->attribute('wire:model', 'users.doj') }}
                    </div>
                </div>
            </div>
            <div class="row my-2">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password" class="text-capitalize">password</label>
                        {{ html()->password('password')->value('')->class('form-control')->attribute('wire:model', 'users.password') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="pin" class="text-capitalize">pin</label>
                        {{ html()->password('pin')->value('')->class('form-control')->attribute('wire:model', 'users.pin') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" wire:click="save(1)" class="btn btn-success">Save & Add New</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
