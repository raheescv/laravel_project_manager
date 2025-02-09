<aside class="sidebar">
    <div class="sidebar__inner scrollable-content">
        <div class="sidebar__stuck align-items-center mb-3 px-3">
            <button type="button" class="sidebar-toggler btn-close btn-lg rounded-circle" aria-label="Close"></button>
            <p class="m-0 text-danger fw-bold">&lt;= Close the sidebar</p>
        </div>
        <div class="sidebar__wrap">
            <nav>
                <div class="nav nav-underline nav-fill nav-component flex-nowrap border-bottom" id="nav-tab" role="tablist">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#nav-chat" type="button" role="tab" aria-controls="nav-chat" aria-selected="true">
                        <i class="d-block demo-pli-speech-bubble-5 fs-3 mb-2"></i>
                        <span>Chat</span>
                    </button>

                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#nav-reports" type="button" role="tab" aria-controls="nav-reports" aria-selected="false">
                        <i class="d-block demo-pli-information fs-3 mb-2"></i>
                        <span>Reports</span>
                    </button>

                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#nav-settings" type="button" role="tab" aria-controls="nav-settings" aria-selected="false">
                        <i class="d-block demo-pli-wrench fs-3 mb-2"></i>
                        <span>Settings</span>
                    </button>
                </div>
            </nav>
        </div>
        <div class="tab-content sidebar__wrap" id="nav-tabContent">
            <div id="nav-reports" class="tab-pane fade py-4" role="tabpanel" aria-labelledby="nav-reports-tab">
            </div>
            <div id="nav-settings" class="tab-pane fade py-4" role="tabpanel" aria-labelledby="nav-settings-tab">
            </div>
        </div>
    </div>
</aside>
