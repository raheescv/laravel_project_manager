<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white">Tailoring Configuration Settings</h5>
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small mb-1" for="redirection_page">Order Redirection after create</label>
                    {{ html()->select('redirection_page', ['create' => 'Create Page', 'show' => 'Show Page'])->value('')->class('form-select form-select-sm')->placeholder('Select where to redirect')->attribute('wire:model', 'redirection_page') }}
                    <small class="form-text text-muted">Choose whether to redirect to the create page or show page after tailoring order actions.</small>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light text-end py-2 px-3">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="fa fa-save me-1"></i>Save Changes
            </button>
        </div>
    </form>
</div>
