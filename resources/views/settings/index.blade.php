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
                A table is an arrangement of Product
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
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabsWhatsapp" type="button" role="tab" aria-controls="home" aria-selected="true">
                                    Whatsapp
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabsTheme" type="button" role="tab" aria-controls="profile" aria-selected="false" tabindex="-1">
                                    Theme
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div id="tabsWhatsapp" class="tab-pane fade active show" role="tabpanel" aria-labelledby="home-tab">
                                @livewire('settings.whatsapp')
                            </div>
                            <div id="tabsTheme" class="tab-pane fade" role="tabpanel" aria-labelledby="profile-tab">
                                <h5>Theme tab</h5>
                                <p class="mb-0">Far far away, behind the word mountains,</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
