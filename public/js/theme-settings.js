/**
 * Theme Settings Manager
 *
 * This script handles theme settings storage in localStorage and syncs with the server
 * through Livewire components.
 */

// The key used for storing theme settings in localStorage
const THEME_STORAGE_KEY = 'pm_theme_settings';

/**
 * Initialize theme from localStorage or default settings
 */
function initializeThemeFromStorage() {
    // Get settings from localStorage if they exist
    const storedSettings = localStorage.getItem(THEME_STORAGE_KEY);
    let themeSettings = null;

    try {
        // Try to parse stored settings
        if (storedSettings) {
            themeSettings = JSON.parse(storedSettings);
            console.log('Retrieved settings from localStorage:', themeSettings);
        }

        // Apply the settings to the UI elements
        if (themeSettings && Object.keys(themeSettings).length > 0) {
            console.log('Applying stored theme settings...');
            applyThemeSettings(themeSettings);
        } else {
            console.log('No stored settings found, collecting current UI state...');
            // First time: collect current settings from UI and save to localStorage
            themeSettings = collectCurrentSettings();
            saveToLocalStorage(themeSettings);
        }

        // Ensure the color scheme is always applied, even if other settings fail
        if (themeSettings && themeSettings.color && themeSettings.color.scheme) {
            console.log('Ensuring color scheme is applied:', themeSettings.color.scheme);
            // Apply color scheme directly to ensure it takes effect
            document.documentElement.className = document.documentElement.className
                .replace(/\bscheme-\S+/g, '')
                .trim();
            document.documentElement.classList.add('scheme-' + themeSettings.color.scheme);
        }

        // Send to Livewire component to sync with database
        notifyLivewireComponent(themeSettings);
    } catch (error) {
        console.error('Error initializing theme settings:', error);
        // If there was an error, still try to collect and save current settings
        themeSettings = collectCurrentSettings();
        saveToLocalStorage(themeSettings);
    }
}

/**
 * Collect current settings from UI elements
 */
function collectCurrentSettings() {
    const settings = {
        layout: document.getElementById('_dm-fluidLayoutRadio').checked ? 'fluid' :
            (document.getElementById('_dm-boxedLayoutRadio').checked ? 'boxed' :
                (document.getElementById('_dm-centeredLayoutRadio').checked ? 'centered' : 'fluid')),
        transition: document.getElementById('_dm-transitionSelect').value,
        header: {
            sticky: document.getElementById('_dm-stickyHeaderCheckbox').checked
        },
        navigation: {
            sticky: document.getElementById('_dm-stickyNavCheckbox').checked,
            profileWidget: document.getElementById('_dm-profileWidgetCheckbox').checked,
            mode: document.getElementById('_dm-miniNavRadio').checked ? 'mini' :
                (document.getElementById('_dm-maxiNavRadio').checked ? 'maxi' :
                    (document.getElementById('_dm-pushNavRadio').checked ? 'push' :
                        (document.getElementById('_dm-slideNavRadio').checked ? 'slide' :
                            (document.getElementById('_dm-revealNavRadio').checked ? 'reveal' : 'maxi'))))
        },
        sidebar: {
            disableBackdrop: document.getElementById('_dm-disableBackdropCheckbox').checked,
            staticPosition: document.getElementById('_dm-staticSidebarCheckbox').checked,
            stuck: document.getElementById('_dm-stuckSidebarCheckbox').checked,
            unite: document.getElementById('_dm-uniteSidebarCheckbox').checked,
            pinned: document.getElementById('_dm-pinnedSidebarCheckbox').checked
        },
        color: {
            scheme: getCurrentColorScheme(),
            mode: getCurrentColorMode(),
            darkMode: document.getElementById('settingsThemeToggler').checked
        },
        misc: {
            fontSize: parseInt(document.getElementById('_dm-fontSizeRange').value),
            bodyScrollbar: document.getElementById('_dm-bodyScrollbarCheckbox').checked,
            sidebarsScrollbar: document.getElementById('_dm-sidebarsScrollbarCheckbox').checked
        }
    };

    return settings;
}

/**
 * Get the currently selected color scheme
 */
function getCurrentColorScheme() {
    const activeSchemeBtn = document.querySelector('._dm-colorSchemes.active');
    return activeSchemeBtn ? activeSchemeBtn.getAttribute('data-color') : 'gray';
}

/**
 * Get the currently selected color mode
 */
function getCurrentColorMode() {
    const activeColorModeBtn = document.querySelector('._dm-colorModeBtn.active');
    return activeColorModeBtn ? activeColorModeBtn.getAttribute('data-color-mode') : '';
}

/**
 * Apply theme settings to the UI elements
 */
function applyThemeSettings(settings) {
    // Apply layout settings
    if (settings.layout === 'fluid') {
        document.getElementById('_dm-fluidLayoutRadio').checked = true;
    } else if (settings.layout === 'boxed') {
        document.getElementById('_dm-boxedLayoutRadio').checked = true;
    } else if (settings.layout === 'centered') {
        document.getElementById('_dm-centeredLayoutRadio').checked = true;
    }

    // Apply transition
    document.getElementById('_dm-transitionSelect').value = settings.transition;

    // Apply header settings
    document.getElementById('_dm-stickyHeaderCheckbox').checked = settings.header.sticky;

    // Apply navigation settings
    document.getElementById('_dm-stickyNavCheckbox').checked = settings.navigation.sticky;
    document.getElementById('_dm-profileWidgetCheckbox').checked = settings.navigation.profileWidget;

    if (settings.navigation.mode === 'mini') {
        document.getElementById('_dm-miniNavRadio').checked = true;
    } else if (settings.navigation.mode === 'maxi') {
        document.getElementById('_dm-maxiNavRadio').checked = true;
    } else if (settings.navigation.mode === 'push') {
        document.getElementById('_dm-pushNavRadio').checked = true;
    } else if (settings.navigation.mode === 'slide') {
        document.getElementById('_dm-slideNavRadio').checked = true;
    } else if (settings.navigation.mode === 'reveal') {
        document.getElementById('_dm-revealNavRadio').checked = true;
    }

    // Apply sidebar settings
    document.getElementById('_dm-disableBackdropCheckbox').checked = settings.sidebar.disableBackdrop;
    document.getElementById('_dm-staticSidebarCheckbox').checked = settings.sidebar.staticPosition;
    document.getElementById('_dm-stuckSidebarCheckbox').checked = settings.sidebar.stuck;
    document.getElementById('_dm-uniteSidebarCheckbox').checked = settings.sidebar.unite;
    document.getElementById('_dm-pinnedSidebarCheckbox').checked = settings.sidebar.pinned;

    // Apply color scheme
    applyColorScheme(settings.color.scheme);

    // Apply color mode
    applyColorMode(settings.color.mode);

    // Apply dark mode
    document.getElementById('settingsThemeToggler').checked = settings.color.darkMode;
    if (settings.color.darkMode) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    // Apply misc settings
    document.getElementById('_dm-fontSizeRange').value = settings.misc.fontSize;
    document.getElementById('_dm-fontSizeValue').textContent = settings.misc.fontSize + 'px';
    document.documentElement.style.fontSize = settings.misc.fontSize + 'px';

    document.getElementById('_dm-bodyScrollbarCheckbox').checked = settings.misc.bodyScrollbar;
    document.getElementById('_dm-sidebarsScrollbarCheckbox').checked = settings.misc.sidebarsScrollbar;

    // Apply scrollbar settings
    if (settings.misc.bodyScrollbar && typeof OverlayScrollbars !== 'undefined') {
        OverlayScrollbars(document.body, {
            scrollbars: {
                autoHide: 'leave'
            }
        });
    }

    if (settings.misc.sidebarsScrollbar && typeof OverlayScrollbars !== 'undefined') {
        document.querySelectorAll('.scrollable-content').forEach(el => {
            OverlayScrollbars(el, {
                scrollbars: {
                    autoHide: 'leave'
                }
            });
        });
    }
}

/**
 * Apply color scheme setting
 */
function applyColorScheme(scheme) {
    // First, ensure the scheme is valid
    if (!scheme) scheme = 'gray'; // Default scheme if none is provided

    // Remove active class from all scheme buttons
    document.querySelectorAll('._dm-colorSchemes').forEach(btn => {
        btn.classList.remove('active');
    });

    // Find and activate the button for the selected scheme
    const targetButton = document.querySelector(`._dm-colorSchemes[data-color="${scheme}"]`);
    if (targetButton) {
        targetButton.classList.add('active');
    }

    // Apply the scheme class to the document element
    // First remove any existing scheme classes
    document.documentElement.className = document.documentElement.className
        .replace(/\bscheme-\S+/g, '')
        .trim();

    // Then add the new scheme class
    document.documentElement.classList.add('scheme-' + scheme);

    // For debugging
    console.log('Applied color scheme:', scheme);
}

/**
 * Apply color mode setting
 */
function applyColorMode(mode) {
    document.querySelectorAll('._dm-colorModeBtn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-color-mode') === mode) {
            btn.classList.add('active');
            document.documentElement.className = document.documentElement.className
                .replace(/\btm--\S+/g, '')
                .concat(' ' + mode);
        }
    });
}

/**
 * Save settings to localStorage
 */
function saveToLocalStorage(settings) {
    // Add timestamp for tracking last update
    settings._lastUpdated = new Date().toISOString();
    localStorage.setItem(THEME_STORAGE_KEY, JSON.stringify(settings));

    // Dispatch custom event that settings changed
    document.dispatchEvent(new CustomEvent('themeSettingChanged', { detail: settings }));
}

/**
 * Notify Livewire component of changes
 */
function notifyLivewireComponent(settings) {
    // If Livewire is loaded and our component is available
    if (window.Livewire) {
        // For Livewire v3, we need to use the dispatch method with proper argument format
        window.Livewire.dispatch('themeUpdated', { settings });
    }
}

/**
 * Setup event listeners for all theme settings inputs and buttons
 */
function setupThemeEventListeners() {
    // Layout settings
    document.getElementById('_dm-fluidLayoutRadio')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-boxedLayoutRadio')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-centeredLayoutRadio')?.addEventListener('change', handleSettingChange);

    // Transition settings
    document.getElementById('_dm-transitionSelect')?.addEventListener('change', handleSettingChange);

    // Header settings
    document.getElementById('_dm-stickyHeaderCheckbox')?.addEventListener('change', handleSettingChange);

    // Navigation settings
    document.getElementById('_dm-stickyNavCheckbox')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-profileWidgetCheckbox')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-miniNavRadio')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-maxiNavRadio')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-pushNavRadio')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-slideNavRadio')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-revealNavRadio')?.addEventListener('change', handleSettingChange);

    // Sidebar settings
    document.getElementById('_dm-disableBackdropCheckbox')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-staticSidebarCheckbox')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-stuckSidebarCheckbox')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-uniteSidebarCheckbox')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-pinnedSidebarCheckbox')?.addEventListener('change', handleSettingChange);

    // Color scheme buttons
    document.querySelectorAll('._dm-colorSchemes').forEach(btn => {
        btn.addEventListener('click', handleColorSchemeChange);
    });

    // Color mode buttons
    document.querySelectorAll('._dm-colorModeBtn').forEach(btn => {
        btn.addEventListener('click', handleColorModeChange);
    });

    // Dark mode toggle
    document.getElementById('settingsThemeToggler')?.addEventListener('change', handleSettingChange);

    // Misc settings
    document.getElementById('_dm-fontSizeRange')?.addEventListener('input', handleFontSizeChange);
    document.getElementById('_dm-bodyScrollbarCheckbox')?.addEventListener('change', handleSettingChange);
    document.getElementById('_dm-sidebarsScrollbarCheckbox')?.addEventListener('change', handleSettingChange);
}

/**
 * Handle setting change events
 */
function handleSettingChange() {
    const settings = collectCurrentSettings();
    saveToLocalStorage(settings);
    notifyLivewireComponent(settings);
}

/**
 * Handle color scheme change
 */
function handleColorSchemeChange(e) {
    const scheme = e.currentTarget.getAttribute('data-color');
    if (!scheme) return; // Don't proceed if no scheme was found

    console.log('Color scheme changed to:', scheme);

    // Remove active class from all scheme buttons
    document.querySelectorAll('._dm-colorSchemes').forEach(btn => {
        btn.classList.remove('active');
    });

    // Mark the current button as active
    e.currentTarget.classList.add('active');

    // Update both the class and data-attribute for the color scheme
    document.documentElement.className = document.documentElement.className
        .replace(/\bscheme-\S+/g, '')
        .trim();
    document.documentElement.classList.add('scheme-' + scheme);

    // Update the data-scheme attribute on the html element
    document.documentElement.setAttribute('data-scheme', scheme);

    // Save the changes
    handleSettingChange();

    // Also update directly in localStorage to ensure it's saved
    try {
        const storedSettings = localStorage.getItem(THEME_STORAGE_KEY);
        if (storedSettings) {
            const settings = JSON.parse(storedSettings);
            if (settings.color) {
                settings.color.scheme = scheme;
                localStorage.setItem(THEME_STORAGE_KEY, JSON.stringify(settings));
                console.log('Color scheme saved to localStorage:', scheme);
            }
        }
    } catch (error) {
        console.error('Error saving color scheme to localStorage:', error);
    }
}

/**
 * Handle color mode change
 */
function handleColorModeChange(e) {
    const mode = e.currentTarget.getAttribute('data-color-mode');
    document.querySelectorAll('._dm-colorModeBtn').forEach(btn => {
        btn.classList.remove('active');
    });
    e.currentTarget.classList.add('active');

    document.documentElement.className = document.documentElement.className
        .replace(/\btm--\S+/g, '')
        .concat(' ' + mode);

    handleSettingChange();
}

/**
 * Handle font size change
 */
function handleFontSizeChange(e) {
    const size = e.target.value;
    document.getElementById('_dm-fontSizeValue').textContent = size + 'px';
    document.documentElement.style.fontSize = size + 'px';

    // Debounce to avoid too many updates while sliding
    clearTimeout(this.fontSizeTimeout);
    this.fontSizeTimeout = setTimeout(() => {
        handleSettingChange();
    }, 300);
}
