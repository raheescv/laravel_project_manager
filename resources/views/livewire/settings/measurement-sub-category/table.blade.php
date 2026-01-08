<div>
    <div class="card-header mb-3">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">

                @can('category.create')
                    <button class="btn btn-primary hstack gap-2" id="MeasurementCategoryAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </button>
                @endcan

                @can('category.delete')
                    <button class="btn btn-icon btn-outline-light"
                            wire:click="delete"
                            wire:confirm="Are you sure you want to delete the selected items?">
                        <i class="demo-pli-recycling fs-5"></i>
                    </button>
                @endcan
            </div>

            <div class="col-md-6 d-flex gap-1 justify-content-end mb-3">
                <select wire:model.live="limit" class="form-control w-auto">
                    <option value="10">10</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                </select>

                <input type="text"
                       wire:model.live="search"
                       class="form-control w-auto"
                       placeholder="Search..."
                       autocomplete="off">
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th width="5%">
                            <input type="checkbox" wire:model.live="selectAll">
                        </th>

                        <th>ID</th>
                         <th>Category Name</th>

                        <th>Name</th>

                        <th width="10%">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox"
                                       value="{{ $item->id }}"
                                       wire:model.live="selected">
                            </td>

                            <td>{{ $item->id }}</td>

                             <td>
               {{ $item->category?->name ?? '-' }}
            </td>

                            <td>{{ $item->name }}</td>

                            <td>
                                @can('category.edit')
                                    <i table_id="{{ $item->id }}"
                                       class="demo-psi-pencil fs-5 pointer edit"></i>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                No categories found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $data->links() }}
    </div>

    @push('scripts')
        <script>
            $(document).on('click', '.edit', function () {
                Livewire.dispatch(
                    'MeasurementCategory-Page-Update-Component',
                    { id: $(this).attr('table_id') }
                );
            });

            $('#MeasurementCategoryAdd').click(function () {
                Livewire.dispatch('MeasurementCategory-Page-Create-Component');
            });

            window.addEventListener('RefreshMeasurementCategoryTable', () => {
                Livewire.dispatch('MeasurementCategory-Refresh-Component');
            });
        </script>
    @endpush
</div>
