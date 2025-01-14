<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('service.create')
                    <a class="btn btn-primary hstack gap-2 align-self-center" href="{{ route('service::create') }}">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </a>
                @endcan
                <div class="btn-group">
                    @can('service.export')
                        <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button>
                    @endcan
                    @can('service.delete')
                        <button class="btn btn-icon btn-outline-light" title="To delete the selected items" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
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
                <div class="btn-group">
                    @can('service.import')
                        <button class="btn btn-icon btn-outline-light" data-bs-toggle="modal" data-bs-target="#ServiceImportModal">
                            <i class="demo-pli-download-from-cloud fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
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
                <div class="col-md-3">
                    <label for="status" class="form-label">Status *</label>
                    {{ html()->select('status', activeOrDisabled())->value('')->class('form-control')->placeholder('Select Status')->id('status')->attribute('wire:model.live', 'status') }}
                </div>
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
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                        </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="department_id" label="Department" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="main_category_id" label="Main Category" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sub_category_id" label="Sub Category" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="code" label="Code" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="name" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name_arabic" label="name arabic" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="mrp" label="mrp" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="time" label="time" /> </th>
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
                            <td>{{ $item->code }}</td>
                            <td>{{ $item->name }}</td>
                            <td dir="rtl">{{ $item->name_arabic }}</td>
                            <td class="text-end">{{ currency($item->mrp) }}</td>
                            <td class="text-end">{{ $item->time }}</td>
                            <td class="text-end">
                                @can('service.edit')
                                    <a href="{{ route('service::edit', $item->id) }}"><i class="demo-psi-pencil fs-5 me-2 pointer"></i> </a>
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
