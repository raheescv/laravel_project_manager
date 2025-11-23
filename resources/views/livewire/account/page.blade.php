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
                    <div class="form-group" wire:ignore>
                        <b><label for="account_category_id" class="text-capitalize">Account Category</label></b>
                        {{ html()->select('account_category_id', $accountCategories ?? [])->value(old('account_category_id', $accounts['account_category_id'] ?? ''))->class('select-account_category_id')->id('modal_account_category_id')->placeholder('Select account category')->attribute('wire:model.live', 'accounts.account_category_id') }}
                    </div>
                </div>
            </div>
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
                    if (data['id']) {
                        var preselectedData = {
                            id: data['id'],
                            name: data['name'],
                        };
                        document.querySelectorAll('.select-account_id').forEach(function(element) {
                            var tomSelectInstance = element.tomselect;
                            if (tomSelectInstance) {
                                tomSelectInstance.addOption(preselectedData);
                                tomSelectInstance.addItem(data['id']);
                            }
                        });
                    }
                });
                $('#modal_account_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('accounts.account_category_id', value);
                });
                window.addEventListener('SelectDropDownValues', event => {
                    var data = event.detail[0];
                    if (data && data.account_category_id) {
                        @this.set('accounts.account_category_id', data.account_category_id);
                        var accountCategoryTomSelectInstance = document.querySelector('#modal_account_category_id').tomselect;
                        if (accountCategoryTomSelectInstance && data.account_category) {
                            var preselectedData = {
                                id: data.account_category_id,
                                name: data.account_category['name'],
                            };
                            accountCategoryTomSelectInstance.addOption(preselectedData);
                            accountCategoryTomSelectInstance.addItem(preselectedData.id);
                        }
                    }
                });
            });
        </script>
    @endpush
</div>
