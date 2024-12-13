<div>
    <div class="card-header -4 mb-3">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <button class="btn btn-primary hstack gap-2 align-self-center" id="ProductTypeAdd">
                    <i class="demo-psi-add fs-5"></i>
                    <span class="vr"></span>
                    Add New
                </button>
                <button class="btn btn-icon btn-outline-light">
                    <i class="demo-pli-printer fs-5"></i>
                </button>
                <div class="btn-group">
                    <button class="btn btn-icon btn-outline-light"><i class="demo-pli-file-excel fs-5"></i></button>
                    <button class="btn btn-icon btn-outline-light" wire:click="delete()"><i class="demo-pli-recycling fs-5"></i></button>
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
                    <button class="btn btn-icon btn-outline-light"><i class="demo-pli-download-from-cloud fs-5"></i></button>
                    <button class="btn btn-icon btn-outline-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">Action</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" wire:model.live="selectAll" /> #
                        </th>
                        <th>Name</th>
                        <th>Action</th>
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
                            <td> <i data-id="{{ $item->id }}" class="demo-psi-pencil fs-5 me-2 pointer edit"></i> </td>
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
                    Livewire.dispatch("ProductType-Page-Update-Component", {
                        id: $(this).data('id')
                    });
                });
                $('#ProductTypeAdd').click(function() {
                    Livewire.dispatch("ProductType-Page-Create-Component");
                });
                window.addEventListener('RefreshProductTypeTable', event => {
                    Livewire.dispatch("ProductType-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
