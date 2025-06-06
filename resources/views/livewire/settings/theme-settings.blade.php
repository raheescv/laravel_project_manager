<div>
    <!-- Theme Settings Component - This will be mostly empty since we're using JavaScript to manage settings -->
    <div id="theme-settings-container" class="theme-settings-container">
        <!-- Any server-rendered content or initial states can be placed here -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize theme from localStorage if available
                initializeThemeFromStorage();

                // Setup event listeners for all theme settings inputs and buttons
                setupThemeEventListeners();

                // Additional check to ensure color scheme is applied
                ensureColorSchemeIsApplied();
            });

            // Function to ensure color scheme is applied
            function ensureColorSchemeIsApplied() {
                try {
                    const storedSettings = localStorage.getItem('pm_theme_settings');
                    if (storedSettings) {
                        const settings = JSON.parse(storedSettings);
                        if (settings && settings.color && settings.color.scheme) {
                            console.log('Ensuring color scheme is applied on page load:', settings.color.scheme);

                            // Apply scheme class directly to document element
                            document.documentElement.className = document.documentElement.className
                                .replace(/\bscheme-\S+/g, '')
                                .trim();
                            document.documentElement.classList.add('scheme-' + settings.color.scheme);

                            // Also update button state
                            document.querySelectorAll('._dm-colorSchemes').forEach(btn => {
                                btn.classList.remove('active');
                                if (btn.getAttribute('data-color') === settings.color.scheme) {
                                    btn.classList.add('active');
                                }
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error applying color scheme on load:', error);
                }
            }

            // Listen for Livewire events - use Livewire v3 syntax
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('themeSaved', (data) => {
                    console.log('Theme saved to database', data);
                });
            });
        </script>
    </div>
</div>
