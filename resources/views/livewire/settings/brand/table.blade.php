<div>
    <div class="card-header -4 mb-3">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                @can('brand.create')
                    <button class="btn btn-primary hstack gap-2 align-self-center" id="BrandAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span class="vr"></span>
                        Add New
                    </button>
                @endcan
                @can('brand.delete')
                    <div class="btn-group">
                        <button class="btn btn-icon btn-outline-light" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?"><i class="demo-pli-recycling fs-5"></i>
                        </button>
                    </div>
                @endcan
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
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="#" wire:click.prevent="sortBy('name')">
                                Name
                                @if ($sortField === 'name')
                                    {!! sortDirection($sortDirection) !!}
                                @endif
                            </a>
                        </th>
                        <th width="15%">Logo</th>
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
                            <td>{{ $item->name }}</td>
                            <td>
                                @if ($item->image_path)
                                    <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }} Logo" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">
                                @else
                                    <span class="text-muted">No Logo</span>
                                @endif
                            </td>
                            <td>
                                @can('brand.edit')
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
                    Livewire.dispatch("Brand-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                $('#BrandAdd').click(function() {
                    Livewire.dispatch("Brand-Page-Create-Component");
                });
                window.addEventListener('RefreshBrandTable', event => {
                    Livewire.dispatch("Brand-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>

