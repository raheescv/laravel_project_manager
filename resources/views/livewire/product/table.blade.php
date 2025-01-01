<div>
    <div class="card-header -4 mb-3">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <a class="btn btn-primary hstack gap-2 align-self-center" href="{{ route('product::create') }}">
                    <i class="demo-psi-add fs-5"></i>
                    <span class="vr"></span>
                    Add New
                </a>
                <div class="btn-group">
                    <button class="btn btn-icon btn-outline-light" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                    <button class="btn btn-icon btn-outline-light" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?"><i
                            class="demo-pli-recycling fs-5"></i></button>
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
                    <button class="btn btn-icon btn-outline-light" data-bs-toggle="modal" data-bs-target="#ProductImportModal">
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
            <div class="col-md-3" wire:ignore>
                <h4> <label for="department_id">Department</label> </h4>
                {{ html()->select('department_id', [])->value('')->class('select-department_id-list')->id('department_id')->placeholder('All') }}
            </div>
            <div class="col-md-3" wire:ignore>
                <h4> <label for="main_category_id">Main Category</label> </h4>
                {{ html()->select('main_category_id', [])->value('')->class('select-category_id-list')->id('main_category_id')->placeholder('All') }}
            </div>
            <div class="col-md-3" wire:ignore>
                <h4> <label for="sub_category_id">Sub Category</label> </h4>
                {{ html()->select('sub_category_id', [])->value('')->class('select-category_id-list')->id('sub_category_id')->placeholder('All') }}
            </div>
            <div class="col-md-3" wire:ignore>
                <h4> <label for="unit_id">Unit</label> </h4>
                {{ html()->select('unit_id', [])->value('')->class('select-unit_id-list')->id('unit_id')->placeholder('All') }}
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th>
                            <input type="checkbox" wire:model.live="selectAll" />
                            <a href="#" wire:click.prevent="sortBy('id')">
                                #
                                @if ($sortField === 'id') <span>
                                        @if ($sortDirection === 'asc')
                                            &uarr;
                                        @else
                                            &darr;
                                        @endif
                                    </span> @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('department_id')">
                                Department
                                @if ($sortField === 'department_id')
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
                            <a href="#" wire:click.prevent="sortBy('main_category_id')">
                                Main Category
                                @if ($sortField === 'main_category_id')
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
                            <a href="#" wire:click.prevent="sortBy('sub_category_id')">
                                Sub Category
                                @if ($sortField === 'sub_category_id')
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
                            <a href="#" wire:click.prevent="sortBy('unit_id')">
                                Unit
                                @if ($sortField === 'unit_id')
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
                            <a href="#" wire:click.prevent="sortBy('code')">
                                Code
                                @if ($sortField === 'code')
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
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('name_arabic')">
                                Name Arabic
                                @if ($sortField === 'name_arabic')
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
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('cost')">
                                cost
                                @if ($sortField === 'cost')
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
                        <th class="text-end">
                            <a href="#" wire:click.prevent="sortBy('mrp')">
                                MRP
                                @if ($sortField === 'mrp')
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
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" />
                                {{ $item->id }}
                            </td>
                            <td>{{ $item->department?->name }}</td>
                            <td>{{ $item->mainCategory?->name }}</td>
                            <td>{{ $item->subCategory?->name }}</td>
                            <td>{{ $item->unit?->name }}</td>
                            <td>{{ $item->code }}</td>
                            <td>{{ $item->name }}</td>
                            <td dir="rtl">{{ $item->name_arabic }}</td>
                            <td class="text-end">{{ currency($item->cost) }}</td>
                            <td class="text-end">{{ currency($item->mrp) }}</td>
                            <td class="text-end"> <a href="{{ route('product::edit', $item->id) }}"><i class="demo-psi-pencil fs-5 me-2 pointer"></i> </a> </td>
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
                $('#unit_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('unit_id', value);
                });
                $('#department_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('department_id', value);
                });
                $('#main_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('main_category_id', value);
                });
                $('#sub_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('sub_category_id', value);
                });
            });
        </script>
    @endpush
</div>
