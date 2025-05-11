<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Customer</h1>
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
            <div class="row mb-2">
                <div class="col-md-8">
                    <div class="form-group">
                        <b><label for="name" class="text-capitalize">name</label></b>
                        {{ html()->input('name')->value('')->class('form-control')->attribute('wire:model', 'accounts.name') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group" wire:ignore>
                        <b><label for="customer_type_id" class="text-capitalize">Customer Type</label></b>
                        {{ html()->select('customer_type_id', [])->value('')->class('select-customer_type-id')->id('modal_customer_type_id')->attribute('wire:model', 'accounts.customer_type_id') }}
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="mobile" class="text-capitalize">mobile</label></b>
                        {{ html()->input('mobile')->value('')->class('form-control')->attribute('wire:model.live', 'accounts.mobile') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="whatsapp_mobile" class="text-capitalize">whatsapp mobile</label></b>
                        {{ html()->input('whatsapp_mobile')->value('')->class('form-control')->attribute('wire:model.live', 'accounts.whatsapp_mobile') }}
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4">
                    <div class="form-group">
                        <b><label for="email" class="text-capitalize">email</label></b>
                        {{ html()->email('email')->value('')->class('form-control')->attribute('wire:model', 'accounts.email') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <b><label for="dob" class="text-capitalize">DOB</label></b>
                        {{ html()->date('dob')->value('')->class('form-control')->attribute('wire:model', 'accounts.dob') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <b><label for="company" class="text-capitalize">company</label></b>
                        {{ html()->input('company')->value('')->class('form-control')->attribute('wire:model', 'accounts.company') }}
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4">
                    <div class="form-group">
                        <b><label for="id_no" class="text-capitalize">ID no</label></b>
                        {{ html()->input('id_no')->value('')->class('form-control')->attribute('wire:model.live', 'accounts.id_no') }}
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group" wire:ignore>
                        <b><label for="nationality" class="text-capitalize">nationality</label></b>
                        {{ html()->select('nationality', $countries)->value('')->class('tomSelect')->id('modal_nationality')->placeholder('')->attribute('wire:model.live', 'accounts.nationality') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" wire:click="save(1)" class="btn btn-success">Save & Add New</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
        @if (count($existingCustomers))
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <h3>Existing Customer</h3>
                <table class="table table-striped align-middle table-sm">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($existingCustomers as $item)
                            <tr wire:click="selectCustomer('{{ $item->id }}')" class="pointer">
                                <td>{{ $item['name'] }}</td>
                                <td>{{ $item['mobile'] }}</td>
                                <td>{{ $item['email'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
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
