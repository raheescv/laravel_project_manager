<div>
    <div class="modal-header bg-primary text-white py-2">
        <h6 class="modal-title d-flex align-items-center mb-0 text-white">
            <i class="fa fa-user-plus me-2"></i> Customer Information
            @if (isset($accounts['id']))
                <span class="badge bg-white text-primary ms-2 fw-normal">ID: {{ $accounts['id'] }}</span>
            @endif
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body p-3">

            {{-- Personal Details --}}
            <div class="mb-3">
                <h6 class="text-secondary fw-semibold border-bottom pb-2 mb-3">
                    <i class="fa fa-user-circle me-1"></i> Personal Details
                </h6>
                <div class="row g-2">
                    <div class="col-md-8">
                        <div class="form-floating">
                            {{ html()->input('name')->value('')->class('form-control form-control-sm')->attribute('wire:model', 'accounts.name')->placeholder('Full Name') }}
                            <label><i class="fa fa-user me-1 text-muted"></i> Full Name <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div wire:ignore>
                            <label class="form-label small mb-1 text-secondary">
                                <i class="fa fa-tag me-1"></i> Customer Type <span class="text-danger">*</span>
                            </label>
                            {{ html()->select('customer_type_id', $customerTypes ?? [])->value(old('customer_type_id', $accounts['customer_type_id'] ?? ''))->class('tomSelect')->id('modal_customer_type_id')->placeholder('Select customer type')->attribute('wire:model.live', 'accounts.customer_type_id')->attribute('style', 'width:100%') }}
                            @error('accounts.customer_type_id')
                                <div class="text-danger small mt-1"><i class="fa fa-exclamation-circle me-1"></i>{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contact Information --}}
            <div class="mb-3">
                <h6 class="text-secondary fw-semibold border-bottom pb-2 mb-3">
                    <i class="fa fa-address-card me-1"></i> Contact Information
                </h6>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <div class="form-floating">
                            {{ html()->input('mobile')->value('')->class('form-control form-control-sm')->attribute('wire:model.live', 'accounts.mobile')->placeholder('Mobile') }}
                            <label><i class="fa fa-mobile me-1 text-muted"></i> Mobile <span class="text-danger">*</span></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            {{ html()->input('whatsapp_mobile')->value('')->class('form-control form-control-sm')->attribute('wire:model.live', 'accounts.whatsapp_mobile')->placeholder('WhatsApp') }}
                            <label><i class="fa fa-whatsapp me-1 text-success"></i> WhatsApp Number</label>
                        </div>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="form-floating">
                            {{ html()->email('email')->value('')->class('form-control form-control-sm')->attribute('wire:model', 'accounts.email')->placeholder('Email') }}
                            <label><i class="fa fa-envelope me-1 text-muted"></i> Email Address</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            {{ html()->input('company')->value('')->class('form-control form-control-sm')->attribute('wire:model', 'accounts.company')->placeholder('Company') }}
                            <label><i class="fa fa-building me-1 text-muted"></i> Company</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Additional Details --}}
            <div class="mb-3">
                <h6 class="text-secondary fw-semibold border-bottom pb-2 mb-3">
                    <i class="fa fa-id-card me-1"></i> Additional Details
                </h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <div class="form-floating">
                            {{ html()->date('dob')->value('')->class('form-control form-control-sm')->attribute('wire:model', 'accounts.dob')->placeholder('DOB') }}
                            <label><i class="fa fa-birthday-cake me-1 text-muted"></i> Date of Birth</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            {{ html()->input('id_no')->value('')->class('form-control form-control-sm')->attribute('wire:model.live', 'accounts.id_no')->placeholder('ID Number') }}
                            <label><i class="fa fa-id-card-o me-1 text-muted"></i> ID Number</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div wire:ignore>
                            <label class="form-label small mb-1 text-secondary">
                                <i class="fa fa-globe me-1"></i> Nationality
                            </label>
                            {{ html()->select('nationality', $countries)->value('')->class('tomSelect')->id('modal_nationality')->placeholder('Select nationality')->attribute('wire:model.live', 'accounts.nationality')->attribute('style', 'width:100%') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Credit Information --}}
            <div class="mb-2">
                <h6 class="text-secondary fw-semibold border-bottom pb-2 mb-3">
                    <i class="fa fa-credit-card me-1"></i> Credit Information
                </h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <div class="form-floating">
                            {{ html()->number('credit_period_days')->value('')->class('form-control form-control-sm')->attribute('wire:model', 'accounts.credit_period_days')->placeholder('Credit Period')->attribute('min', '0')->attribute('step', '1') }}
                            <label><i class="fa fa-calendar me-1 text-muted"></i> Credit Period (Days)</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Validation Errors --}}
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger py-2 px-3 mb-0 mt-3">
                    <strong class="small"><i class="fa fa-exclamation-triangle me-1"></i> Please correct the following:</strong>
                    <ul class="mb-0 small ps-3 mt-1">
                        @foreach ($this->getErrorBag()->toArray() as $field => $errors)
                            <li>{{ $errors[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Similar Customers --}}
            @if (count($existingCustomers))
                <div class="mt-3 border rounded p-3 bg-light">
                    <h6 class="text-warning fw-semibold mb-2">
                        <i class="fa fa-exclamation-circle me-1"></i> Similar Customers Found
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="small">Name</th>
                                    <th class="small">Mobile</th>
                                    <th class="small">Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($existingCustomers as $item)
                                    <tr wire:click="selectCustomer('{{ $item->id }}')" style="cursor:pointer;">
                                        <td class="small"><i class="fa fa-user-circle me-1 text-muted"></i> {{ $item['name'] }}</td>
                                        <td class="small"><i class="fa fa-phone me-1 text-muted"></i> {{ $item['mobile'] }}</td>
                                        <td class="small"><i class="fa fa-envelope-o me-1 text-muted"></i> {{ $item['email'] ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="modal-footer py-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                <i class="fa fa-times me-1"></i> Cancel
            </button>
            <button type="button" wire:click="save(1)" class="btn btn-sm btn-success">
                <i class="fa fa-save me-1"></i> Save & Add New
            </button>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fa fa-check me-1"></i> Save Customer
            </button>
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
