<div>
    <div class="card">

        {{-- HEADER --}}
        <div class="card-header">
            <div class="row align-items-center">

                <div class="col-md-3">
                    <h4 class="mb-0">Measurement Fields</h4>
                </div>

                <div class="col-md-2">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <input type="text"
                           wire:model.live="search"
                           class="form-control"
                           placeholder="Search...">
                </div>

                <div class="col-md-4 text-end">
                    <button class="btn btn-success"
                            wire:click="openModal">
                        + Add Field
                    </button>

                 
                  
                        <button class="btn btn-danger ms-2"
                                onclick="confirm('Delete selected items?') || event.stopImmediatePropagation()"
                                wire:click="bulkDelete">
                            Delete 
                        </button>
                   
                </div>

            </div>
        </div>

        {{-- BODY --}}
        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-3">
                <label>Measurement Category</label>
                <input type="text"
                       class="form-control"
                       value="{{ $category->name }}"
                       disabled>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">

                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox"
                                       wire:model="selectAll">
                            </th>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Field Name</th>
                            <th width="140">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($templates as $i => $t)
                            <tr>
                                <td>
                                    <input type="checkbox"
                                           wire:model="selectedTemplates"
                                           value="{{ $t->id }}">
                                </td>

                                <td>{{ $templates->firstItem() + $i }}</td>
                                <td>{{ $t->category?->name }}</td>
                                <td>{{ $t->name }}</td>

                                <td>
                                    <button class="btn btn-sm btn-primary"
                                            wire:click="editTemplate({{ $t->id }})">
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
                                <td colspan="5" class="text-center">
                                    No templates found
                                </td>
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

    {{-- MODAL --}}
    @if($showModal)
        <div class="modal fade show d-block" style="background:rgba(0,0,0,.5)">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $template_id ? 'Edit Field' : 'Add Field' }}
                        </h5>
                        <button class="btn-close"
                                wire:click="closeModal"></button>
                    </div>

                    <div class="modal-body">
                        <label>Field Name</label>
                        <input type="text"
                               wire:model.defer="template_name"
                               class="form-control">
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary"
                                wire:click="closeModal">
                            Cancel
                        </button>

                        <button class="btn btn-primary"
                                wire:click="save(false)">
                            Save
                        </button>

                        <button class="btn btn-success"
                                wire:click="save(true)">
                            Save & New
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
