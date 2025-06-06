/**
 * Global Theme Applier
 *
 * This script ensures that theme settings from localStorage are applied
 * on every page load, regardless of whether it's the settings page or not.
 */

document.addEventListener('DOMContentLoaded', function () {
    applyStoredThemeSettings();
});

/**
 * Apply stored theme settings from localStorage
 */
function applyStoredThemeSettings() {
    try {
        const storedSettings = localStorage.getItem('pm_theme_settings');
        if (!storedSettings) return;

        const settings = JSON.parse(storedSettings);
        if (!settings || !settings.color) return;

        // Apply color scheme
        if (settings.color.scheme) {
            // console.log('Global: Applying stored color scheme:', settings.color.scheme);

            // Update the data-scheme attribute on the html element
            document.documentElement.setAttribute('data-scheme', settings.color.scheme);

            // Apply class to document element
            document.documentElement.className = document.documentElement.className
                .replace(/\bscheme-\S+/g, '')
                .trim();
            document.documentElement.classList.add('scheme-' + settings.color.scheme);

            // Force application of the color scheme by injecting CSS
            const styleId = 'theme-enforcer-style';
            let styleEl = document.getElementById(styleId);

            if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = styleId;
                document.head.appendChild(styleEl);
            }

            styleEl.textContent = `
                :root {
                    --scheme: ${settings.color.scheme};
                }
                html[data-scheme="${settings.color.scheme}"] {
                    --scheme: ${settings.color.scheme} !important;
                }
                html.scheme-${settings.color.scheme} {
                    --scheme: ${settings.color.scheme} !important;
                }
            `;

            // If the page is loaded and there's an issue with the color scheme not being applied,
            // we'll set it again after a short delay to ensure it takes effect
            setTimeout(() => {
                document.documentElement.classList.add('scheme-' + settings.color.scheme);
            }, 100);
        }

        // Apply color mode
        if (settings.color.mode) {
            // console.log('Global: Applying stored color mode:', settings.color.mode);
            document.documentElement.className = document.documentElement.className
                .replace(/\btm--\S+/g, '')
                .trim();
            if (settings.color.mode) {
                document.documentElement.classList.add(settings.color.mode);
            }
        }

        // Apply dark mode
        if (settings.color.darkMode) {
            // console.log('Global: Applying dark mode');
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Apply font size
        if (settings.misc && settings.misc.fontSize) {
            // console.log('Global: Applying font size:', settings.misc.fontSize + 'px');
            document.documentElement.style.fontSize = settings.misc.fontSize + 'px';
        }
    } catch (error) {
        console.error('Error applying stored theme settings:', error);
    }
}
