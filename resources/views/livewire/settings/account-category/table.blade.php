<div>
    <div class="card-header -4 mb-3">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('account category.create')
                    <button class="btn btn-primary hstack gap-2 align-self-center" id="AccountCategoryAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </button>
                @endcan
                <div class="btn-group">
                    @can('account category.delete')
                        <button class="btn btn-icon btn-outline-light" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
            <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                <div class="form-group">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" wire:model.live="search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
            </div>
        </div>
    </div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-4" wire:ignore>
                <h4> <label for="parent_id">Parent</label> </h4>
                {{ html()->select('parent_id', [])->value('')->class('select-account_category_id-list')->id('parent_id')->placeholder('All') }}
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th width="20%">
                            <input type="checkbox" wire:model.live="selectAll" />
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="parent_id" label="parent" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="name" /> </th>
                        <th width="10%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" />
                                {{ $item->id }}
                            </td>
                            <td>{{ $item->parent?->name }}</td>
                            <td>{{ $item->name }}</td>
                            <td>
                                @can('account category.edit')
                                    <i table_id="{{ $item->id }}" class="demo-psi-pencil fs-5 me-2 pointer edit"></i>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $data->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("AccountCategory-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                $('#AccountCategoryAdd').click(function() {
                    Livewire.dispatch("AccountCategory-Page-Create-Component");
                });
                window.addEventListener('RefreshAccountCategoryTable', event => {
                    Livewire.dispatch("AccountCategory-Refresh-Component");
                });
                $('#parent_id').on('change', function(e) {
                    @this.set('parent_id', $(this).val());
                });
            });
        </script>
    @endpush
</div>

