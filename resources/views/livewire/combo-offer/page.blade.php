<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Combo Offer Modal</h1>
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
                <div class="col-md-8">
                    <div class="form-group">
                        <h4><label for="name">Name</label></h4>
                        {{ html()->input('name')->value('')->class('form-control')->attribute('wire:model', 'combo_offers.name') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <h4><label for="count">Count</label></h4>
                        {{ html()->input('number', 'count')->class('form-control')->attribute('wire:model', 'combo_offers.count')->attribute('step', '0.01') }}
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <h4><label for="amount">Amount</label></h4>
                        {{ html()->input('number', 'amount')->class('form-control')->attribute('wire:model', 'combo_offers.amount')->attribute('step', '0.01') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <h4><label for="status">Status</label></h4>
                        {{ html()->select('status', ['active' => 'Active', 'inactive' => 'Inactive'])->class('form-control')->attribute('wire:model', 'combo_offers.status') }}
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="form-group">
                        <h4><label for="description">Description</label></h4>
                        {{ html()->textarea('description')->class('form-control')->attribute('wire:model', 'combo_offers.description')->rows(3) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" wire:click="save('completed')" class="btn btn-success">Save & Add New</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
