<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5" id="CategoryModalLabel">Category Modal</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    @if ($this->getErrorBag()->count())
                        <ol>
                            <?php foreach ($this->getErrorBag()->toArray() as $key => $value): ?>
                            <li style="color:red">* {{ $value[0] }}</li>
                            <?php endforeach; ?>
                        </ol>
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group" wire:ignore>
                        <h4> <label for="parent_id">Parent</label> </h4>
                        {{ html()->select('parent_id', [])->value('')->class('select-category_id-parent')->placeholder('Please Select Parent If any')->id('modal_parent_id') }}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <h4> <label for="name">Name</label> </h4>
                        {{ html()->input('name')->value('')->class('form-control')->attribute('wire:model', 'categories.name') }}
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
                $('#modal_parent_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('categories.parent_id', value);
                });
                window.addEventListener('SelectDropDownValues', event => {
                    @this.set('categories.parent_id', @this.categories['parent_id']);
                    var tomSelectInstance = document.querySelector('#modal_parent_id').tomselect;
                    if (@this.categories['parent_id']) {
                        preselectedData = {
                            id: @this.categories['parent_id'],
                            name: @this.categories['parent']['name'],
                        };
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
