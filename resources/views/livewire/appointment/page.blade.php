<div>
    <form wire:submit="save">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title text-white">
                <i class="demo-psi-calendar-4 me-2 "></i>
                {{ isset($appointments['id']) ? 'Edit' : 'New' }} Appointment
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0">
                        @foreach ($this->getErrorBag()->toArray() as $value)
                            <li>{{ $value[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row g-3 mb-4">
                <div class="col-md-4" wire:ignore>
                    <div class="form-group">
                        <label for="account_id" class="form-label text-md fw-semibold">Customer <span class="text-danger">*</span></label>
                        {{ html()->select('account_id', [])->value('')->class('select-customer_id')->id('account_id')->placeholder('Select Customer') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="start_time" class="form-label text-md fw-semibold">Start Time <span class="text-danger">*</span></label>
                        {{ html()->datetime('start_time')->value('')->class('form-control shadow-none')->attribute('wire:model.live', 'appointments.start_time') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="end_time" class="form-label text-md fw-semibold">End Time <span class="text-danger">*</span></label>
                        {{ html()->datetime('end_time')->value('')->class('form-control shadow-none')->attribute('wire:model.live', 'appointments.end_time') }}
                    </div>
                </div>
            </div>

            <div class="card border mb-4">
                <div class="card-header bg-light py-2">
                    <h6 class="card-title mb-0">Services & Staff</h6>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-bordered table-striped mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th width='30%'>Employee</th>
                                <th width='60%'>Service</th>
                                <th class="text-center">Action</th>
                            </tr>
                            <tr>
                                <th wire:ignore>
                                    {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list shadow-none')->id('modal_employee_id')->placeholder('Select Employee') }}
                                </th>
                                <th wire:ignore>
                                    {{ html()->select('service_id', [])->value('')->class('select-product_id-list shadow-none')->attribute('type', 'service')->id('modal_service_id')->placeholder('Select Service') }}
                                </th>
                                <th class="text-center">
                                    <button type="button" class="btn btn-sm btn-primary" wire:click="addItem">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $key => $value)
                                <tr>
                                    <td class="align-middle">{{ $value['employee'] }}</td>
                                    <td class="align-middle">{{ $value['service'] }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger" wire:click="removeItem('{{ $key }}')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-group">
                <label for="notes" class="form-label text-md fw-semibold">Notes</label>
                {{ html()->textarea('notes', [])->value('')->class('form-control shadow-none')->rows(3)->attribute('wire:model.live', 'appointments.notes')->placeholder('Enter any additional notes...') }}
            </div>
        </div>
        <div class="modal-footer bg-light">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="button" wire:click="save(1)" class="btn btn-success">Save & Add New</button>
            <button type="submit" class="btn btn-primary">Save Appointment</button>
        </div>
    </form>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('appointments.account_id', value);
                    document.querySelector('#modal_employee_id').tomselect.open();
                });
                $('#modal_service_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('item.service_id', value);
                });
                $('#modal_employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('item.employee_id', value);
                    document.querySelector('#modal_service_id').tomselect.open();
                });
                window.addEventListener('SelectDropDownValues', event => {
                    var data = event.detail[0];
                    @this.set('appointments.account_id', data.account_id);

                    var tomSelectInstance = document.querySelector('#account_id').tomselect;
                    if (data.account_id) {
                        preselectedData = {
                            id: data.account_id,
                            name: data.account_name,
                        };
                        tomSelectInstance.addOption(preselectedData);
                        tomSelectInstance.addItem(preselectedData.id);
                    } else {
                        tomSelectInstance.clear();
                    }
                    var tomSelectInstance = document.querySelector('#modal_service_id').tomselect;
                    tomSelectInstance.clear();
                    var tomSelectInstanceEmployee = document.querySelector('#modal_employee_id').tomselect;
                    tomSelectInstanceEmployee.clear();
                });
            });
        </script>
    @endpush
</div>
