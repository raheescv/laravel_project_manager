<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Inventory Modal</h1>
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
                        <h4> <label for="batch">Batch</label> </h4>
                        {{ html()->input('batch')->value('')->class('form-control')->required(true)->disabled(true)->attribute('wire:model', 'inventories.batch') }}
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <h4> <label for="barcode">Barcode</label> </h4>
                        {{ html()->input('barcode')->value('')->class('form-control')->required(true)->disabled(true)->attribute('wire:model', 'inventories.barcode') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <h4> <label for="batch">Quantity</label> </h4>
                        {{ html()->number('quantity')->value('')->class('form-control number')->autofocus()->attribute('wire:model', 'inventories.quantity') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <h4> <label for="batch">Remarks</label> </h4>
                        {{ html()->input('remarks')->value('')->class('form-control')->required(true)->attribute('wire:model', 'inventories.remarks') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
