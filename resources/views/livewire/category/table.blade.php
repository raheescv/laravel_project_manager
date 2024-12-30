<div>
    <div class="card-header -4 mb-3">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <button class="btn btn-primary hstack gap-2 align-self-center" id="CategoryAdd">
                    <i class="demo-psi-add fs-5"></i>
                    <span class="vr"></span>
                    Add New
                </button>
                <div class="btn-group">
                    <button class="btn btn-icon btn-outline-light" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                    <button class="btn btn-icon btn-outline-light" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?"><i class="demo-pli-recycling fs-5"></i>
                    </button>
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
                    <input type="text" wire:model.live="search" placeholder="Search..." class="form-control" autocomplete="off">
                </div>
                <div class="btn-group">
                    <button class="btn btn-icon btn-outline-light" data-bs-toggle="modal" data-bs-target="#CategoryImportModal">
                        <i class="demo-pli-download-from-cloud fs-5"></i>
                    </button>
                    <button class="btn btn-icon btn-outline-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">Action</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">
            @if ($exportLink)
                <div class="mt-3">
                    <a href="{{ $exportLink }}" target="_blank" class="btn btn-success">Download Export {{ $exportLink }}</a>
                </div>
            @endif
        </div>
    </div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-4" wire:ignore>
                <h4> <label for="parent_id">Parent</label> </h4>
                {{ html()->select('parent_id', [])->value('')->class('select2-category_id-list')->id('parent_id')->placeholder('All') }}
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
                            <a href="#" wire:click.prevent="sortBy('id')">
                                ID
                                @if ($sortField === 'id')
                                    <span>
                                        @if ($sortDirection === 'asc')
                                            &uarr;
                                        @else
                                            &darr;
                                        @endif
                                    </span>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('parent_id')">
                                Parent
                                @if ($sortField === 'parent_id')
                                    <span>
                                        @if ($sortDirection === 'asc')
                                            &uarr;
                                        @else
                                            &darr;
                                        @endif
                                    </span>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('name')">
                                Name
                                @if ($sortField === 'name')
                                    <span>
                                        @if ($sortDirection === 'asc')
                                            &uarr;
                                        @else
                                            &darr;
                                        @endif
                                    </span>
                                @endif
                            </a>
                        </th>
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
                            <td> <i table_id="{{ $item->id }}" class="demo-psi-pencil fs-5 me-2 pointer edit"></i> </td>
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
                    Livewire.dispatch("Category-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                $('#CategoryAdd').click(function() {
                    Livewire.dispatch("Category-Page-Create-Component");
                });
                window.addEventListener('RefreshCategoryTable', event => {
                    Livewire.dispatch("Category-Refresh-Component");
                });
                $('#parent_id').on('change', function(e) {
                    @this.set('parent_id', $(this).val());
                });
            });
        </script>
    @endpush
</div>
