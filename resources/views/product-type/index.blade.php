<x-app-layout>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Product Types</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Product Types</h1>
            <p class="lead">
                A table is an arrangement of information or data
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card mb-3">
                <div class="card-header -4 mb-3">
                    <h5 class="card-title mb-3">Table with
                        toolbar</h5>
                    <div class="row">

                        <!-- Left toolbar -->
                        <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                            <button class="btn btn-primary hstack gap-2 align-self-center">
                                <i class="demo-psi-add fs-5"></i>
                                <span class="vr"></span>
                                Add New
                            </button>
                            <button class="btn btn-icon btn-outline-light">
                                <i class="demo-pli-printer fs-5"></i>
                            </button>
                            <div class="btn-group">
                                <button class="btn btn-icon btn-outline-light"><i class="demo-pli-exclamation fs-5"></i></button>
                                <button class="btn btn-icon btn-outline-light"><i class="demo-pli-recycling fs-5"></i></button>
                            </div>
                        </div>
                        <!-- END : Left toolbar -->

                        <!-- Right Toolbar -->
                        <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                            <div class="form-group">
                                <input type="text" placeholder="Search..." class="form-control" autocomplete="off">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-icon btn-outline-light"><i class="demo-pli-download-from-cloud fs-5"></i></button>
                                <button class="btn btn-icon btn-outline-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle
                                        Dropdown</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#">Action</a></li>
                                    <li><a class="dropdown-item" href="#">Another
                                            action</a></li>
                                    <li><a class="dropdown-item" href="#">Something else
                                            here</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="#">Separated
                                            link</a></li>
                                </ul>
                            </div>
                        </div>
                        <!-- END : Right Toolbar -->

                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsove">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Steve N. Horton</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <nav class="text-align-center mt-5" aria-label="Table navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item disabled">
                                <a class="page-link">Previous</a>
                            </li>
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">1</span>
                            </li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item disabled"><a class="page-link" href="#">...</a></li>
                            <li class="page-item"><a class="page-link" href="#">5</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
