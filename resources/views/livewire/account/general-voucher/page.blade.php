<div>
    <div class="modal-header bg-light">
        <h1 class="modal-title fs-5">
            <i class="fa fa-file-invoice me-2 text-primary"></i>
            General Voucher
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
                            <li> {{ $errors[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0">
                        <i class="fa fa-list-alt me-1 text-primary"></i>
                        Voucher Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6" wire:ignore>
                            <div class="form-group">
                                <label for="debit" class="form-label small fw-medium">
                                    <i class="fa fa-arrow-up me-1 text-danger"></i>
                                    Debit Head <span class="text-danger">*</span>
                                </label>
                                {{ html()->select('debit', [])->value('')->class('select-account_id')->id('debit_id')->attribute('wire:model', 'journals.debit_id') }}
                            </div>
                        </div>
                        <div class="col-md-6" wire:ignore>
                            <div class="form-group">
                                <label for="credit" class="form-label small fw-medium">
                                    <i class="fa fa-arrow-down me-1 text-success"></i>
                                    Credit Head <span class="text-danger">*</span>
                                </label>
                                {{ html()->select('credit', [])->value('')->class('select-account_id')->id('credit_id')->attribute('wire:model', 'journals.credit_id') }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount" class="form-label small fw-medium">
                                    <i class="fa fa-money me-1 text-muted"></i>
                                    Amount <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-dollar"></i>
                                    </span>
                                    {{ html()->number('amount')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('max', 999999)->attribute('min', 1)->id('amount')->attribute('wire:model', 'journals.amount') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
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
                        Additional Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="person_name" class="form-label small fw-medium">
                                    <i class="fa fa-user me-1 text-muted"></i>
                                    Person Name
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    {{ html()->input('person_name')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'journals.person_name') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reference_number" class="form-label small fw-medium">
                                    <i class="fa fa-tag me-1 text-muted"></i>
                                    Reference Number
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-tag"></i>
                                    </span>
                                    {{ html()->text('reference_number')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'journals.reference_number') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="description" class="form-label small fw-medium">
                                    <i class="fa fa-pencil me-1 text-muted"></i>
                                    Description <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-pencil"></i>
                                    </span>
                                    {{ html()->textarea('description')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model.live', 'journals.description')->rows(2) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="remarks" class="form-label small fw-medium">
                                    <i class="fa fa-comment me-1 text-muted"></i>
                                    Remarks
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary-subtle">
                                        <i class="fa fa-comment"></i>
                                    </span>
                                    {{ html()->textarea('remarks')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'journals.remarks')->rows(2) }}
                                </div>
                            </div>
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
                $('#debit_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('journals.debit_id', value);
                    $('#amount').select();
                });
                $('#credit_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('journals.credit_id', value);
                    $('#amount').select();
                });
                window.addEventListener('SelectDropDownValues', event => {
                    journals = event.detail[0];
                    @this.set('journals.debit_id', journals.debit_id);
                    @this.set('journals.credit_id', journals.credit_id);

                    var debitTomSelect = document.querySelector('#debit_id').tomselect;
                    if (journals.debit_id) {
                        preselectedData = {
                            id: journals.debit_id,
                            name: journals.debit_name,
                        };
                        debitTomSelect.addOption(preselectedData);
                        debitTomSelect.addItem(preselectedData.id);
                    } else {
                        debitTomSelect.clear();
                    }

                    var creditTomSelect = document.querySelector('#credit_id').tomselect;
                    if (journals.credit_id) {
                        preselectedData = {
                            id: journals.credit_id,
                            name: journals.credit_name,
                        };
                        creditTomSelect.addOption(preselectedData);
                        creditTomSelect.addItem(preselectedData.id);
                    } else {
                        creditTomSelect.clear();
                    }
                });
            });
        </script>
    @endpush
</div>

