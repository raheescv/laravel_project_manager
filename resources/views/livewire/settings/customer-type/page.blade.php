<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Customer Type Modal</h1>
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
                <div class="mb-3">
                    <label for="name">Name</label>
                    {{ html()->input('name')->value('')->class('form-control')->required(true)->attribute('wire:model', 'customer_types.name') }}
                </div>
                <div class="mb-3">
                    <label for="discount_percentage">Discount Percentage (%)</label>
                    {{ html()->number('discount_percentage')->value('')->class('form-control')->required(true)->attribute('wire:model', 'customer_types.discount_percentage')->attribute('min', '0')->attribute('max', '100')->attribute('step', '0.01')->placeholder('Enter discount percentage (0-100)') }}
                    <small class="form-text text-muted">Enter a value between 0 and 100</small>
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
