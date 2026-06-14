<script>
    // This script runs inline before any external scripts are loaded
    (function() {
        try {
            const storedSettings = localStorage.getItem('pm_theme_settings');
            if (storedSettings) {
                const settings = JSON.parse(storedSettings);
                if (settings && settings.color && settings.color.scheme) {
                    // Apply color scheme immediately to prevent flash of unstyled content
                    document.documentElement.setAttribute('data-scheme', settings.color.scheme);
                    document.documentElement.classList.add('scheme-' + settings.color.scheme);

                    // Also apply dark mode if enabled
                    if (settings.color.darkMode) {
                        document.documentElement.setAttribute('data-bs-theme', 'dark');
                        document.documentElement.classList.add('dark');
                    }
                }

                // Apply the saved base font-size here too (theme-applier.js otherwise
                // sets it only after first paint, so every navigation renders at the
                // default 16px root then snaps to the saved size — reflowing all
                // rem-based UI. That is the "font size jump / window glitch" on page change.)
                if (settings && settings.misc && settings.misc.fontSize) {
                    document.documentElement.style.fontSize = settings.misc.fontSize + 'px';
                }
            }
        } catch (error) {
            console.error('Error in early theme initialization:', error);
        }
    })();
</script>
