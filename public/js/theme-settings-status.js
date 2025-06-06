document.addEventListener('DOMContentLoaded', function () {
    // Initialize theme settings status display
    updateThemeSettingsStatus();

    // Sync theme settings button
    document.getElementById('syncThemeSettings')?.addEventListener('click', function () {
        // Show loading state
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="demo-psi-synchronize me-2 fa-spin"></i> Syncing...';
        this.disabled = true;

        // Force sync regardless of last sync time
        localStorage.removeItem('theme_settings_last_sync');

        // Call the sync function from theme-settings-sync.js
        fetchServerThemeSettings();

        // Restore button after timeout
        setTimeout(() => {
            this.innerHTML = originalText;
            this.disabled = false;
            updateThemeSettingsStatus();
        }, 2000);
    });

    // Reset theme settings button
    document.getElementById('resetThemeSettings')?.addEventListener('click', function () {
        // Show confirmation dialog
        if (confirm('Are you sure you want to reset all theme settings to default values? This cannot be undone.')) {
            // Remove from localStorage
            localStorage.removeItem('pm_theme_settings');

            // Notify user
            alert('Theme settings have been reset. The page will now reload to apply default settings.');

            // Reload page to apply default settings
            window.location.reload();
        }
    });

    // Update status display when settings change
    window.addEventListener('storage', function (e) {
        if (e.key === 'pm_theme_settings') {
            updateThemeSettingsStatus();
        }
    });
});

// Update the theme settings status display
function updateThemeSettingsStatus() {
    const storageStatus = document.getElementById('storageStatus');
    const lastUpdated = document.getElementById('lastUpdated');
    const syncStatus = document.getElementById('syncStatus');

    // Check if theme settings exist in localStorage
    const storedSettings = localStorage.getItem('pm_theme_settings');
    const lastSync = localStorage.getItem('theme_settings_last_sync');

    if (storedSettings) {
        // Parse settings to get last updated timestamp
        try {
            const settings = JSON.parse(storedSettings);

            storageStatus.textContent = 'Active (using browser localStorage)';
            storageStatus.className = 'text-success';

            // Display last updated time if available
            if (settings._lastUpdated) {
                const date = new Date(settings._lastUpdated);
                lastUpdated.textContent = date.toLocaleString();
            } else {
                lastUpdated.textContent = 'Unknown';
            }

            // Show sync status
            if (lastSync) {
                const syncDate = new Date(parseInt(lastSync));
                syncStatus.textContent = 'Last synced: ' + syncDate.toLocaleString();

                // Check if sync is recent (last 10 minutes)
                const now = new Date().getTime();
                if ((now - parseInt(lastSync)) < 600000) {
                    syncStatus.className = 'text-success';
                } else {
                    syncStatus.className = 'text-warning';
                }
            } else {
                syncStatus.textContent = 'Never synced with server';
                syncStatus.className = 'text-warning';
            }

        } catch (e) {
            storageStatus.textContent = 'Error parsing stored settings';
            storageStatus.className = 'text-danger';
            lastUpdated.textContent = 'N/A';
            syncStatus.textContent = 'Failed';
            syncStatus.className = 'text-danger';
        }
    } else {
        storageStatus.textContent = 'Not configured (using defaults)';
        storageStatus.className = 'text-warning';
        lastUpdated.textContent = 'Never';
        syncStatus.textContent = 'Not synced';
        syncStatus.className = 'text-warning';
    }
}

// Add timestamp when settings change
document.addEventListener('themeSettingChanged', function () {
    const storedSettings = localStorage.getItem('pm_theme_settings');
    if (storedSettings) {
        try {
            const settings = JSON.parse(storedSettings);
            settings._lastUpdated = new Date().toISOString();
            localStorage.setItem('pm_theme_settings', JSON.stringify(settings));
            updateThemeSettingsStatus();
        } catch (e) {
            console.error('Error updating settings timestamp', e);
        }
    }
});
