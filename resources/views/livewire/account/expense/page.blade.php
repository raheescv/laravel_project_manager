<div>
    <div class="modal-header bg-light">
        <h1 class="modal-title fs-5">
            <i class="fa fa-money me-2 text-primary"></i>
            Expense
        </h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger p-2 mb-3">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <strong>Please correct the following errors:</strong>
                    <ul class="mb-0 ps-3 mt-1">
                        @foreach ($this->getErrorBag()->toArray() as $field => $errors)
                            <li>{{ ucfirst($field) }}: {{ $errors[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0">
                        <i class="fa fa-list-alt me-1 text-primary"></i>
                        Expense Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4" wire:ignore>
                            <div class="form-group">
                                <label for="debit" class="form-label small fw-medium">
                                    <i class="fa fa-tags me-1 text-muted"></i>
                                    Expense Category <span class="text-danger">*</span>
                                </label>
                                {{ html()->select('debit', [])->value('')->class('select-account_id')->attribute('account_type', 'expense')->id('category_id')->attribute('wire:model', 'journals.debit') }}
                            </div>
                        </div>
                        <div class="col-md-4" wire:ignore>
                            <div class="form-group">
                                <label for="credit" class="form-label small fw-medium">
                                    <i class="fa fa-credit-card me-1 text-muted"></i>
                                    Payment Method <span class="text-danger">*</span>
                                </label>
                                {{ html()->select('credit', $paymentMethods ?? [])->value($default_payment_method_id ?? null)->id('payment_method_id')->class('select-payment_method_id-list')->attribute('wire:model', 'journals.credit') }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date" class="form-label small fw-medium">
                                    <i class="fa fa-calendar me-1 text-muted"></i>
                                    Date <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    {{ html()->input('date')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'journals.date') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0">
                        <i class="fa fa-info-circle me-1 text-primary"></i>
                        Payment Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="amount" class="form-label small fw-medium">
                                    <i class="fa fa-money me-1 text-muted"></i>
                                    Amount <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-dollar"></i>
                                    </span>
                                    {{ html()->number('amount')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('max', 999999)->attribute('min', 1)->id('journal_amount')->attribute('wire:model', 'journals.amount') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="person_name" class="form-label small fw-medium">
                                    <i class="fa fa-user me-1 text-muted"></i>
                                    Payee
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    {{ html()->input('person_name')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'journals.person_name') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="reference_number" class="form-label small fw-medium">
                                    <i class="fa fa-tag me-1 text-muted"></i>
                                    Reference No
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-tag"></i>
                                    </span>
                                    {{ html()->text('reference_number')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'journals.reference_number') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-0 border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0">
                        <i class="fa fa-file-text me-1 text-primary"></i>
                        Additional Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="description" class="form-label small fw-medium">
                            <i class="fa fa-pencil me-1 text-muted"></i>
                            Description <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-secondary-subtle">
                                <i class="fa fa-pencil"></i>
                            </span>
                            {{ html()->textarea('description')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model.live', 'journals.description')->rows(3) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer bg-light">
            <div class="d-flex justify-content-between w-100">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i>
                    Cancel
                </button>
                <div>
                    <button type="button" wire:click="save(1)" class="btn btn-success">
                        <i class="fa fa-save me-1"></i>
                        Save & Add New
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check me-1"></i>
                        Save
                    </button>
                </div>
            </div>
        </div>
    </form>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('journals.debit', value);
                    $('#journal_amount').select();
                });
                $('#payment_method_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('journals.credit', value);
                    $('#journal_amount').select();
                });
                window.addEventListener('SelectDropDownValues', event => {
                    journals = event.detail[0];
                    @this.set('journals.debit', journals.debit);
                    var tomSelectInstance = document.querySelector('#category_id').tomselect;
                    if (journals.debit) {
                        preselectedData = {
                            id: journals.debit,
                            name: journals.debit_name,
                        };
                        console.log(preselectedData);
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
