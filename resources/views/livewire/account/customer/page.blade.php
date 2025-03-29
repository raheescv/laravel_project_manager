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
                <div class="col-md-12">
                    <div class="form-group">
                        <b><label for="name" class="text-capitalize">name</label></b>
                        {{ html()->input('name')->value('')->class('form-control')->attribute('wire:model', 'accounts.name') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="mobile" class="text-capitalize">mobile</label></b>
                        {{ html()->input('mobile')->value('')->class('form-control')->attribute('wire:model.live', 'accounts.mobile') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <b><label for="email" class="text-capitalize">email</label></b>
                        {{ html()->email('email')->value('')->class('form-control')->attribute('wire:model', 'accounts.email') }}
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
            $(document).ready(function() {});
        </script>
    @endpush
</div>
