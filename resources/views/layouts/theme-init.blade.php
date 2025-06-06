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
            }
        } catch (error) {
            console.error('Error in early theme initialization:', error);
        }
    })();
</script>
