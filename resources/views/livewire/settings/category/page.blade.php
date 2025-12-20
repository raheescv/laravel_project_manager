<div>
    <!-- Modal Header with Gradient -->
    <div class="modal-header bg-primary bg-gradient text-white border-0">
        <div class="d-flex align-items-center">
            <div class="p-2 bg-white bg-opacity-20 rounded-circle me-3">
                <i class="fa fa-folder-open fa-lg"></i>
            </div>
            <div>
                <h1 class="modal-title fs-5 mb-0 fw-semibold">
                    {{ $table_id ? 'Edit Category' : 'Create New Category' }}
                </h1>
                <small class="opacity-75">Manage category information</small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <form wire:submit="save">
        <div class="modal-body p-4">
            <!-- Error Messages -->
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="fa fa-exclamation-circle me-2"></i>
                    <div class="flex-grow-1">
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach ($this->getErrorBag()->toArray() as $field => $errors)
                                @foreach ($errors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Form Fields Card -->
            <div class="card border-0 shadow-sm mb-0">
                <div class="card-body p-4">
                    <!-- Parent Category Field -->
                    <div class="mb-4" wire:ignore>
                        <label for="modal_parent_id" class="form-label fw-semibold mb-2 d-flex align-items-center">
                            <i class="fa fa-sitemap me-2 text-primary"></i>
                            Parent Category
                            <span class="text-muted ms-2 fw-normal">(Optional)</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa fa-folder text-muted"></i>
                            </span>
                            {{ html()->select('parent_id', [])->value('')->class('select-category_id form-control border-start-0')->placeholder('Select a parent category (if any)')->id('modal_parent_id') }}
                        </div>
                        <small class="form-text text-muted mt-1 d-block">
                            <i class="fa fa-info-circle me-1"></i>
                            Leave empty if this is a top-level category
                        </small>
                    </div>

                    <!-- Category Name Field -->
                    <div class="mb-4">
                        <label for="category_name" class="form-label fw-semibold mb-2 d-flex align-items-center">
                            <i class="fa fa-tag me-2 text-primary"></i>
                            Category Name
                            <span class="text-danger ms-1">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa fa-font text-muted"></i>
                            </span>
                            {{ html()->input('name')->value('')->class('form-control border-start-0')->attribute('wire:model', 'categories.name')->id('category_name')->placeholder('Enter category name') }}
                        </div>
                        @error('categories.name')
                            <div class="text-danger small mt-1">
                                <i class="fa fa-exclamation-circle me-1"></i>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Sale Visibility Flag Field -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-2 d-flex align-items-center">
                            <i class="fa fa-eye me-2 text-primary"></i>
                            Sale Visibility
                        </label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="sale_visibility_flag" wire:model="categories.sale_visibility_flag">
                            <label class="form-check-label" for="sale_visibility_flag">
                                <span class="fw-semibold">Visible in Sales</span>
                                <small class="text-muted d-block mt-1">
                                    <i class="fa fa-info-circle me-1"></i>
                                    When enabled, this category will be visible in the pos sales screen
                                </small>
                            </label>
                        </div>
                    </div>

                    <!-- Online Visibility Flag Field -->
                    <div class="mb-0">
                        <label class="form-label fw-semibold mb-2 d-flex align-items-center">
                            <i class="fa fa-globe me-2 text-primary"></i>
                            Online Visibility
                        </label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="online_visibility_flag" wire:model="categories.online_visibility_flag">
                            <label class="form-check-label" for="online_visibility_flag">
                                <span class="fw-semibold">Visible Online</span>
                                <small class="text-muted d-block mt-1">
                                    <i class="fa fa-info-circle me-1"></i>
                                    When enabled, this category will be visible on the online platform
                                </small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer border-top bg-light px-4 py-3">
            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                <i class="fa fa-times me-2"></i>
                Cancel
            </button>
            <button type="button" wire:click="save('completed')" class="btn btn-success">
                <i class="fa fa-save me-2"></i>
                Save & Add New
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-check me-2"></i>
                Save Category
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#modal_parent_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('categories.parent_id', value);
                });
                window.addEventListener('SelectDropDownValues', event => {
                    @this.set('categories.parent_id', @this.categories['parent_id']);
                    var tomSelectInstance = document.querySelector('#modal_parent_id').tomselect;
                    if (@this.categories['parent_id']) {
                        preselectedData = {
                            id: @this.categories['parent_id'],
                            name: @this.categories['parent']['name'],
                        };
                        tomSelectInstance.addOption(preselectedData);
                        tomSelectInstance.addItem(preselectedData.id);
                    } else {
                        tomSelectInstance.clear();
                    }
                });
            });
        </script>
    @endpush
</div>
