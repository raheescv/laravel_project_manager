<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Product Unit Measurement Modal</h1>
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
                        <label for="unit_name" class="form-label">Convert From (Base Unit)</label>
                        {{ html()->input('unit_name')->value('')->class('form-control')->attribute('disabled', false)->attribute('wire:model', 'product_units.product.unit.name') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label for="name" class="form-label">Convert To *<span style="font-size:8px ">(1 base unit = ? Sub unit)</span></label>
                    {{ html()->select('sub_unit_id', $units)->value('')->class('form-control')->placeholder('Select your unit')->id('sub_unit_id')->attribute('wire:model', 'product_units.sub_unit_id') }}
                </div>
                <div class="col-md-6">
                    <label for="conversion_factor" class="form-label">Conversion Factor *</label>
                    {{ html()->number('conversion_factor')->value('')->attribute('step', 'any')->class('form-control number')->required(true)->autofocus()->id('conversion_factor')->placeholder('Your Conversion Factor')->attribute('wire:model', 'product_units.conversion_factor') }}
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="barcode" class="form-label">Barcode *</label>
                        {{ html()->input('barcode')->value('')->attribute('step', 'any')->class('form-control')->required(true)->placeholder('Enter your Barcode')->attribute('wire:model', 'product_units.barcode') }}
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
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#sub_unit_id').on('change', function(e) {
                    $("#conversion_factor").select();
                });
            });
        </script>
    @endpush
</div>
