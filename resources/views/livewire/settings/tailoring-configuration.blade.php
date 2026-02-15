<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0 text-white">Tailoring Configuration Settings</h4>
                </div>
                <form wire:submit="save">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label fw-medium" for="redirection_page">Order Redirection after create</label>
                                    {{ html()->select('redirection_page', ['create' => 'Create Page', 'show' => 'Show Page'])->value('')->class('form-select')->placeholder('Select where to redirect')->attribute('wire:model', 'redirection_page') }}
                                    <small class="form-text text-muted">Choose whether to redirect to the create page or show page after tailoring order actions.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-end py-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
