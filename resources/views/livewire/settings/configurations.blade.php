<div>
    <div class="col-md-4">
        <form wire:submit="save">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <label for="barcode_type">Barcode Type</label>
                        {{ html()->select('barcode_type', barcodeTypes())->value('')->class('form-control')->placeholder('Select Any')->attribute('wire:model', 'barcode_type') }}
                    </div>
                </div>
            </div>
            <div class="card-footer"> <br>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
