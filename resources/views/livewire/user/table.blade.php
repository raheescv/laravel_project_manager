<div>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Users</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Users 2</h1>
            <p class="lead">
                This page contains users information
            </p>

            <!-- Search form -->
            <div class="col-md-8 offset-md-2 mb-3">
                <div class="searchbox input-group">
                    <input class="searchbox__input form-control form-control-lg" autofocus wire:model.live="search" type="search" placeholder="Search users . . ." aria-label="Search">
                    <div class="searchbox__btn-group">
                        <button class="searchbox__btn btn btn-icon bg-transparent shadow-none border-0 btn-sm" type="submit">
                            <i class="demo-pli-magnifi-glass"></i>
                        </button>
                    </div>
                </div>
            </div>
            <!-- END : Search form -->
            <div class="d-md-flex align-items-baseline mt-3">
                <button type="button" class="btn btn-info hstack gap-2 mb-3">
                    <i class="demo-psi-add fs-4"></i>
                    <span class="vr"></span>
                    Add new
                </button>
                <div class="d-flex align-items-center gap-1 text-nowrap ms-auto mb-3">
                    <span class="d-none d-md-inline-block me-2">Sort by : </span>
                    <select class="d-inline-block w-auto form-select" wire:model.live="filter">
                        <option value="date-created" selected="">Date Created</option>
                        <option value="date-modified">Date Modified</option>
                        <option value="alphabetically">Alphabetically</option>
                        <option value="alphabetically-reversed">Alphabetically Reversed</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="row">
                @foreach ($data as $item)
                    <div class="col-sm-6 col-md-4 col-xl-3 mb-3">
                        <div class="card hv-grow">
                            <div class="card-body hv-outline-parent">
                                <div class="d-flex align-items-center position-relative pb-3">
                                    <div class="flex-shrink-0">
                                        <img class="hv-oc img-md rounded-circle" src="./assets/img/profile-photos/3.png" alt="Profile Picture" loading="lazy">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <a href="#" class="h5 stretched-link btn-link">{{ $item['name'] }}</a>
                                        <p class="text-body-secondary m-0">Marketing manager</p>
                                    </div>
                                </div>
                                <div class="mt-3 pt-2 text-center border-top">
                                    <div class="d-flex justify-content-center gap-3">
                                        <a href="#" class="btn btn-sm btn-hover btn-outline-light">
                                            <i class="d-block demo-pli-old-telephone fs-3 mb-2"></i> {{ $item['mobile'] }}
                                        </a>
                                        <a href="#" class="btn btn-sm btn-hover btn-outline-light">
                                            <i class="d-block demo-pli-mail fs-3 mb-2"></i> {{ $item['email'] }}
                                        </a>
                                        <a href="#" class="btn btn-sm btn-hover btn-outline-light">
                                            <i class="d-block demo-pli-pen-5 fs-3 mb-2"></i> Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
</div>
