<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white">Rent Out Configuration</h5>
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            {{-- Reservation/Booking Form Logos --}}
            <h6 class="fw-bold text-muted text-uppercase mb-2 pb-1 border-bottom">
                <i class="fa fa-image me-1"></i> Reservation / Booking Form Logos
            </h6>
            <p class="text-muted small mb-3">
                Upload logos for the rentout/booking agreement form PDF. Max size: 2MB. Max dimensions: 800×400px. Allowed: JPG, PNG, GIF.
            </p>

            {{-- Bond Paper / Letterhead Mode --}}
            <div class="card bg-light border mb-3">
                <div class="card-body p-3">
                    <h6 class="fw-semibold mb-1">
                        <i class="fa fa-print me-1"></i> Bond Paper / Letterhead Mode
                    </h6>
                    <p class="text-muted small mb-3">
                        When enabled, logos and footer images are hidden during PDF generation but their space is preserved (blank area). Use this
                        when printing on pre-printed bond paper / letterhead stationery.
                    </p>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-medium small">Bond Paper Mode</label>
                            <select wire:model="reservation_bond_paper_mode" class="form-select form-select-sm">
                                <option value="no">Disabled (Show Logos)</option>
                                <option value="yes">Enabled (Hide Logos, Reserve Space)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium small">Header Logo Height (Px)</label>
                            <input type="number" wire:model="reservation_logo_height" class="form-control form-control-sm" min="0"
                                placeholder="80">
                            <small class="form-text text-muted">Reserved blank space height for header area when bond paper mode is on.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium small">Footer Image Height (Px)</label>
                            <input type="number" wire:model="reservation_footer_height" class="form-control form-control-sm" min="0"
                                placeholder="30">
                            <small class="form-text text-muted">Reserved blank space height for footer area when bond paper mode is on.</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Logo Upload Fields --}}
            <div class="row g-3 mb-3">
                {{-- Rental Reservation Logo --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Rental Reservation Logo</label>
                    <input type="file" wire:model="rental_reservation_logo_file" class="form-control form-control-sm" accept="image/*">
                    <small class="form-text text-muted">Max 2MB, max 800×400px.</small>
                    @if ($existing_rental_reservation_logo)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $existing_rental_reservation_logo) }}" class="img-thumbnail" style="max-height: 60px;"
                                alt="Current logo">
                        </div>
                    @endif
                </div>

                {{-- Lease Reservation Logo --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Lease Reservation Logo</label>
                    <input type="file" wire:model="lease_reservation_logo_file" class="form-control form-control-sm" accept="image/*">
                    <small class="form-text text-muted">Max 2MB, max 800×400px.</small>
                    @if ($existing_lease_reservation_logo)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $existing_lease_reservation_logo) }}" class="img-thumbnail" style="max-height: 60px;"
                                alt="Current logo">
                        </div>
                    @endif
                </div>

                {{-- Lease Residential Logo --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Lease Residential Logo</label>
                    <input type="file" wire:model="lease_residential_logo_file" class="form-control form-control-sm" accept="image/*">
                    <small class="form-text text-muted">Max 2MB, max 800×400px.</small>
                    @if ($existing_lease_residential_logo)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $existing_lease_residential_logo) }}" class="img-thumbnail" style="max-height: 60px;"
                                alt="Current logo">
                        </div>
                    @endif
                </div>

                {{-- Rental Residential Logo --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Rental Residential Logo</label>
                    <input type="file" wire:model="rental_residential_logo_file" class="form-control form-control-sm" accept="image/*">
                    <small class="form-text text-muted">Max 2MB, max 800×400px.</small>
                    @if ($existing_rental_residential_logo)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $existing_rental_residential_logo) }}" class="img-thumbnail" style="max-height: 60px;"
                                alt="Current logo">
                        </div>
                    @endif
                </div>

                {{-- Rentout Agreement Footer Image --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Rentout Agreement Footer Image</label>
                    <input type="file" wire:model="rent_out_agreement_footer_file" class="form-control form-control-sm" accept="image/*">
                    <small class="form-text text-muted">Max 2MB, max 800×400px.</small>
                    @if ($existing_rent_out_agreement_footer)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $existing_rent_out_agreement_footer) }}" class="img-thumbnail" style="max-height: 60px;"
                                alt="Current footer">
                        </div>
                    @endif
                </div>
            </div>

            {{-- Rentout Agreement Images (Multiple) --}}
            <div class="mb-3">
                <label class="form-label fw-semibold small">Rentout Agreement Images (Rental Residential Lease)</label>
                <p class="text-muted small mb-2">
                    Multiple images shown at the end of the rental residential lease PDF. Order = upload order.
                </p>
                <input type="file" wire:model="rent_out_agreement_images_files" class="form-control form-control-sm" accept="image/*" multiple>
                <small class="form-text text-muted">Max 2MB each. New upload replaces all. Select multiple files.</small>

                @if (count($existing_rent_out_agreement_images) > 0)
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach ($existing_rent_out_agreement_images as $img)
                            <img src="{{ asset('storage/' . $img) }}" class="img-thumbnail" style="max-height: 60px;" alt="Agreement image">
                        @endforeach
                    </div>
                @endif

                <div class="form-check mt-2">
                    <input type="checkbox" wire:model="clear_agreement_images" class="form-check-input" id="clearAgreementImages">
                    <label class="form-check-label small" for="clearAgreementImages">Clear Existing Agreement Images</label>
                </div>
            </div>


            {{-- LPO Image --}}
            <h6 class="fw-bold text-muted text-uppercase mb-2 pb-1 border-bottom mt-2">
                <i class="fa fa-file-image-o me-1"></i> LPO Header Image
            </h6>
            <p class="text-muted small mb-3">
                Upload a header image for the Local Purchase Order (LPO) PDF. This replaces the default logo. Max size: 2MB. Allowed: JPG, PNG.
            </p>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">LPO Header Image</label>
                    <input type="file" wire:model="lpo_header_image_file" class="form-control form-control-sm" accept="image/*">
                    <small class="form-text text-muted">Max 2MB. Replaces existing image on save.</small>
                    @if ($existing_lpo_header_image)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $existing_lpo_header_image) }}" class="img-thumbnail" style="max-height: 80px;"
                                alt="LPO Header Image">
                        </div>
                    @endif
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
