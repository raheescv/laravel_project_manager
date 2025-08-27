<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0 text-white">Company Profile</h4>
                </div>

                <form wire:submit="save">
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label fw-medium" for="company_name">
                                        <i class="fa fa-building me-2"></i>Company Name
                                    </label>
                                    {{ html()->input('company_name')->value('')->class('form-control')->placeholder('Enter your company name')->attribute('wire:model', 'company_name') }}
                                </div><br>
                                <div class="form-group">
                                    <label class="form-label fw-medium" for="mobile">
                                        <i class="fa fa-phone me-2"></i>Contact Number
                                    </label>
                                    {{ html()->input('mobile')->value('')->class('form-control')->placeholder('Enter your company contact number')->attribute('wire:model', 'mobile') }}
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label fw-medium d-block mb-3">
                                        <i class="fa fa-image me-2"></i>Company Logo
                                    </label>
                                    <div class="upload-container border rounded p-3 bg-light">
                                        <x-filepond::upload wire:model="logo" multiple max-files="1" class="mb-3" />
                                    </div>
                                </div>
                            </div>

                            @if ($uploaded_logo)
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Current Logo</h6>
                                        </div>
                                        <div class="card-body p-3 text-center">
                                            <img src="{{ $uploaded_logo }}" class="img-fluid rounded shadow-sm" alt="Company Logo" style="max-height: 200px;">
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer bg-light d-flex justify-content-end py-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    @filepondScripts
@endpush
