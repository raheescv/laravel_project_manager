<div class="card border-0 shadow-sm mt-4" id="category-measurements-card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Measurements Configuration @if($category) for <span class="text-primary">{{ $category->name }}</span> @endif</h5>
            <p class="text-muted small mb-0">Configure dynamic measurement fields for this category</p>
        </div>
        <div class="d-flex gap-2">
            @if($categoryId && !$showForm)
                <button class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" wire:click="addNew">
                    <i class="fa fa-plus fs-6"></i>
                    <span>Add Field</span>
                </button>
            @endif
        </div>
    </div>

    <div class="card-body">
        @if(!$categoryId)
            <div class="text-center py-5 text-muted">
                <i class="fa fa-tasks fs-1 mb-3"></i>
                <p>Please select a category from the table above to configure its measurements.</p>
            </div>
        @elseif($showForm)
            <!-- Form to add/edit measurement field -->
            <form wire:submit.prevent="save" class="p-4 bg-light rounded-3 shadow-none border">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Field Key</label>
                        <input type="text" wire:model="field_key" class="form-control @error('field_key') is-invalid @enderror" placeholder="e.g. length, shoulder, cuff_size">
                        @error('field_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text mt-1">Unique key for this measurement (lowercase, no spaces).</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Display Label</label>
                        <input type="text" wire:model="label" class="form-control @error('label') is-invalid @enderror" placeholder="e.g. Length, Shoulder">
                        @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text mt-1">The name shown on the measurement form.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Field Type</label>
                        <select wire:model.live="field_type" class="form-select">
                            <option value="input">Input (Number/Text)</option>
                            <option value="select">Dropdown (Select)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Section</label>
                        <select wire:model="section" class="form-select">
                            <option value="basic_body">Basic & Body</option>
                            <option value="collar_cuff">Collar & Cuff</option>
                            <option value="specifications">Specifications</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Options Source (if select)</label>
                        <input type="text" wire:model="options_source" class="form-control" placeholder="e.g. collar_models, cuff_designs" @if($field_type != 'select') disabled @endif>
                        <div class="form-text mt-1">Source key for dropdown options.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Sort Order</label>
                        <input type="number" wire:model="sort_order" class="form-control">
                    </div>
                    <div class="col-md-9 d-flex align-items-center gap-4 pt-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="is_active" id="isActive">
                            <label class="form-check-label" for="isActive">Is Active</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="is_required" id="isRequired">
                            <label class="form-check-label" for="isRequired">Is Required</label>
                        </div>
                    </div>
                    <div class="col-12 mt-4 d-flex justify-content-end gap-2 border-top pt-3">
                        <button type="button" class="btn btn-light" wire:click="cancel">Cancel</button>
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i class="fa fa-save fs-6"></i>
                            <span>{{ $editingMeasurement ? 'Update Measurement' : 'Save Measurement' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        @else
            <!-- List of measurements -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="80">Order</th>
                            <th>Field Key</th>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th width="120" class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($measurements as $m)
                            <tr>
                                <td><span class="badge bg-info">{{ $m->sort_order }}</span></td>
                                <td class="fw-semibold text-dark">{{ $m->field_key }}</td>
                                <td>{{ $m->label }}</td>
                                <td>
                                    @if($m->field_type == 'select')
                                        <span class="badge bg-warning-subtle text-warning">Select ({{ $m->options_source }})</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary">Input</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $sections = [
                                            'basic_body' => 'Basic & Body',
                                            'collar_cuff' => 'Collar & Cuff',
                                            'specifications' => 'Specifications'
                                        ];
                                    @endphp
                                    <span class="text-muted small text-uppercase fw-bold">{{ $sections[$m->section] ?? $m->section }}</span>
                                </td>
                                <td>
                                    @if($m->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-icon btn-sm btn-hover btn-light" wire:click="edit({{ $m->id }})" title="Edit Field">
                                        <i class="fa fa-pencil fs-5 text-muted"></i>
                                    </button>
                                    <button class="btn btn-icon btn-sm btn-hover btn-light text-danger" wire:click="delete({{ $m->id }})" wire:confirm="Are you sure you want to delete this field?" title="Delete Field">
                                        <i class="fa fa-trash fs-5"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa fa-edit fs-2 mb-2 d-block"></i>
                                    No measurements configured for this category yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
