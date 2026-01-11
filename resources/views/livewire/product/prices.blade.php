<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Product Prices Modal</h1>
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
                <div class="col-md-6">
                    <label for="name" class="form-label">Price Type *</label>
                    {{ html()->select('price_type', $priceTypes)->value('')->class('form-control')->placeholder('Select your type')->id('price_type')->attribute('wire:model.live', 'product_prices.price_type') }}
                </div>
                <div class="col-md-6">
                    <label for="amount" class="form-label">Amount *</label>
                    {{ html()->number('amount')->value('')->attribute('step', 'any')->class('form-control number')->required(true)->autofocus()->id('amount')->placeholder('Product selling price')->attribute('wire:model', 'product_prices.amount') }}
                </div>
            </div>
            @if ($product_prices['price_type'] == 'offer')
                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date" class="form-label">Start Date *</label>
                            {{ html()->date('start_date')->value('')->class('form-control')->required(true)->attribute('wire:model', 'product_prices.start_date') }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_date" class="form-label">End Date *</label>
                            {{ html()->date('end_date')->value('')->class('form-control')->required(true)->attribute('wire:model', 'product_prices.end_date') }}
                        </div>
                    </div>
                </div>
            @endif
            @if (isset($product_prices['id']))
                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="start_date" class="form-label">Status</label>
                            {{ html()->select('status', activeOrDisabled())->value('')->class('form-control')->required(true)->attribute('wire:model', 'product_prices.status') }}
                        </div>
                    </div>
                </div>
            @endif
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
                $('#price_type').on('change', function(e) {
                    $("#amount").select();
                });
            });
        </script>
    @endpush
</div>
