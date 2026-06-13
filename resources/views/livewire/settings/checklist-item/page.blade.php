<div>
    <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">
            <i class="demo-psi-gear fs-4 me-2 text-primary"></i>
            {{ isset($formData['id']) ? 'Edit Checklist Item' : 'Create New Checklist Item' }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body py-4">
            @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="demo-pli-danger-2 fs-4 me-2"></i>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-md-12">
                    <label for="modal_checklist_category" class="form-label d-flex align-items-center justify-content-between">
                        <span>Category</span>
                        <small class="text-muted fw-normal">Pick an existing one or type a new category</small>
                    </label>
                    <div wire:ignore>
                        <select class="checklist-category-select" id="modal_checklist_category" placeholder="Pick or type a category">
                            <option value="">Pick or type a category</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('formData.category')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <label for="name" class="form-label">Item Name</label>
                    <input type="text" class="form-control @error('formData.name') is-invalid @enderror" id="name" placeholder="Checklist Item Name" required wire:model="formData.name">
                    @error('formData.name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <label for="modal_checklist_property_type_id" class="form-label d-flex align-items-center justify-content-between">
                        <span>Property Type</span>
                        <small class="text-muted fw-normal">Leave blank = applies to all types</small>
                    </label>
                    <div wire:ignore>
                        <select class="select-property_type_id" id="modal_checklist_property_type_id" placeholder="All property types">
                            <option value="">All property types</option>
                        </select>
                    </div>
                    @error('formData.property_type_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <label for="image" class="form-label">Item Image</label>
                    <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" wire:model="image" accept="image/*">
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if ($image)
                        <div class="mt-2">
                            <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="img-thumbnail" style="max-width: 120px; max-height: 120px;">
                        </div>
                    @elseif (!empty($formData['image_path']))
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $formData['image_path']) }}" alt="Current Image" class="img-thumbnail" style="max-width: 120px; max-height: 120px;">
                            <small class="text-muted d-block">Current image</small>
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control @error('formData.sort_order') is-invalid @enderror" id="sort_order" placeholder="Sort Order" wire:model="formData.sort_order">
                    @error('formData.sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="is_active" wire:model="formData.is_active">
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <div class="ms-auto d-flex gap-2">
                <button type="button" wire:click="save(1)" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <i class="demo-psi-repeat-2 fs-5"></i>
                    <span>Save & Add Another</span>
                </button>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4">
                    <i class="demo-psi-save fs-5"></i>
                    <span>Save</span>
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
        @include('components.select.propertyTypeSelect')
        <script>
            $(document).ready(function () {
                // Category: searchable combobox over existing DB categories, with the
                // option to type a brand-new one (create: true).
                var catEl = document.getElementById('modal_checklist_category');
                if (catEl && !catEl.tomselect) {
                    new TomSelect(catEl, {
                        create: true,
                        persist: false,
                        createOnBlur: true,
                        maxItems: 1,
                        render: {
                            option_create: function (data, escape) {
                                return '<div class="create">Add <strong>' + escape(data.input) + '</strong>…</div>';
                            },
                        },
                    });
                }

                // Sync both pickers into Livewire on change.
                $('#modal_checklist_category').on('change', function () {
                    @this.set('formData.category', this.value || '');
                });
                $('#modal_checklist_property_type_id').on('change', function () {
                    @this.set('formData.property_type_id', $(this).val() || null);
                });

                // Seed (edit) or clear (create / save-and-add-another) the pickers.
                window.addEventListener('SetChecklistModalSelects', function (e) {
                    var d = e.detail || {};

                    var ptEl = document.querySelector('#modal_checklist_property_type_id');
                    var pt = ptEl && ptEl.tomselect;
                    if (pt) {
                        pt.clear(true);
                        pt.clearOptions();
                        if (d.propertyTypeId) {
                            pt.addOption({ id: d.propertyTypeId, name: d.propertyTypeName || d.propertyTypeId });
                            pt.addItem(String(d.propertyTypeId), true);
                        }
                    }

                    var cat = catEl && catEl.tomselect;
                    if (cat) {
                        cat.clear(true); // keep the existing-category options for suggestions
                        if (d.category) {
                            if (!cat.options[d.category]) {
                                cat.addOption({ value: d.category, text: d.category });
                            }
                            cat.addItem(d.category, true);
                        }
                    }
                });
            });
        </script>
    @endpush
</div>
