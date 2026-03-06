<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white">Company Profile</h5>
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-medium small mb-1" for="company_name">
                        <i class="fa fa-building me-1"></i>Company Name
                    </label>
                    {{ html()->input('company_name')->value('')->class('form-control form-control-sm')->placeholder('Enter your company name')->attribute('wire:model', 'company_name') }}
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-medium small mb-1" for="mobile">
                        <i class="fa fa-phone me-1"></i>Contact Number
                    </label>
                    {{ html()->input('mobile')->value('')->class('form-control form-control-sm')->placeholder('Enter your company contact number')->attribute('wire:model', 'mobile') }}
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-medium small mb-1" for="gst">
                        <i class="fa fa-receipt me-1"></i>GST Number
                    </label>
                    {{ html()->input('gst_no')->value('')->class('form-control form-control-sm')->placeholder('Enter your GST number')->attribute('wire:model', 'gst_no') }}
                </div>
                <div class="col-12 col-md-12">
                    <label class="form-label fw-medium small mb-1" for="company_name">
                        <i class="fa fa-building me-1"></i>Company Description
                    </label>
                    {{ html()->textarea('company_description')->value('')->class('form-control form-control-sm')->placeholder('Enter your company description')->attribute('wire:model', 'company_description') }}
                </div>
                <div class="col-12">
                    <label class="form-label fw-medium small mb-1 d-block">
                        <i class="fa fa-image me-1"></i>Company Logo
                    </label>
                    <div class="upload-container border rounded p-2 bg-light">
                        <x-filepond::upload wire:model="logo" multiple max-files="1" class="mb-0" />
                    </div>
                </div>
                @if ($uploaded_logo)
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-header py-1 px-2">
                                <h6 class="mb-0 small">Current Logo</h6>
                            </div>
                            <div class="card-body p-2 text-center">
                                <img src="{{ $uploaded_logo }}" class="img-fluid rounded shadow-sm" alt="Company Logo" style="max-height: 160px;">
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-footer bg-light d-flex justify-content-end py-2 px-3">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="fa fa-save me-1"></i>Save Changes
            </button>
        </div>
    </form>
</div>

@push('scripts')
    @filepondScripts
@endpush
