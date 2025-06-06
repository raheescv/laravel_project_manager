/**
 * Server-side Theme Settings Loader
 *
 * This script provides functionality to synchronize theme settings between
 * server-side (database) and client-side (localStorage).
 */

document.addEventListener('DOMContentLoaded', function () {
    // Check if we need to fetch server settings
    const lastSync = localStorage.getItem('theme_settings_last_sync');
    const now = new Date().getTime();

    // Sync with server no more than once every hour to avoid excessive requests
    if (!lastSync || (now - parseInt(lastSync)) > 3600000) {
        fetchServerThemeSettings();
    }
});

/**
 * Fetch theme settings from the server via AJAX
 */
function fetchServerThemeSettings() {
    fetch('/api/theme-settings')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch theme settings');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.settings) {
                handleServerSettings(data.settings);
            }
        })
        .catch(error => {
            console.error('Error fetching theme settings:', error);
        })
        .finally(() => {
            // Update last sync timestamp regardless of success/failure
            localStorage.setItem('theme_settings_last_sync', new Date().getTime().toString());
        });
}

/**
 * Handle server settings - merge with local settings or replace them
 */
function handleServerSettings(serverSettings) {
    const localSettingsStr = localStorage.getItem('pm_theme_settings');

    if (!localSettingsStr) {
        // No local settings, use server settings
        localStorage.setItem('pm_theme_settings', JSON.stringify(serverSettings));
        window.location.reload(); // Reload to apply settings
        return;
    }

    try {
        const localSettings = JSON.parse(localSettingsStr);

        // Check if server settings are newer than local settings
        const serverUpdated = new Date(serverSettings._lastUpdated || 0);
        const localUpdated = new Date(localSettings._lastUpdated || 0);

        if (serverUpdated > localUpdated) {
            // Server has newer settings, use them
            localStorage.setItem('pm_theme_settings', JSON.stringify(serverSettings));
            window.location.reload(); // Reload to apply settings
        } else if (localUpdated > serverUpdated) {
            // Local settings are newer, update server (via Livewire event)
            if (window.Livewire) {
                // For Livewire v3, we need to use the dispatch method with proper argument format
                window.Livewire.dispatch('themeUpdated', { settings: localSettings });
            }
        }
    } catch (e) {
        console.error('Error handling server settings:', e);
    }
}
