<div>
    <div class="card-header -4 mb-3">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('category.create')
                    <button class="btn btn-primary hstack gap-2 align-self-center" id="MeasurementCategoryAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </button>
                @endcan

                <div class="btn-group">
                    <!-- @can('category.export')
                        <button class="btn btn-icon btn-outline-light" wire:click="export()">
                            <i class="demo-pli-file-excel fs-5"></i>
                        </button>
                    @endcan -->

                   
                    @can('category.delete')
                        <button class="btn btn-icon btn-outline-light"
                                wire:click="delete()"
                                wire:confirm="Are you sure you want to delete the selected items?">
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
                    <input type="text"
                           wire:model.live="search"
                           autofocus
                           placeholder="Search..."
                           class="form-control"
                           autocomplete="off">
                </div>

                <div class="btn-group">
                    @can('category.import')
                        <button class="btn btn-icon btn-outline-light"
                                data-bs-toggle="modal"
                                data-bs-target="#MeasurementCategoryImportModal">
                            <i class="demo-pli-download-from-cloud fs-5"></i>
                        </button>
                    @endcan
                </div>
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
                            <x-sortable-header
                                :direction="$sortDirection"
                                :sortField="$sortField"
                                field="id"
                                label="ID" />
                        </th>

                        <th>
                            <x-sortable-header
                                :direction="$sortDirection"
                                :sortField="$sortField"
                                field="name"
                                label="Name" />
                        </th>

                        <th width="10%">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>
                                <input type="checkbox"
                                       value="{{ $item->id }}"
                                       wire:model.live="selected" />
                                {{ $item->id }}
                            </td>

                            <td>{{ $item->name }}</td>

                            <td>
                                @can('category.edit')
                                    <i table_id="{{ $item->id }}"
                                       class="demo-psi-pencil fs-5 me-2 pointer edit"></i>
                                @endcan
                            </td>
                            <td>
                          

                            @can('category.edit')
                                <!-- Add Measurement Field -->
                                                    <a href="{{ route('settings::measurement_category::add_field', $item->id) }}"
                            class="btn btn-sm btn-outline-primary ms-2">
                                <i class="demo-psi-add fs-5"></i>
                            </a>
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

                $(document).on('click', '.edit', function () {
                    Livewire.dispatch(
                        "MeasurementCategory-Page-Update-Component",
                        { id: $(this).attr('table_id') }
                    );
                });

                $('#MeasurementCategoryAdd').click(function () {
                    Livewire.dispatch("MeasurementCategory-Page-Create-Component");
                });

                window.addEventListener('RefreshMeasurementCategoryTable', () => {
                    Livewire.dispatch("MeasurementCategory-Refresh-Component");
                });

            });
        </script>
    @endpush
</div>
