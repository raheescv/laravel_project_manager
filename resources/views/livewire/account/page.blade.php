<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Account Modal</h1>
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
            @if (!$type_selection_freeze)
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <div class="form-group">
                            <b><label for="account_type" class="text-capitalize">Account Type *</label></b>
                            {{ html()->select('account_type', accountTypes())->value('')->class('form-control')->attribute('wire:model', 'accounts.account_type')->placeholder('Please Select Account Type')->id('modal_account_type') }}
                        </div>
                    </div>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12 mb-2">
                    <div class="form-group">
                        <b><label for="name" class="text-capitalize">Name *</label></b>
                        {{ html()->input('name')->value('')->class('form-control')->attribute('wire:model', 'accounts.name') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <b><label for="description" class="text-capitalize">Description</label></b>
                        {{ html()->textarea('description')->value('')->class('form-control')->attribute('wire:model', 'accounts.description') }}
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
                window.addEventListener('AddToAccountSelectBox', event => {
                    var data = event.detail[0];
                    console.log(data);
                    var tomSelectInstance = document.querySelector('.select-account_id').tomselect;
                    if (data['name']) {
                        preselectedData = {
                            id: data['id'],
                            name: data['name'],
                        };
                        tomSelectInstance.addOption(preselectedData);
                    }
                    tomSelectInstance.addItem(data['id']);
                });
            });
        </script>
    @endpush
</div>
