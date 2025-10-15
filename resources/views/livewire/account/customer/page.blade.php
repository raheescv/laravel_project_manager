<div>
    <div class="modal-header bg-primary bg-gradient text-white">
        <h1 class="modal-title fs-5 d-flex align-items-center text-white">
            <i class="fa fa-user-plus me-2"></i>
            <span>Customer Information</span>
            @if (isset($accounts['id']))
                <span class="badge bg-light text-primary ms-2 fs-6 d-flex align-items-center">
                    <i class="fa fa-id-badge me-1"></i>ID: {{ $accounts['id'] }}
                </span>
            @endif
        </h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body p-4 bg-white">

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 fw-semibold d-flex align-items-center">
                        <i class="fa fa-user-circle me-2 text-primary"></i>
                        Personal Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="form-floating">
                                {{ html()->input('name')->value('')->class('form-control border-primary-subtle shadow-sm')->attribute('wire:model', 'accounts.name')->placeholder('Full Name') }}
                                <label for="name" class="text-muted">
                                    <i class="fa fa-user me-1 text-primary"></i>
                                    Full Name <span class="text-danger">*</span>
                                </label>
                            </div>
                            <div class="form-text ms-1">
                                <i class="fa fa-info-circle me-1 text-primary"></i>
                                Enter customer's full name as it appears on official documents
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" wire:ignore>
                                <label for="customer_type_id" class="form-label mb-2 fw-medium d-flex align-items-center">
                                    <i class="fa fa-tag me-1 text-primary"></i>
                                    <span>Customer Type</span> <span class="text-danger">*</span>
                                </label>
                                <div class="input-group shadow-sm">
                                    {{ html()->select('customer_type_id', $customerTypes ?? [])->value(old('customer_type_id', $accounts['customer_type_id'] ?? ''))->class('tomSelect border-primary-subtle')->id('modal_customer_type_id')->placeholder('Select customer type')->attribute('wire:model.live', 'accounts.customer_type_id')->attribute('style', 'width:100%') }}
                                </div>
                                @error('accounts.customer_type_id')
                                    <div class="text-danger small mt-1">
                                        <i class="fa fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 fw-semibold d-flex align-items-center">
                        <i class="fa fa-address-card me-2 text-primary"></i>
                        Contact Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                {{ html()->input('mobile')->value('')->class('form-control border-primary-subtle shadow-sm')->attribute('wire:model.live', 'accounts.mobile')->placeholder('Mobile Number') }}
                                <label for="mobile" class="text-muted">
                                    <i class="fa fa-mobile me-1 text-primary"></i>
                                    Mobile <span class="text-danger">*</span>
                                </label>
                            </div>
                            <div class="form-text ms-1">
                                <i class="fa fa-info-circle me-1"></i>
                                Include country code for international numbers
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                {{ html()->input('whatsapp_mobile')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model.live', 'accounts.whatsapp_mobile')->placeholder('WhatsApp Number') }}
                                <label for="whatsapp_mobile" class="text-muted">
                                    <i class="fa fa-whatsapp me-1 text-success"></i>
                                    WhatsApp Number
                                </label>
                            </div>
                            <div class="form-text ms-1">
                                <i class="fa fa-info-circle me-1 text-success"></i>
                                Use international format (e.g., +1234567890)
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                {{ html()->email('email')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'accounts.email')->placeholder('Email Address') }}
                                <label for="email" class="text-muted">
                                    <i class="fa fa-envelope me-1 text-primary"></i>
                                    Email Address
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                {{ html()->input('company')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'accounts.company')->placeholder('Company') }}
                                <label for="company" class="text-muted">
                                    <i class="fa fa-building me-1 text-primary"></i>
                                    Company
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 fw-semibold d-flex align-items-center">
                        <i class="fa fa-id-card me-2 text-primary"></i>
                        Additional Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-floating">
                                {{ html()->date('dob')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model', 'accounts.dob')->placeholder('Date of Birth') }}
                                <label for="dob" class="text-muted">
                                    <i class="fa fa-birthday-cake me-1 text-primary"></i>
                                    Date of Birth
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                {{ html()->input('id_no')->value('')->class('form-control border-secondary-subtle shadow-sm')->attribute('wire:model.live', 'accounts.id_no')->placeholder('ID Number') }}
                                <label for="id_no" class="text-muted">
                                    <i class="fa fa-id-card-o me-1 text-primary"></i>
                                    ID Number
                                </label>
                            </div>
                            <div class="form-text ms-1">
                                <i class="fa fa-info-circle me-1"></i>
                                National ID, passport, or other identifier
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" wire:ignore>
                                <label for="nationality" class="form-label mb-2 fw-medium d-flex align-items-center">
                                    <i class="fa fa-globe me-1 text-primary"></i>
                                    <span>Nationality</span>
                                </label>
                                <div class="input-group shadow-sm">
                                    {{ html()->select('nationality', $countries)->value('')->class('tomSelect border-secondary-subtle')->id('modal_nationality')->placeholder('Select nationality')->attribute('wire:model.live', 'accounts.nationality')->attribute('style', 'width:100%') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light pt-4">
                @if ($this->getErrorBag()->count())
                    <div class="alert alert-danger p-3 border-start border-danger border-4 shadow-sm mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa fa-exclamation-triangle me-2 fs-4 text-danger"></i>
                            <strong>Please correct the following errors:</strong>
                        </div>
                        <ul class="list-unstyled mb-0 ms-4">
                            @foreach ($this->getErrorBag()->toArray() as $field => $errors)
                                <li><i class="fa fa-times-circle me-1 text-danger"></i> {{ $errors[0] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <button type="button" class="btn btn-outline-secondary px-4 py-2 d-flex align-items-center" data-bs-dismiss="modal">
                    <i class="fa fa-times me-2"></i>Cancel
                </button>
                <button type="button" wire:click="save(1)" class="btn btn-success px-4 py-2 d-flex align-items-center">
                    <i class="fa fa-save me-2"></i>Save & Add New
                </button>
                <button type="submit" class="btn btn-primary px-4 py-2 d-flex align-items-center">
                    <i class="fa fa-check me-2"></i>Save Customer
                </button>
            </div>

            @if (count($existingCustomers))
                <div class="mt-4 p-4 bg-light border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-primary mb-0 d-flex align-items-center">
                            <i class="fa fa-exclamation-circle me-2"></i>
                            Similar Customers Found
                        </h5>
                    </div>
                    <div class="alert alert-warning p-2 mb-3 d-flex align-items-center">
                        <i class="fa fa-info-circle me-2 fs-4"></i>
                        <span>These customers have similar details. Click on a customer to select them instead of creating a duplicate.</span>
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
                                        <td class="py-3">
                                            <i class="fa fa-phone me-1 text-success"></i>{{ $item['mobile'] }}
                                        </td>
                                        <td class="py-3">
                                            <i class="fa fa-envelope-o me-1 text-primary"></i>{{ $item['email'] ?: '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 text-muted small d-flex align-items-center">
                        <i class="fa fa-mouse-pointer me-1"></i>
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
