<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-6 d-flex gap-2 flex-wrap">
                @can('tailoring measurement option.create')
                    <button class="btn btn-primary d-inline-flex align-items-center gap-2" id="TailoringMeasurementOptionAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span>Add New</span>
                    </button>
                @endcan
                @can('tailoring measurement option.delete')
                    @if (count($selected) > 0)
                        <button class="btn btn-outline-danger d-inline-flex align-items-center gap-2" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                            <span>Delete</span>
                        </button>
                    @endif
                @endcan
                <div class="d-flex align-items-center gap-2" wire:ignore>
                    <select wire:model.live="filterType" class="tomSelect" id="type_id" style="width: 100%;">
                        <option value="">-- All Types --</option>
                        @foreach ($optionTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <div class="d-flex bg-light rounded-2 px-2">
                        <span class="d-flex align-items-center text-muted">
                            <i class="demo-psi-list-view fs-6"></i>
                        </span>
                        <select wire:model.live="limit" class="form-select bg-transparent border-0 fw-semibold py-2" style="width: 80px; box-shadow: none; font-size: 0.875rem;">
                            <option value="10">10</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="d-flex bg-light rounded-2 px-2 flex-grow-1 flex-md-grow-0" style="min-width: 200px;">
                        <span class="d-flex align-items-center text-muted">
                            <i class="demo-psi-magnifi-glass fs-6"></i>
                        </span>
                        <input type="text" wire:model.live="search" placeholder="Search value..." class="form-control bg-transparent border-0 py-2" style="box-shadow: none; font-size: 0.875rem;"
                            autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" width="80">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model.live="selectAll" id="selectAll">
                            </div>
                        </th>
                        <th width="10%"> <x-sortable-header field="id" label="ID" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th width="25%"> <x-sortable-header field="option_type" label="Option Type" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th> <x-sortable-header field="value" label="Value" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th width="10%" class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="ps-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $item->id }}" wire:model.live="selected">
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border">#{{ $item->id }}</span></td>
                            <td><span class="badge bg-info-subtle text-info">{{ $optionTypes[$item->option_type] ?? $item->option_type }}</span></td>
                            <td class="fw-semibold text-dark">{{ $item->value }}</td>
                            <td class="text-end pe-4">
                                @can('tailoring measurement option.edit')
                                    <button type="button" table_id="{{ $item->id }}" class="btn btn-icon btn-sm btn-hover btn-light edit" title="Edit Measurement Option">
                                        <i class="demo-psi-pencil fs-5 text-muted"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">No measurement options found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($data->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $data->links() }}
        </div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("TailoringMeasurementOption-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                $('#type_id').change(function() {
                    @this.set('filterType', $(this).val());
                });
                $('#TailoringMeasurementOptionAdd').click(function() {
                    Livewire.dispatch("TailoringMeasurementOption-Page-Create-Component");
                });
                window.addEventListener('RefreshTailoringMeasurementOptionTable', event => {
                    Livewire.dispatch("RefreshTailoringMeasurementOptionTable");
                });
            });
        </script>
    @endpush
</div>
