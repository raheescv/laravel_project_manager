<div>
    <div class="modal-header">
        <h5>{{ $item['name'] ?? '' }}</h5>
    </div>
    <form wire:submit="submit">
        <div class="modal-body">
            <div class="row mb-2">
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="unit_price" class="text-capitalize text-end">Unit Price</label></b>
                        {{ html()->number('unit_price')->value('')->class('form-control number select_on_focus')->attribute('wire:model.live', 'item.unit_price') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="quantity" class="text-capitalize text-end">quantity</label></b>
                        {{ html()->number('quantity')->value('')->class('form-control number select_on_focus')->attribute('step','any')->attribute('wire:model.live', 'item.quantity') }}
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="discount" class="text-capitalize text-end">Discount</label></b>
                        {{ html()->number('discount')->value('')->class('form-control number select_on_focus')->attribute('wire:model.live', 'item.discount') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="tax" class="text-capitalize text-end">tax %</label></b>
                        {{ html()->number('tax')->value('')->attribute('max', '50')->class('form-control number select_on_focus')->attribute('style', 'width:100%')->attribute('wire:model.live', 'item.tax') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <b><label for="total" class="text-capitalize text-end">total</label></b>
                        {{ html()->number('total')->value('')->class('form-control number')->disabled(true)->attribute('wire:model.live', 'item.total') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer d-sm-flex justify-content-end">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </form>
</div>
