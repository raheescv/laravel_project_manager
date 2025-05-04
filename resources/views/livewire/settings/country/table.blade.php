<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('country.create')
                    <button class="btn btn-primary hstack gap-2 align-self-center" id="CountryAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </button>
                @endcan
                <div class="btn-group">
                    @can('country.delete')
                        <button class="btn btn-icon btn-outline-light" wire:click="delete()" wire:confirm="Are you sure?">
                            <i class="demo-pli-recycling fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
            <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                <div class="form-group">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" wire:model.live="search" class="form-control" placeholder="Search...">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" wire:model.live="selectAll">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                        </th>
                        <th width="30%">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Name" />
                        </th>
                        <th width="30%">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="code" label="Code" />
                        </th>
                        <th width="10%">Phone Code</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected">
                                {{ $item->id }}
                            </td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->code }}</td>
                            <td>{{ $item->phone_code }}</td>
                            <td>{!! $item->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}</td>
                            <td class="text-end">
                                @can('country.edit')
                                    <i class="demo-psi-pencil fs-5 pointer edit" table_id="{{ $item->id }}"></i>
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
                $('#CountryAdd').click(function() {
                    Livewire.dispatch('Country-Page-Create-Component');
                });

                $(document).on('click', '.edit', function() {
                    Livewire.dispatch('Country-Page-Update-Component', {
                        id: $(this).attr('table_id')
                    });
                });

                window.addEventListener('RefreshCountryTable', event => {
                    Livewire.dispatch('Country-Refresh-Component');
                });
            });
        </script>
    @endpush
</div>
