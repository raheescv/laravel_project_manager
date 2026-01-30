<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings::index') }}">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tailoring Category</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Tailoring Category</h1>
            <p class="lead">
                Manage tailoring categories for your orders
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('settings.tailoring-category.table')

            <div class="card border-0 shadow-sm mt-4" id="tailoring-settings-tabs-card">
                <div class="card-header bg-white p-0">
                    <ul class="nav nav-tabs nav-fill border-bottom-0" id="tailoringSettingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold py-3 d-flex align-items-center justify-content-center gap-2 border-0 border-bottom border-3 border-transparent" id="models-tab" data-bs-toggle="tab" data-bs-target="#models-pane" type="button" role="tab" aria-controls="models-pane" aria-selected="true">
                                <i class="fa fa-cube text-primary"></i>
                                <span>Category Models</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-3 d-flex align-items-center justify-content-center gap-2 border-0 border-bottom border-3 border-transparent" id="measurements-tab" data-bs-toggle="tab" data-bs-target="#measurements-pane" type="button" role="tab" aria-controls="measurements-pane" aria-selected="false">
                                <i class="fa fa-tasks text-warning"></i>
                                <span>Measurements Settings</span>
                            </button>
                        </li>
                    </ul>
                </div>
                <style>
                    #tailoringSettingsTabs .nav-link {
                        color: #6c757d;
                        transition: all 0.2s ease;
                        background: none;
                    }
                    #tailoringSettingsTabs .nav-link:hover {
                        color: #0d6efd;
                    }
                    #tailoringSettingsTabs .nav-link.active {
                        color: #0d6efd;
                        background: #f8f9fa;
                        border-bottom-color: #0d6efd !important;
                    }
                    .tab-content-container {
                        min-height: 400px;
                    }
                </style>
                <div class="card-body p-0">
                    <div class="tab-content" id="tailoringSettingsTabsContent">
                        <div class="tab-pane fade show active" id="models-pane" role="tabpanel" aria-labelledby="models-tab" tabindex="0">
                            @livewire('settings.tailoring-category-model.table')
                        </div>
                        <div class="tab-pane fade" id="measurements-pane" role="tabpanel" aria-labelledby="measurements-tab" tabindex="0">
                            @livewire('settings.tailoring-category.measurements')
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <x-settings.tailoring-category.tailoring-category-modal />
    <x-settings.tailoring-category.tailoring-category-model-modal />
</x-app-layout>
