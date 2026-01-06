<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Vendor Modal</h1>
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
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="form-group">
                        <b><label for="name" class="text-capitalize">name *</label></b>
                        {{ html()->input('name')->value('')->class('form-control')->attribute('wire:model', 'accounts.name') }}
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="mobile" class="text-capitalize">mobile</label></b>
                        {{ html()->input('mobile')->value('')->class('form-control')->attribute('wire:model.live', 'accounts.mobile') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="email" class="text-capitalize">email</label></b>
                        {{ html()->email('email')->value('')->class('form-control')->attribute('wire:model', 'accounts.email') }}
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="form-group">
                        <b><label for="place" class="text-capitalize">Place</label></b>
                        {{ html()->input('place')->value('')->class('form-control')->attribute('wire:model', 'accounts.place') }}
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="credit_period_days" class="text-capitalize">Credit Period (Days)</label></b>
                        {{ html()->number('credit_period_days')->value('')->class('form-control')->attribute('wire:model', 'accounts.credit_period_days')->placeholder('e.g., 30, 60, 90')->attribute('min', '0')->attribute('step', '1') }}
                        <small class="form-text text-muted">
                            <i class="fa fa-info-circle me-1"></i>
                            Number of days allowed for credit payment
                        </small>
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
