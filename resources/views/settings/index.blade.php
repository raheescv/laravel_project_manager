<x-app-layout>
    <div class=" content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Settings</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Settings</h1>
            <p class="lead">
                A table is an arrangement of Settings
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="tab-base">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link " data-bs-toggle="tab" data-bs-target="#tabsConfiguration" type="button" role="tab" aria-controls="profile" aria-selected="false"
                                    tabindex="-1">
                                    Configuration
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsSaleSettings" type="button" role="tab" aria-controls="profile" aria-selected="false"
                                    tabindex="-1">
                                    Sale Settings
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabsCompanyProfile" type="button" role="tab" aria-controls="profile" aria-selected="false"
                                    tabindex="-1">
                                    Company Profile
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsTheme" type="button" role="tab" aria-controls="profile" aria-selected="false" tabindex="-1">
                                    Theme
                                </button>
                            </li>
                            @can('whatsapp.integration')
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link " data-bs-toggle="tab" data-bs-target="#tabsWhatsapp" type="button" role="tab" aria-controls="home" aria-selected="true">
                                        Whatsapp
                                    </button>
                                </li>
                            @endcan
                        </ul>
                        <div class="tab-content">
                            <div id="tabsConfiguration" class="tab-pane" role="tabpanel">
                                @livewire('settings.configurations')
                            </div>
                            <div id="tabsSaleSettings" class="tab-pane" role="tabpanel">
                                @livewire('settings.sale-configuration')
                            </div>
                            <div id="tabsCompanyProfile" class="tab-pane fade active show" role="tabpanel">
                                @livewire('settings.company-profile')
                            </div>
                            <div id="tabsTheme" class="tab-pane" role="tabpanel">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Theme Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-4">
                                            Customize your workspace appearance. All settings are automatically saved to your browser's local storage and synchronized with your account.
                                        </p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <h6>Layout Preferences</h6>
                                                    <p class="text-muted small">
                                                        Choose your preferred layout style, transitions and positioning.
                                                    </p>
                                                    <button class="btn btn-primary" id="openSettingsOffcanvas" type="button" data-bs-toggle="offcanvas" data-bs-target="#_dm-settingsContainer">
                                                        <i class="demo-psi-gear me-2"></i> Open Settings Panel
                                                    </button>
                                                </div>

                                                <div class="mb-4">
                                                    <h6>Sync with Server</h6>
                                                    <p class="text-muted small">
                                                        Synchronize theme settings between your browser and the server.
                                                    </p>
                                                    <button class="btn btn-outline-primary" id="syncThemeSettings">
                                                        <i class="demo-psi-synchronize me-2"></i> Sync Settings
                                                    </button>
                                                </div>

                                                <div class="mb-4">
                                                    <h6>Reset to Defaults</h6>
                                                    <p class="text-muted small">
                                                        Reset all theme settings to their default values.
                                                    </p>
                                                    <button class="btn btn-outline-danger" id="resetThemeSettings">
                                                        <i class="demo-psi-reset me-2"></i> Reset Settings
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6>Theme Settings Status</h6>
                                                        <div id="themeSettingsStatus">
                                                            <p><strong>Storage:</strong> <span id="storageStatus">Checking...</span></p>
                                                            <p><strong>Last Updated:</strong> <span id="lastUpdated">Checking...</span></p>
                                                            <p><strong>Sync Status:</strong> <span id="syncStatus">Checking...</span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @can('whatsapp.integration')
                                <div id="tabsWhatsapp" class="tab-pane" role="tabpanel">
                                    @livewire('settings.whatsapp')
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- SETTINGS CONTAINER  -->
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <div id="_dm-settingsContainer" class="_dm-settings-container offcanvas offcanvas-end rounded-start" tabindex="-1">
        <button id="_dm-settingsToggler" class="_dm-btn-settings btn btn-sm btn-danger p-2 rounded-0 rounded-start shadow-none" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#_dm-settingsContainer" aria-label="Customization button" aria-controls="#_dm-settingsContainer">
            <i class="demo-psi-gear fs-1"></i>
        </button>

        @livewire('settings.theme-settings')

        <div class="offcanvas-body py-0">
            <div class="_dm-settings-container__content row">
                <div class="col-lg-3 p-4">

                    <h4 class="fw-bold pb-3 mb-2">Layouts</h4>

                    <!-- OPTION : Centered Layout -->
                    <h6 class="mb-2 pb-1">Layouts</h6>
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-fluidLayoutRadio">Fluid Layout</label>
                        <div class="form-check form-switch">
                            <input id="_dm-fluidLayoutRadio" class="form-check-input ms-0" type="radio" name="settingLayouts" autocomplete="off" checked>
                        </div>
                    </div>

                    <!-- OPTION : Boxed layout -->
                    <div class="d-flex align-items-center pt-1 mb-2" hidden style="display: none">
                        <label class="form-check-label flex-fill" for="_dm-boxedLayoutRadio">Boxed Layout</label>
                        <div class="form-check form-switch">
                            <input id="_dm-boxedLayoutRadio" class="form-check-input ms-0" type="radio" name="settingLayouts" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Boxed layout with background images -->
                    <div id="_dm-boxedBgOption" class="opacity-50 d-flex align-items-center pt-1 mb-2" hidden style="display: none">
                        <label class="form-label flex-fill mb-0">BG for
                            Boxed Layout</label>

                        <button id="_dm-boxedBgBtn" class="btn btn-icon btn-primary btn-xs" type="button" data-bs-toggle="offcanvas" data-bs-target="#_dm-boxedBgContent" disabled>
                            <i class="demo-psi-dot-horizontal"></i>
                        </button>
                    </div>

                    <!-- OPTION : Centered Layout -->
                    <div class="d-flex align-items-start pt-1 pb-3 mb-2">
                        <label class="form-check-label flex-fill text-nowrap" for="_dm-centeredLayoutRadio">Centered
                            Layout</label>
                        <div class="form-check form-switch">
                            <input id="_dm-centeredLayoutRadio" class="form-check-input ms-0" type="radio" name="settingLayouts" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Transition timing -->
                    <h6 class="mt-4 mb-2 py-1">Transitions</h6>
                    <div class="d-flex align-items-center pt-1 pb-3 mb-2">
                        <select id="_dm-transitionSelect" class="form-select" aria-label="select transition timing">
                            <option value="in-quart">In Quart</option>
                            <option value="out-quart" selected>Out
                                Quart</option>
                            <option value="in-back">In Back</option>
                            <option value="out-back">Out Back</option>
                            <option value="in-out-back">In Out Back</option>
                            <option value="steps">Steps</option>
                            <option value="jumping">Jumping</option>
                            <option value="rubber">Rubber</option>
                        </select>
                    </div>

                    <!-- OPTION : Sticky Header -->
                    <h6 class="mt-4 mb-2 py-1">Header</h6>
                    <div class="d-flex align-items-center pt-1 pb-3 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-stickyHeaderCheckbox">Sticky
                            header</label>
                        <div class="form-check form-switch">
                            <input id="_dm-stickyHeaderCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Additional Offcanvas -->
                    {{-- <h6 class="mt-4 mb-2 py-1">Additional Offcanvas</h6> --}}
                    {{-- <p>Select the offcanvas placement.</p> --}}
                    {{-- <div class="text-nowrap"> --}}
                    {{-- <button type="button" class="_dm-offcanvasBtn btn btn-sm btn-primary" value="offcanvas-top">Top</button> --}}
                    {{-- <button type="button" class="_dm-offcanvasBtn btn btn-sm btn-primary" value="offcanvas-end">Right</button> --}}
                    {{-- <button type="button" class="_dm-offcanvasBtn btn btn-sm btn-primary" value="offcanvas-bottom">Btm</button> --}}
                    {{-- <button type="button" class="_dm-offcanvasBtn btn btn-sm btn-primary" value="offcanvas-start">Left</button> --}}
                    {{-- </div> --}}

                </div>
                <div class="col-lg-3 p-4 bg-body">

                    <h4 class="fw-bold pb-3 mb-2">Sidebars</h4>

                    <!-- OPTION : Sticky Navigation -->
                    <h6 class="mb-2 pb-1">Navigation</h6>
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-stickyNavCheckbox">Sticky
                            navigation</label>
                        <div class="form-check form-switch">
                            <input id="_dm-stickyNavCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Navigation Profile Widget -->
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-profileWidgetCheckbox">Widget
                            Profile</label>
                        <div class="form-check form-switch">
                            <input id="_dm-profileWidgetCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off" checked>
                        </div>
                    </div>

                    <!-- OPTION : Mini navigation mode -->
                    <div class="d-flex align-items-center pt-3 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-miniNavRadio">Min / Collapsed
                            Mode</label>
                        <div class="form-check form-switch">
                            <input id="_dm-miniNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Maxi navigation mode -->
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-maxiNavRadio">Max / Expanded
                            Mode</label>
                        <div class="form-check form-switch">
                            <input id="_dm-maxiNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode" autocomplete="off" checked>
                        </div>
                    </div>

                    <!-- OPTION : Push navigation mode -->
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-pushNavRadio">Push Mode</label>
                        <div class="form-check form-switch">
                            <input id="_dm-pushNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Slide on top navigation mode -->
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-slideNavRadio">Slide on top</label>
                        <div class="form-check form-switch">
                            <input id="_dm-slideNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Slide on top navigation mode -->
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-revealNavRadio">Reveal Mode</label>
                        <div class="form-check form-switch">
                            <input id="_dm-revealNavRadio" class="form-check-input ms-0" type="radio" name="navigation-mode" autocomplete="off">
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between gap-3 py-3">
                        <button class="nav-toggler btn btn-primary btn-sm" type="button">
                            Navigation
                        </button>
                        <button class="sidebar-toggler btn btn-primary btn-sm" type="button">
                            Sidebar
                        </button>
                    </div>

                    <h6 class="mt-3 mb-2 py-1">Sidebar</h6>

                    <!-- OPTION : Disable sidebar backdrop -->
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-disableBackdropCheckbox">Disable
                            backdrop</label>
                        <div class="form-check form-switch">
                            <input id="_dm-disableBackdropCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Static position -->
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-staticSidebarCheckbox">Static
                            position</label>
                        <div class="form-check form-switch">
                            <input id="_dm-staticSidebarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Stuck sidebar -->
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-stuckSidebarCheckbox">Stuck Sidebar
                        </label>
                        <div class="form-check form-switch">
                            <input id="_dm-stuckSidebarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Unite Sidebar -->
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-uniteSidebarCheckbox">Unite
                            Sidebar</label>
                        <div class="form-check form-switch">
                            <input id="_dm-uniteSidebarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Pinned Sidebar -->
                    <div class="d-flex align-items-start pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-pinnedSidebarCheckbox">Pinned
                            Sidebar</label>
                        <div class="form-check form-switch">
                            <input id="_dm-pinnedSidebarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                        </div>
                    </div>

                </div>
                <div class="col-lg-6 p-4">
                    <h4 class="fw-bold pb-3 mb-2">Colors</h4>

                    <div class="d-flex mb-4 pb-4">
                        <div class="d-flex flex-column">
                            <h5 class="h6">Modes</h5>
                            <div class="form-check form-check-alt form-switch">
                                <input id="settingsThemeToggler" class="form-check-input mode-switcher" type="checkbox" role="switch">
                                <label class="form-check-label ps-3 fw-bold d-none d-md-flex align-items-center " for="settingsThemeToggler">
                                    <i class="mode-switcher-icon icon-light demo-psi-sun fs-3"></i>
                                    <i class="mode-switcher-icon icon-dark d-none demo-psi-half-moon fs-5"></i>
                                </label>
                            </div>
                        </div>
                        <div class="vr mx-4"></div>
                        <div class="_dm-colorSchemesMode__colors">
                            <h5 class="h6">Color Schemes</h5>
                            <div id="dm_colorSchemesContainer" class="d-flex flex-wrap justify-content-center">
                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-gray" type="button" data-color="gray"></button>
                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-navy" type="button" data-color="navy"></button>
                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-ocean" type="button" data-color="ocean"></button>
                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-lime" type="button" data-color="lime"></button>

                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-violet" type="button" data-color="violet"></button>
                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-orange" type="button" data-color="orange"></button>
                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-teal" type="button" data-color="teal"></button>
                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-corn" type="button" data-color="corn"></button>

                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-cherry" type="button" data-color="cherry"></button>
                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-coffee" type="button" data-color="coffee"></button>
                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-pear" type="button" data-color="pear"></button>
                                <button class="_dm-colorSchemes _dm-box-xs _dm-bg-night" type="button" data-color="night"></button>
                            </div>
                        </div>
                    </div>

                    <div id="dm_colorModeContainer">
                        <div class="row text-center mb-2">

                            <!-- Expanded Header -->
                            <div class="col-md-4">
                                <h6 class="m-0">Expanded Header</h6>
                                <div class="_dm-colorShcemesMode">

                                    <!-- Scheme Button -->
                                    <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--expanded-hd">
                                        <img src="./assets/img/color-schemes/expanded-header.png" alt="color scheme illusttration" loading="lazy">
                                    </button>

                                </div>
                            </div>

                            <!-- Fair Header -->
                            <div class="col-md-4">
                                <h6 class="m-0">Fair Header</h6>
                                <div class="_dm-colorShcemesMode">

                                    <!-- Scheme Button -->
                                    <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--fair-hd">
                                        <img src="./assets/img/color-schemes/fair-header.png" alt="color scheme illusttration" loading="lazy">
                                    </button>

                                </div>
                            </div>

                            <div class="col-md-4">
                                <h6 class="m-0">Full Header</h6>

                                <div class="_dm-colorShcemesMode">

                                    <!-- Scheme Button -->
                                    <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--full-hd">
                                        <img src="./assets/img/color-schemes/full-header.png" alt="color scheme illusttration" loading="lazy">
                                    </button>

                                </div>
                            </div>
                        </div>

                        <div class="row text-center mb-2">
                            <div class="col-md-4">
                                <h6 class="m-0">Primary Nav</h6>

                                <div class="_dm-colorShcemesMode">

                                    <!-- Scheme Button -->
                                    <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--primary-mn">
                                        <img src="./assets/img/color-schemes/navigation.png" alt="color scheme illusttration" loading="lazy">
                                    </button>

                                </div>
                            </div>

                            <div class="col-md-4">
                                <h6 class="m-0">Brand</h6>

                                <div class="_dm-colorShcemesMode">

                                    <!-- Scheme Button -->
                                    <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--primary-brand">
                                        <img src="./assets/img/color-schemes/brand.png" alt="color scheme illusttration" loading="lazy">
                                    </button>

                                </div>
                            </div>

                            <div class="col-md-4">
                                <h6 class="m-0">Tall Header</h6>
                                <div class="_dm-colorShcemesMode">

                                    <!-- Scheme Button -->
                                    <button type="button" class="_dm-colorModeBtn btn p-1 shadow-none" data-color-mode="tm--tall-hd">
                                        <img src="./assets/img/color-schemes/tall-header.png" alt="color scheme illusttration" loading="lazy">
                                    </button>

                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="pt-3">

                        <h5 class="fw-bold mt-2">Miscellaneous</h5>

                        <div class="d-flex gap-3 my-3">
                            <label for="_dm-fontSizeRange" class="form-label flex-shrink-0 mb-0">Root
                                Font sizes</label>
                            <div class="position-relative flex-fill">
                                <input type="range" class="form-range" min="9" max="19" step="1" value="16" id="_dm-fontSizeRange">
                                <output id="_dm-fontSizeValue" class="range-bubble"></output>
                            </div>
                        </div>

                        <h5 class="fw-bold mt-4">Scrollbars</h5>
                        <p class="mb-2">Hides native scrollbars and creates
                            custom styleable overlay scrollbars.</p>
                        <div class="row">
                            <div class="col-5">

                                <!-- OPTION : Apply the OverlayScrollBar to the body. -->
                                <div class="d-flex align-items-center pt-1 mb-2">
                                    <label class="form-check-label flex-fill" for="_dm-bodyScrollbarCheckbox">Body
                                        scrollbar</label>
                                    <div class="form-check form-switch">
                                        <input id="_dm-bodyScrollbarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                                    </div>
                                </div>

                                <!-- OPTION : Apply the OverlayScrollBar to content containing class .scrollable-content. -->
                                <div class="d-flex align-items-center pt-1 mb-2">
                                    <label class="form-check-label flex-fill" for="_dm-sidebarsScrollbarCheckbox">Navigation
                                        and Sidebar</label>
                                    <div class="form-check form-switch">
                                        <input id="_dm-sidebarsScrollbarCheckbox" class="form-check-input ms-0" type="checkbox" autocomplete="off">
                                    </div>
                                </div>

                            </div>
                            <div class="col-7">

                                <div class="alert alert-warning mb-0" role="alert">
                                    Please consider the performance impact
                                    of using any scrollbar plugin.
                                </div>

                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <!-- END - SETTINGS CONTAINER [ DEMO ] -->

    <!-- OFFCANVAS [ DEMO ] -->
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <div id="_dm-offcanvas" class="offcanvas" tabindex="-1">

        <!-- Offcanvas header -->
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Offcanvas Header</h5>
            <button type="button" class="btn-close btn-lg text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <!-- Offcanvas content -->
        <div class="offcanvas-body">
            <h5>Content Here</h5>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                Sapiente eos nihil earum aliquam quod in dolor, aspernatur
                obcaecati et at. Dicta, ipsum aut, fugit nam dolore porro
                non est totam sapiente animi recusandae obcaecati dolorum,
                rem ullam cumque. Illum quidem reiciendis autem neque
                excepturi odit est accusantium, facilis provident molestias,
                dicta obcaecati itaque ducimus fuga iure in distinctio
                voluptate nesciunt dignissimos rem error a. Expedita
                officiis nam dolore dolores ea. Soluta repellendus delectus
                culpa quo. Ea tenetur impedit error quod exercitationem ut
                ad provident quisquam omnis! Nostrum quasi ex delectus vero,
                facilis aut recusandae deleniti beatae. Qui velit commodi
                inventore.</p>
        </div>

    </div>
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <!-- END - OFFCANVAS [ DEMO ] -->
    @push('scripts')
        @include('components.select.accountSelect')
        <script src="{{ asset('js/theme-settings.js') }}"></script>
        <script src="{{ asset('js/theme-settings-status.js') }}"></script>
        <script src="{{ asset('js/theme-settings-sync.js') }}"></script>
    @endpush
</x-app-layout>
