<div>
    <div class="modal-header border-bottom-0 pb-0">
        <h1 class="modal-title fs-5 fw-semibold d-flex align-items-center">
            <i class="fa fa-wallet me-2 text-primary"></i>
            {{ $table_id ? 'Edit Account' : 'Create Account' }}
        </h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body pt-3">
            {{-- Error Messages --}}
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start mb-4" role="alert">
                    <i class="fa fa-exclamation-circle me-2 mt-1"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block mb-2">Please fix the following errors:</strong>
                        <ul class="mb-0 ps-3">
                            @foreach ($this->getErrorBag()->toArray() as $value)
                                <li>{{ $value[0] }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Account Type and Category Fields --}}
            <div class="d-flex flex-wrap gap-3 mb-4">
                {{-- Account Type Field --}}
                @if (!$type_selection_freeze)
                    <div class="flex-fill" style="min-width: 200px;">
                        <label for="account_type" class="form-label fw-semibold mb-2">
                            <i class="fa fa-tag me-1 text-muted"></i>
                            Account Type <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa fa-list text-muted"></i>
                            </span>
                            {{ html()->select('account_type', accountTypes())->value('')->class('form-control border-start-0')->attribute('wire:model', 'accounts.account_type')->placeholder('Please Select Account Type')->id('modal_account_type') }}
                        </div>
                        @error('accounts.account_type')
                            <div class="text-danger small mt-1">
                                <i class="fa fa-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>
                @endif

                {{-- Account Category Field --}}
                <div class="flex-fill" style="min-width: 200px;">
                    <label for="account_category_id" class="form-label fw-semibold mb-2">
                        <i class="fa fa-folder me-1 text-muted"></i>
                        Account Category
                    </label>
                    <div class="input-group" wire:ignore>
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-folder-open text-muted"></i>
                        </span>
                        {{ html()->select('account_category_id', $accountCategories ?? [])->value(old('account_category_id', $accounts['account_category_id'] ?? ''))->class('select-account_category_id form-control border-start-0')->id('modal_account_category_id')->placeholder('Select account category')->attribute('wire:model.live', 'accounts.account_category_id') }}
                    </div>
                </div>
            </div>

            <div class="row g-3">
                {{-- Name Field --}}
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold mb-2">
                            <i class="fa fa-user me-1 text-muted"></i>
                            Name <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa fa-building text-muted"></i>
                            </span>
                            {{ html()->input('name')->value('')->class('form-control border-start-0')->attribute('wire:model', 'accounts.name')->placeholder('Enter account name') }}
                        </div>
                        @error('accounts.name')
                            <div class="text-danger small mt-1">
                                <i class="fa fa-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                {{-- Alias Name Field --}}
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="alias_name" class="form-label fw-semibold mb-2">
                            <i class="fa fa-tag me-1 text-muted"></i>
                            Alias Name
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa fa-at text-muted"></i>
                            </span>
                            {{ html()->input('alias_name')->value('')->class('form-control border-start-0')->attribute('wire:model', 'accounts.alias_name')->placeholder('Enter alias name (optional)') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Opening Balance Fields --}}
            <div class="mb-4">
                <div class="card border-0 bg-light-subtle shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fa fa-balance-scale me-2 text-primary fs-5"></i>
                            <h6 class="mb-0 fw-semibold text-muted">Opening Balance</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="opening_debit" class="form-label fw-semibold mb-2">
                                    <i class="fa fa-arrow-up me-1 text-success"></i>
                                    Opening Debit
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fa fa-money-bill-wave text-success"></i>
                                    </span>
                                    {{ html()->input('opening_debit')->type('number')->value('')->class('form-control border-start-0')->attribute('wire:model', 'accounts.opening_debit')->placeholder('0.00')->id('opening_debit') }}
                                </div>
                                @error('accounts.opening_debit')
                                    <div class="text-danger small mt-1">
                                        <i class="fa fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="opening_credit" class="form-label fw-semibold mb-2">
                                    <i class="fa fa-arrow-down me-1 text-danger"></i>
                                    Opening Credit
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fa fa-money-bill-wave text-danger"></i>
                                    </span>
                                    {{ html()->input('opening_credit')->type('number')->value('')->class('form-control border-start-0')->attribute('wire:model', 'accounts.opening_credit')->placeholder('0.00')->id('opening_credit') }}
                                </div>
                                @error('accounts.opening_credit')
                                    <div class="text-danger small mt-1">
                                        <i class="fa fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fa fa-info-circle me-1"></i>
                                Enter the initial balance when creating this account. Leave blank if not applicable.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Description Field --}}
            <div class="mb-3">
                <label for="description" class="form-label fw-semibold mb-2">
                    <i class="fa fa-align-left me-1 text-muted"></i>
                    Description
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 align-items-start pt-2">
                        <i class="fa fa-file-text text-muted"></i>
                    </span>
                    {{ html()->textarea('description')->value('')->class('form-control border-start-0')->attribute('wire:model', 'accounts.description')->attribute('rows', '3')->placeholder('Enter account description (optional)') }}
                </div>
            </div>
        </div>
        <div class="modal-footer border-top bg-light pt-3 pb-3">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                <i class="fa fa-times me-1"></i>Close
            </button>
            <button type="button" wire:click="save(1)" class="btn btn-success">
                <i class="fa fa-save me-1"></i>Save & Add New
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-check me-1"></i>Save
            </button>
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
