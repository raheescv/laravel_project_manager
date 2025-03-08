<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">User Modal</h1>
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
                <div class="col-md-12">
                    <div class="form-group">
                        <b><label for="name">Name</label></b>
                        {{ html()->input('name')->value('')->class('form-control')->required(true)->attribute('wire:model', 'users.name') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="email">Email</label></b>
                        {{ html()->email('email')->value('')->class('form-control')->required(true)->attribute('wire:model', 'users.email') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="mobile">Mobile</label></b>
                        {{ html()->input('mobile')->value('')->class('form-control')->required(true)->attribute('wire:model', 'users.mobile') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <b><label for="password">Password</label></b>
                        {{ html()->password('password')->value('')->class('form-control')->required(true)->attribute('wire:model', 'users.password') }}
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
