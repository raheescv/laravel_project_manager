<div>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Notification Preferences</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">
                Control which real-time browser notifications you receive. These settings apply to your account only.
            </p>

            <div class="mb-4">
                <h6 class="fw-semibold mb-3">RentOut Module</h6>

                <div class="d-flex align-items-center justify-content-between border rounded px-3 py-2 mb-2">
                    <div>
                        <div class="fw-medium">Browser Notifications</div>
                        <div class="text-muted small">
                            Receive real-time toast &amp; desktop alerts when RentOut bookings are created or their status changes.
                        </div>
                    </div>
                    <div class="form-check form-switch ms-3 mb-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="browserNotificationToggle"
                            wire:model="is_browser_notification_enabled"
                            role="switch"
                        >
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        <i class="demo-psi-save me-1"></i> Save Preferences
                    </span>
                    <span wire:loading wire:target="save">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
