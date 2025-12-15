<div>
    <div class="card">

        <div class="card-header">
            <div class="row align-items-center">

                <div class="col-md-4">
                    <h4 class="mb-0">Measurement Fields</h4>
                </div>

                <!-- Limit Dropdown -->
                <div class="col-md-2">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="col-md-4">
                    <input type="text" wire:model.live="search" 
                           class="form-control" placeholder="Search..." autocomplete="off">
                </div>

            </div>
        </div>

        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="row mb-3">

                <div class="col-md-6">
                    <label for="category">Measurement Category</label>
                    <input type="text" id="category" class="form-control" value="{{ $category->name }}" disabled>
                    @error('category_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-6">
                    <label for="template_name">Field Name</label>
                    <input type="text" id="template_name" wire:model="template_name" class="form-control" placeholder="Template name">
                    @error('template_name') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

            </div>

            <div class="mb-3">
                <button wire:click="save" class="btn btn-primary">
                    @if($template_id) Update @else Save @endif
                </button>
            </div>

            <hr>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th width="80" wire:click="sortBy('id')" style="cursor:pointer;">
                            ID
                            @if ($sortField === 'id')
                                @if ($sortDirection === 'asc') ▲ @else ▼ @endif
                            @endif
                        </th>

                        <th wire:click="sortBy('category_id')" style="cursor:pointer;">
                            Category
                            @if ($sortField === 'category_id')
                                @if ($sortDirection === 'asc') ▲ @else ▼ @endif
                            @endif
                        </th>

                        <th wire:click="sortBy('name')" style="cursor:pointer;">
                            Field Name
                            @if ($sortField === 'name')
                                @if ($sortDirection === 'asc') ▲ @else ▼ @endif
                            @endif
                        </th>

                        <th width="140">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($templates as $i => $t)
                        <tr>
                            <td>{{ $templates->firstItem() + $i }}</td>
                            <td>{{ $t->category?->name }}</td>
                            <td>{{ $t->name }}</td>
                            <td>
                                <button class="btn btn-sm btn-primary me-1" wire:click="editTemplate({{ $t->id }})">
                                    Edit
                                </button>
                                <button class="btn btn-sm btn-danger"
                                        onclick="confirm('Delete this?') || event.stopImmediatePropagation()"
                                        wire:click="deleteTemplate({{ $t->id }})">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No templates found</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                {{ $templates->links() }}
            </div>

        </div>

    </div>
</div>
