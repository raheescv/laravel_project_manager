<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Income</h1>
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
                <div class="col-md-4" wire:ignore>
                    <div class="form-group">
                        <b><label for="credit" class="text-capitalize">Income Category *</label></b>
                        {{ html()->select('credit', [])->value('')->class('select-account_id')->attribute('account_type', 'income')->id('category_id')->attribute('wire:model', 'journals.credit') }}
                    </div>
                </div>
                <div class="col-md-4" wire:ignore>
                    <div class="form-group">
                        <b><label for="debit" class="text-capitalize">Payment Method *</label></b>
                        {{ html()->select('debit', $paymentMethods ?? [])->value($default_payment_method_id ?? null)->id('payment_method_id')->class('select-payment_method_id-list')->attribute('wire:model', 'journals.debit') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <b><label for="date" class="text-capitalize">Date *</label></b>
                        {{ html()->input('date')->value('')->class('form-control')->attribute('wire:model', 'journals.date') }}
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <b><label for="amount" class="text-capitalize">Amount *</label></b>
                        {{ html()->number('amount')->value('')->class('form-control')->attribute('max', 999999)->attribute('min', 1)->id('journal_amount')->attribute('wire:model', 'journals.amount') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <b><label for="person_name" class="text-capitalize">Receiver</label></b>
                        {{ html()->input('person_name')->value('')->class('form-control')->attribute('wire:model', 'journals.person_name') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <b><label for="reference_number" class="text-capitalize">Reference No</label></b>
                        {{ html()->text('reference_number')->value('')->class('form-control')->attribute('wire:model', 'journals.reference_number') }}
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="form-group">
                        <b><label for="description" class="text-capitalize">Description *</label></b>
                        {{ html()->textarea('description')->class('form-control')->attribute('wire:model.live', 'journals.description') }}
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
                $('#category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('journals.credit', value);
                    $('#journal_amount').select();
                });
                $('#payment_method_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('journals.debit', value);
                    $('#journal_amount').select();
                });
                window.addEventListener('SelectDropDownValues', event => {
                    journals = event.detail[0];
                    @this.set('journals.credit', journals.credit);
                    var tomSelectInstance = document.querySelector('#category_id').tomselect;
                    if (journals.credit) {
                        preselectedData = {
                            id: journals.credit,
                            name: journals.credit_name,
                        };
                        tomSelectInstance.addOption(preselectedData);
                        tomSelectInstance.addItem(preselectedData.id);
                    } else {
                        tomSelectInstance.clear();
                    }
                });
            });
        </script>
    @endpush
</div>
