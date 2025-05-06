<div>
    <form wire:submit="save">
        <div class="modal-header">
            <h5 class="modal-title">Appointment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
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
                    <label for="account_id" class="form-label">Customer</label>
                    {{ html()->select('account_id', [])->value('')->class('select-customer_id')->id('account_id')->placeholder('Please Select Customer') }}
                </div>
                <div class="col-md-4">
                    <label for="start_time" class="form-label">Start Time</label>
                    {{ html()->datetime('start_time')->value('')->class('form-control')->attribute('wire:model.live', 'appointments.start_time') }}
                </div>
                <div class="col-md-4">
                    <label for="end_time" class="form-label">End Time</label>
                    {{ html()->datetime('end_time')->value('')->class('form-control')->attribute('wire:model.live', 'appointments.end_time') }}
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr class="table-success">
                                <th width='45%'>Employee</th>
                                <th width='45%'>Service</th>
                                <th>Action</th>
                            </tr>
                            <tr class="table-success">
                                <th wire:ignore>
                                    {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list')->id('modal_employee_id')->placeholder('Please Select Employee') }}
                                </th>
                                <th wire:ignore>
                                    {{ html()->select('service_id', [])->value('')->class('select-product_id-list')->attribute('type', 'service')->id('modal_service_id')->placeholder('Please Select Service') }}
                                </th>
                                <th>
                                    <i class="fa fa-2x fa-plus" wire:click="addItem"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $key => $value)
                                <tr>
                                    <td>{{ $value['employee'] }}</td>
                                    <td>{{ $value['service'] }}</td>
                                    <td>
                                        <i class="fa fa-2x fa-trash" wire:click="removeItem('{{ $key }}')"></i>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                {{ html()->textarea('notes', [])->value('')->class('form-control')->attribute('wire:model.live', 'appointments.notes') }}
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
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('appointments.account_id', value);
                });
                $('#modal_service_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('item.service_id', value);
                });
                $('#modal_employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('item.employee_id', value);
                });
            });
        </script>
    @endpush
</div>
