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
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group" wire:ignore>
                        <h4> <label for="account_type">Account Type</label> </h4>
                        {{ html()->select('account_type', accountTypes())->value('')->class('tomSelect')->placeholder('Please Select Account Type')->id('modal_account_type') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <h4> <label for="name">Name</label> </h4>
                        {{ html()->input('name')->value('')->class('form-control')->attribute('wire:model', 'accounts.name') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <h4> <label for="description">Description</label> </h4>
                        {{ html()->input('description')->value('')->class('form-control')->attribute('wire:model', 'accounts.description') }}
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
                $('#modal_account_type').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('accounts.account_type', value);
                });
                window.addEventListener('SelectDropDownValues', event => {
                    var accounts = event.detail[0];
                    console.log(accounts);
                    var tomSelectInstance = document.querySelector('#modal_account_type').tomselect;
                    @this.set('accounts.account_type', accounts['account_type']);
                    if (accounts['account_type']) {
                        tomSelectInstance.addItem(accounts['account_type']);
                    } else {
                        tomSelectInstance.clear();
                    }
                });
            });
        </script>
    @endpush
</div>
