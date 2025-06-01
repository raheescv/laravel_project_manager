<div>
    <div class="modal-header bg-gradient-primary text-white">
        <h1 class="modal-title fs-5 text-black d-flex align-items-center">
            <i class="fa fa-user-plus me-2"></i>
            <span>Customer Information</span>
        </h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body bg-light p-4 bg-white">
            <div class="row mb-3">
                <div class="col-md-12">
                    @if ($this->getErrorBag()->count())
                        <div class="alert alert-danger p-3 border-start border-danger border-4 shadow-sm">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa fa-exclamation-triangle me-2 fs-4 text-danger"></i>
                                <strong>Please correct the following errors:</strong>
                            </div>
                            <ul class="list-unstyled mb-0 ms-4">
                                <?php foreach ($this->getErrorBag()->toArray() as $value): ?>
                                <li><i class="fa fa-times-circle me-1 text-danger"></i> {{ $value[0] }}</li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <div class="row mb-3 g-3">
                <div class="col-md-8">
                    <div class="form-floating">
                        {{ html()->input('name')->value('')->class('form-control border-0 bg-light shadow-sm')->attribute('wire:model', 'accounts.name')->placeholder('Full Name') }}
                        <label for="name" class="text-capitalize text-muted">
                            <i class="fa fa-user me-1 text-primary"></i>
                            Full Name
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group" wire:ignore>
                        <label for="customer_type_id" class="form-label mb-2 text-capitalize d-flex align-items-center">
                            <i class="fa fa-tag me-1 text-primary"></i>
                            <span>Customer Type</span>
                        </label>
                        {{ html()->select('customer_type_id', [])->value('')->class('select-customer_type-id  border-0 bg-light shadow-sm')->id('modal_customer_type_id')->attribute('wire:model', 'accounts.customer_type_id') }}
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-3">
                <div class="col-md-6">
                    <div class="form-floating">
                        {{ html()->input('mobile')->value('')->class('form-control border-0 bg-light shadow-sm')->attribute('wire:model.live', 'accounts.mobile')->placeholder('Mobile Number') }}
                        <label for="mobile" class="text-capitalize text-muted">
                            <i class="fa fa-mobile me-1 text-primary"></i>
                            Mobile
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        {{ html()->input('whatsapp_mobile')->value('')->class('form-control border-0 bg-light shadow-sm')->attribute('wire:model.live', 'accounts.whatsapp_mobile')->placeholder('WhatsApp Number') }}
                        <label for="whatsapp_mobile" class="text-capitalize text-muted">
                            <i class="fa fa-whatsapp me-1 text-success"></i>
                            WhatsApp Number
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-4 g-3">
                <div class="col-md-4">
                    <div class="form-floating">
                        {{ html()->email('email')->value('')->class('form-control border-0 bg-light shadow-sm')->attribute('wire:model', 'accounts.email')->placeholder('Email Address') }}
                        <label for="email" class="text-capitalize text-muted">
                            <i class="fa fa-envelope me-1 text-primary"></i>
                            Email Address
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        {{ html()->date('dob')->value('')->class('form-control border-0 bg-light shadow-sm')->attribute('wire:model', 'accounts.dob')->placeholder('Date of Birth') }}
                        <label for="dob" class="text-capitalize text-muted">
                            <i class="fa fa-birthday-cake me-1 text-primary"></i>
                            Date of Birth
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        {{ html()->input('company')->value('')->class('form-control border-0 bg-light shadow-sm')->attribute('wire:model', 'accounts.company')->placeholder('Company') }}
                        <label for="company" class="text-capitalize text-muted">
                            <i class="fa fa-building me-1 text-primary"></i>
                            Company
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-3 g-3">
                <div class="col-md-4">
                    <div class="form-floating">
                        {{ html()->input('id_no')->value('')->class('form-control border-0 bg-light shadow-sm')->attribute('wire:model.live', 'accounts.id_no')->placeholder('ID Number') }}
                        <label for="id_no" class="text-capitalize text-muted">
                            <i class="fa fa-card-clip me-1 text-primary"></i>
                            ID Number
                        </label>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group" wire:ignore>
                        <label for="nationality" class="form-label mb-2 text-capitalize d-flex align-items-center">
                            <i class="fa fa-globe me-1 text-primary"></i>
                            <span>Nationality</span>
                        </label>
                        {{ html()->select('nationality', $countries)->value('')->class('tomSelect  border-0 bg-light shadow-sm')->id('modal_nationality')->placeholder('Select nationality')->attribute('wire:model.live', 'accounts.nationality') }}
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light pt-4">
                <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal">
                    <i class="fa fa-times me-2"></i>Cancel
                </button>
                <button type="button" wire:click="save(1)" class="btn btn-success px-4 py-2">
                    <i class="fa fa-save me-2"></i>Save & Add New
                </button>
                <button type="submit" class="btn btn-primary px-4 py-2">
                    <i class="fa fa-check me-2"></i>Save Customer
                </button>
            </div>

            @if (count($existingCustomers))
                <div class="mt-4 p-4 bg-light border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-primary mb-0">
                            <i class="fa fa-exclamation-circle me-2"></i>
                            Similar Customers Found
                        </h5>
                    </div>
                    <div class="table-responsive rounded shadow-sm">
                        <table class="table table-hover bg-white mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-3">Name</th>
                                    <th class="py-3">Mobile</th>
                                    <th class="py-3">Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($existingCustomers as $item)
                                    <tr wire:click="selectCustomer('{{ $item->id }}')" class="pointer hover-bg-light" style="cursor: pointer;">
                                        <td class="py-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fa fa-user-circle me-2 text-primary"></i>
                                                {{ $item['name'] }}
                                            </div>
                                        </td>
                                        <td class="py-3">{{ $item['mobile'] }}</td>
                                        <td class="py-3">{{ $item['email'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 text-muted small">
                        <i class="fa fa-info-circle me-1"></i>
                        Click on a row to select an existing customer
                    </div>
                </div>
            @endif
        </div>
    </form>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#modal_nationality').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('accounts.nationality', value);
                });
                window.addEventListener('SelectDropDownValues', event => {
                    var data = event.detail[0];
                    @this.set('accounts.nationality', data.nationality);
                    var tomSelectInstance = document.querySelector('#modal_nationality').tomselect;
                    if (data.nationality) {
                        preselectedData = {
                            id: data.nationality,
                            name: data.nationality,
                        };
                        console.log(preselectedData);
                        tomSelectInstance.addOption(preselectedData);
                        tomSelectInstance.addItem(preselectedData.id);
                    } else {
                        tomSelectInstance.clear();
                    }
                    @this.set('accounts.customer_type_id', data.customer_type_id);
                    var tomSelectInstance = document.querySelector('#modal_customer_type_id').tomselect;
                    if (data.customer_type_id) {
                        preselectedData = {
                            id: data.customer_type_id,
                            name: data.customer_type['name'],
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
