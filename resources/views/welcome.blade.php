<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-scheme="navy">

<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
    <meta name="description" content="Nifty is a responsive admin dashboard template based on Bootstrap 5 framework. There are a lot of useful components.">
    <title>Dashboard 1 | Nifty - Admin Template</title>

    <!-- STYLESHEETS -->
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->

    <!-- Fonts [ OPTIONAL ] -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS [ REQUIRED ] -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">

    <!-- Nifty CSS [ REQUIRED ] -->
    <link rel="stylesheet" href="{{ asset('assets/css/nifty.min.css') }}">

    <!-- Nifty Demo Icons [ OPTIONAL ] -->
    <link rel="stylesheet" href="{{ asset('assets/css/demo-purpose/demo-icons.min.css') }}">

    <!-- Demo purpose CSS [ DEMO ] -->
    <link rel="stylesheet" href="{{ asset('assets/css/demo-purpose/demo-settings.min.css') }}">

    <!-- Favicons [ OPTIONAL ] -->
    <link rel="apple-touch-icon" sizes="180x180" href="./apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./favicon-16x16.png">
    <link rel="manifest" href="./site.webmanifest">

    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

   [ REQUIRED ]
   You must include this category in your project.


   [ OPTIONAL ]
   This is an optional plugin. You may choose to include it in your project.


   [ DEMO ]
   Used for demonstration purposes only. This category should NOT be included in your project.


   [ SAMPLE ]
   Here's a sample script that explains how to initialize plugins and/or components: This category should NOT be included in your project.


   Detailed information and more samples can be found in the documentation.

   ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->

</head>

<body class="out-quart">

    <!-- PAGE CONTAINER -->
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <div id="root" class="root mn--max tm--expanded-hd">

        <!-- CONTENTS -->
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <section id="content" class="content">
            <div class="content__header content__boxed overlapping">
                <div class="content__wrap">

                    <!-- Page title and information -->
                    <h1 class="page-title mb-2">Dashboard</h1>
                    <h2 class="h5">Welcome back to the Dashboard.</h2>
                    <p>Scroll down to see quick links and overviews of your
                        Server, To do list<br> Order status or get some Help
                        using Nifty.</p>
                    <!-- END : Page title and information -->

                </div>

            </div>

            <div class="content__boxed">
                <div class="content__wrap">
                    <div class="row">
                        <div class="col-xl-7 mb-3 mb-xl-0">

                            <div class="card h-100">
                                <div class="card-header d-flex align-items-center border-0">
                                    <div class="me-auto">
                                        <h3 class="h4 m-0">Network</h3>
                                    </div>
                                    <div class="toolbar-end">
                                        <button type="button" class="btn btn-icon btn-sm btn-hover btn-light" aria-label="Refresh Network Chart">
                                            <i class="demo-pli-repeat-2 fs-5"></i>
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-icon btn-sm btn-hover btn-light" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Network dropdown">
                                                <i class="demo-pli-dot-horizontal fs-4"></i>
                                                <span class="visually-hidden">Toggle
                                                    Dropdown</span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="#" class="dropdown-item">
                                                        <i class="demo-pli-pen-5 fs-5 me-2"></i>
                                                        Edit Date
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="#" class="dropdown-item">
                                                        <i class="demo-pli-refresh fs-5 me-2"></i>
                                                        Refresh
                                                    </a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a href="#" class="dropdown-item">
                                                        <i class="demo-pli-file-csv fs-5 me-2"></i>
                                                        Save as CSV
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="#" class="dropdown-item">
                                                        <i class="demo-pli-calendar-4 fs-5 me-2"></i>
                                                        View Details
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Network - Area Chart -->
                                <div class="card-body py-0" style="height: 250px; max-height: 275px">
                                    <canvas id="_dm-networkChart"></canvas>
                                </div>
                                <!-- END : Network - Area Chart -->

                                <div class="card-body mt-4">
                                    <div class="row">
                                        <div class="col-md-8">

                                            <!-- CPU Temperature -->
                                            <h4 class="h5 mb-3">CPU
                                                Temperature</h4>
                                            <div class="row">
                                                <div class="col-5">
                                                    <div class="h5 display-6 fw-normal">
                                                        43.7 <span class="fw-bold fs-5 align-top">°C</span>
                                                    </div>
                                                </div>
                                                <div class="col-7 text-sm">
                                                    <div class="d-flex justify-content-between align-items-start px-3 mb-3">
                                                        Min Values
                                                        <span class="d-block badge bg-success ms-auto">27°</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-start px-3">
                                                        Max Values
                                                        <span class="d-block badge bg-danger ms-auto">89°</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- END : CPU Temperature -->

                                            <!-- Today Tips -->
                                            <div class="mt-4">
                                                <h5>Today Tips</h5>
                                                <p>Lorem ipsum dolor sit
                                                    amet, consectetuer
                                                    adipiscing elit, sed
                                                    diam nonummy nibh
                                                    euismod tincidunt.</p>
                                            </div>
                                            <!-- END : Today Tips -->

                                        </div>
                                        <div class="col-md-4">

                                            <!-- Bandwidth usage and progress bars -->
                                            <h4 class="h5 mb-3">Bandwidth
                                                Usage</h4>
                                            <div class="h2 fw-normal">
                                                754.9<span class="ms-2 fs-6 align-top">Mbps</span>
                                            </div>

                                            <div class="mt-4 mb-2 d-flex justify-content-between">
                                                <span class>Income</span>
                                                <span class>70%</span>
                                            </div>
                                            <div class="progress progress-md">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 70%" aria-label="Incoming Progress" aria-valuenow="70" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>

                                            <div class="mt-4 mb-2 d-flex justify-content-between">
                                                <span class>Outcome</span>
                                                <span class>10%</span>
                                            </div>
                                            <div class="progress progress-md">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: 10%" aria-label="Outcome Progress" aria-valuenow="10" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                            <!-- END : Bandwidth usage and progress bars -->

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-5">
                            <div class="row">
                                <div class="col-sm-6">

                                    <!-- Tile - HDD Usage -->
                                    <div class="card bg-success text-white overflow-hidden mb-3">
                                        <div class="p-3 pb-2">
                                            <h5 class="mb-3"><i class="demo-psi-data-storage text-reset text-opacity-75 fs-3 me-2"></i>
                                                HDD Usage</h5>
                                            <ul class="list-group list-group-borderless">
                                                <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                                    <div class="me-auto">Free
                                                        Space</div>
                                                    <span class="fw-bold">132Gb</span>
                                                </li>
                                                <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                                    <div class="me-auto">Used
                                                        space</div>
                                                    <span class="fw-bold">1,45Gb</span>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Area Chart -->
                                        <div class="py-0" style="height: 70px; margin: 0 -5px -5px;">
                                            <canvas id="_dm-hddChart"></canvas>
                                        </div>
                                        <!-- END : Area Chart -->

                                    </div>
                                    <!-- END : Tile - HDD Usage -->

                                </div>
                                <div class="col-sm-6">

                                    <!-- Tile - Earnings -->
                                    <div class="card bg-info text-white overflow-hidden mb-3">
                                        <div class="p-3 pb-2">
                                            <h5 class="mb-3"><i class="demo-psi-coin text-reset text-opacity-75 fs-2 me-2"></i>
                                                Earning</h5>
                                            <ul class="list-group list-group-borderless">
                                                <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                                    <div class="me-auto">Today</div>
                                                    <span class="fw-bold">$764</span>
                                                </li>
                                                <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                                    <div class="me-auto">Last
                                                        7 Day</div>
                                                    <span class="fw-bold">$1,332</span>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Line Chart -->
                                        <div class="py-0" style="height: 70px; margin: 0 -5px -5px;">
                                            <canvas id="_dm-earningChart"></canvas>
                                        </div>
                                        <!-- END : Line Chart -->

                                    </div>
                                    <!-- END : Tile - Earnings -->

                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">

                                    <!-- Tile - Sales -->
                                    <div class="card bg-purple text-white overflow-hidden mb-3">
                                        <div class="p-3 pb-2">
                                            <h5 class="mb-3"><i class="demo-psi-basket-coins text-reset text-opacity-75 fs-2 me-2"></i>
                                                Sales</h5>
                                            <ul class="list-group list-group-borderless">
                                                <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                                    <div class="me-auto">Today</div>
                                                    <span class="fw-bold">$764</span>
                                                </li>
                                                <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                                    <div class="me-auto">Last
                                                        7 Day</div>
                                                    <span class="fw-bold">$1,332</span>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Bar Chart -->
                                        <div class="py-0" style="height: 70px">
                                            <canvas id="_dm-salesChart"></canvas>
                                        </div>
                                        <!-- END : Bar Chart -->

                                    </div>
                                    <!-- END : Tile - Sales -->

                                </div>
                                <div class="col-sm-6">

                                    <!-- Tile - Task Progress -->
                                    <div class="card bg-warning text-white overflow-hidden mb-3">
                                        <div class="p-3 pb-2">
                                            <h5 class="mb-3"><i class="demo-psi-basket-coins text-reset text-opacity-75 fs-2 me-2"></i>
                                                Progress</h5>
                                            <ul class="list-group list-group-borderless">
                                                <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                                    <div class="me-auto">Completed</div>
                                                    <span class="fw-bold">34</span>
                                                </li>
                                                <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                                    <div class="me-auto">Total</div>
                                                    <span class="fw-bold">79</span>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Horizontal Bar Chart -->
                                        <div class="py-0 pb-2" style="height: 70px">
                                            <canvas id="_dm-taskChart"></canvas>
                                        </div>
                                        <!-- END : Horizontal Bar Chart -->

                                    </div>
                                    <!-- END : Tile - Task Progress -->

                                </div>
                            </div>

                            <!-- Simple state widget -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 p-3">
                                            <div class="h3 display-3">95</div>
                                            <span class="h6">New
                                                Friends</span>
                                        </div>
                                        <div class="flex-grow-1 text-center ms-3">
                                            <p class="text-body-secondary">Lorem
                                                ipsum dolor sit amet,
                                                consectetuer adipiscing
                                                elit.</p>
                                            <button class="btn btn-sm btn-danger">View
                                                Details</button>

                                            <!-- Social media statistics -->
                                            <div class="mt-4 pt-3 d-flex justify-content-around border-top">
                                                <div class="text-center">
                                                    <h4 class="mb-1">1,345</h4>
                                                    <small class="text-body-secondary">Following</small>
                                                </div>
                                                <div class="text-center">
                                                    <h4 class="mb-1">23k</h4>
                                                    <small class="text-body-secondary">Followers</small>
                                                </div>
                                                <div class="text-center">
                                                    <h4 class="mb-1">278</h4>
                                                    <small class="text-body-secondary">Posts</small>
                                                </div>
                                            </div>
                                            <!-- END : Social media statistics -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- END : Simple state widget -->

                        </div>
                    </div>

                </div>
            </div>

            <div class="bg-body-secondary bg-opacity-50 my-3 pt-3">
                <div class="content__boxed">
                    <div class="content__wrap">

                        <div class="row gx-5 gy-5 gy-md-0">
                            <div class="col-md-4">

                                <!-- TODO List -->
                                <h4 class="mb-3">To-do list</h4>
                                <ul class="list-group list-group-borderless">
                                    <li class="list-group-item px-0">
                                        <div class="form-check ">
                                            <input id="_dm-todoList1" class="form-check-input" type="checkbox" checked>
                                            <label for="_dm-todoList1" class="form-check-label text-decoration-line-through">
                                                Find an idea <span class="badge bg-danger text-decoration-line-through">Important</span>
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item px-0">
                                        <div class="form-check">
                                            <input id="_dm-todoList2" class="form-check-input" type="checkbox">
                                            <label for="_dm-todoList2" class="form-check-label">
                                                Do some work
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item px-0">
                                        <div class="form-check">
                                            <input id="_dm-todoList3" class="form-check-input" type="checkbox">
                                            <label for="_dm-todoList3" class="form-check-label">
                                                Read the book
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item px-0">
                                        <div class="form-check">
                                            <input id="_dm-todoList4" class="form-check-input" type="checkbox">
                                            <label for="_dm-todoList4" class="form-check-label">
                                                Upgrade server <span class="badge bg-warning">Warning</span>
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item px-0">
                                        <div class="form-check">
                                            <input id="_dm-todoList5" class="form-check-input" type="checkbox">
                                            <label for="_dm-todoList5" class="form-check-label">
                                                Redesign my logo <span class="badge bg-info">2
                                                    Mins</span>
                                            </label>
                                        </div>
                                    </li>
                                </ul>

                                <div class="input-group mt-3">
                                    <input type="text" class="form-control form-control-sm" placeholder="Add new list" aria-label="Add new list" aria-describedby="button-addon">
                                    <button id="button-addon" class="btn btn-sm btn-secondary hstack gap-2" type="button">
                                        <i class="demo-psi-add text-dark text-opacity-40 fs-4"></i>
                                        <span class="vr"></span>
                                        Add New
                                    </button>
                                </div>
                                <!-- END : TODO List -->

                            </div>
                            <div class="col-md-4">

                                <!-- Service options -->
                                <h4 class="mb-3">Services</h4>
                                <div class="list-group list-group-borderless">
                                    <div class="list-group-item px-0 mb-2">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-check-label h5 mb-0" for="_dm-dbPersonalStatus">Show
                                                my personal status</label>
                                            <div class="form-check form-switch">
                                                <input id="_dm-dbPersonalStatus" class="form-check-input" type="checkbox" checked>
                                            </div>
                                        </div>
                                        <span>Lorem ipsum dolor sit amet,
                                            consectetuer adipiscing
                                            elit.</span>
                                    </div>

                                    <div class="list-group-item px-0 mb-2">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-check-label h5 mb-0" for="_dm-dbOfflineContact">Show
                                                offline contact</label>
                                            <div class="form-check form-switch">
                                                <input id="_dm-dbOfflineContact" class="form-check-input" type="checkbox">
                                            </div>
                                        </div>
                                        <span>Aenean commodo ligula eget
                                            dolor. Aenean massa.</span>
                                    </div>

                                    <div class="list-group-item px-0 mb-2">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-check-label h5 mb-0" for="_dm-dbMuteNotifications">Mute
                                                notifications</label>
                                            <div class="form-check form-switch">
                                                <input id="_dm-dbMuteNotifications" class="form-check-input" type="checkbox">
                                            </div>
                                        </div>
                                        <span>Aenean commodo ligula eget
                                            dolor. Aenean massa.</span>
                                    </div>

                                    <div class="list-group-item px-0 mb-2">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-check-label h5 mb-0" for="_dm-dbInvisibleMode">Invisible
                                                Mode</label>
                                            <div class="form-check form-switch">
                                                <input id="_dm-dbInvisibleMode" class="form-check-input" type="checkbox" checked>
                                            </div>
                                        </div>
                                        <span>Nascetur ridiculus mus.</span>
                                    </div>
                                </div>
                                <!-- END : Service options -->

                            </div>
                            <div class="col-md-4">

                                <!-- User quote  -->
                                <div class="d-flex align-items-center position-relative hv-grow-parent hv-outline-parent">
                                    <div class="flex-shrink-0">
                                        <img class="hv-gc hv-oc img-lg rounded-circle" src="./assets/img/profile-photos/8.png" alt="Profile Picture" loading="lazy">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <a href="#" class="d-block stretched-link h5 link-offset-2-hover text-decoration-none link-underline-hover mb-0">Kathryn
                                            Obrien</a>
                                        Project manager
                                    </div>
                                </div>

                                <figure class="d-flex flex-column align-items-center justify-content-center my-4">
                                    <blockquote class="blockquote mb-0">
                                        <p class="quote">Lorem ipsum dolor
                                            sit amet, consecte tuer
                                            adipiscing elit, sed diam
                                            nonummy nibh euismod
                                            tincidunt</p>
                                    </blockquote>
                                </figure>

                                <div class="border-top pt-3">
                                    <a href="#" class="btn btn-icon btn-link text-indigo" aria-label="Facebook button">
                                        <i class="demo-psi-facebook fs-4"></i>
                                    </a>
                                    <a href="#" class="btn btn-icon btn-link text-info" aria-label="Twitter button">
                                        <i class="demo-psi-twitter fs-4"></i>
                                    </a>
                                    <a href="#" class="btn btn-icon btn-link text-red" aria-label="Google plus button">
                                        <i class="demo-psi-google-plus fs-4"></i>
                                    </a>
                                    <a href="#" class="btn btn-icon btn-link text-orange" aria-label="Instagram button">
                                        <i class="demo-psi-instagram fs-4"></i>
                                    </a>
                                </div>
                                <!-- END : User quote  -->

                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="content__boxed">
                <div class="content__wrap">

                    <!-- Table with toolbar -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-3">Order Status</h5>
                            <div class="row">

                                <!-- Left toolbar -->
                                <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                                    <button class="btn btn-primary hstack gap-2">
                                        <i class="demo-psi-add fs-5"></i>
                                        <span class="vr"></span>
                                        Add New
                                    </button>
                                    <button class="btn btn-icon btn-outline-light" aria-label="Print table">
                                        <i class="demo-pli-printer fs-5"></i>
                                    </button>
                                    <div class="btn-group">
                                        <button class="btn btn-icon btn-outline-light" aria-label="Information"><i class="demo-pli-exclamation fs-5"></i></button>
                                        <button class="btn btn-icon btn-outline-light" aria-label="Remove"><i class="demo-pli-recycling fs-5"></i></button>
                                    </div>
                                </div>
                                <!-- END : Left toolbar -->

                                <!-- Right Toolbar -->
                                <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                                    <div class="form-group">
                                        <input type="text" placeholder="Search..." class="form-control" autocomplete="off">
                                    </div>
                                    <div class="btn-group">
                                        <button class="btn btn-icon btn-outline-light" aria-label="Download"><i class="demo-pli-download-from-cloud fs-5"></i></button>
                                        <div class="btn-group dropdown">
                                            <button class="btn btn-icons btn-outline-light dropdown-toggle hstack gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                                                Options
                                                <span class="vr"></span>
                                                <span class="visually-hidden">Toggle
                                                    Dropdown</span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#">Action</a></li>
                                                <li><a class="dropdown-item" href="#">Another
                                                        action</a></li>
                                                <li><a class="dropdown-item" href="#">Something
                                                        else here</a></li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li><a class="dropdown-item" href="#">Separated
                                                        link</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <!-- END : Right Toolbar -->

                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>User</th>
                                            <th>Order date</th>
                                            <th>Amount</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Tracking
                                                Number</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><a href="#" class="btn-link"> Order
                                                    #53431</a></td>
                                            <td>Steve N. Horton</td>
                                            <td><span class="text-body"><i class="demo-pli-clock"></i>
                                                    May 22, 2024</span></td>
                                            <td>$45.00</td>
                                            <td class="text-center fs-5">
                                                <div class="d-block badge bg-success">Paid</div>
                                            </td>
                                            <td class="text-center">-</td>
                                        </tr>
                                        <tr>
                                            <td><a href="#" class="btn-link"> Order
                                                    #53432</a></td>
                                            <td>Charles S Boyle</td>
                                            <td><span class="text-body"><i class="demo-pli-clock"></i>
                                                    May 24, 2024</span></td>
                                            <td>$245.30</td>
                                            <td class="text-center fs-5">
                                                <div class="d-block badge bg-info">Shipped</div>
                                            </td>
                                            <td class="text-center">CGX0089734531</td>
                                        </tr>
                                        <tr>
                                            <td><a href="#" class="btn-link"> Order
                                                    #53433</a></td>
                                            <td>Lucy Doe</td>
                                            <td><span class="text-body"><i class="demo-pli-clock"></i>
                                                    May 24, 2024</span></td>
                                            <td>$38.00</td>
                                            <td class="text-center fs-5">
                                                <div class="d-block badge bg-info">Shipped</div>
                                            </td>
                                            <td class="text-center">CGX0089934571</td>
                                        </tr>
                                        <tr>
                                            <td><a href="#" class="btn-link"> Order
                                                    #53434</a></td>
                                            <td>Teresa L. Doe</td>
                                            <td><span class="text-body"><i class="demo-pli-clock"></i>
                                                    May 15, 2024</span></td>
                                            <td>$77.99</td>
                                            <td class="text-center fs-5">
                                                <div class="d-block badge bg-info">Shipped</div>
                                            </td>
                                            <td class="text-center">CGX0089734574</td>
                                        </tr>
                                        <tr>
                                            <td><a href="#" class="btn-link"> Order
                                                    #53435</a></td>
                                            <td>Teresa L. Doe</td>
                                            <td><span class="text-body"><i class="demo-pli-clock"></i>
                                                    May 12, 2024</span></td>
                                            <td>$18.00</td>
                                            <td class="text-center fs-5">
                                                <div class="d-block badge bg-success">Paid</div>
                                            </td>
                                            <td class="text-center">-</td>
                                        </tr>
                                        <tr>
                                            <td><a href="#" class="btn-link">Order
                                                    #53437</a></td>
                                            <td>Charles S Boyle</td>
                                            <td><span class="text-body"><i class="demo-pli-clock"></i>
                                                    May 17, 2024</span></td>
                                            <td>$658.00</td>
                                            <td class="text-center fs-5">
                                                <div class="d-block badge bg-danger">Refunded</div>
                                            </td>
                                            <td class="text-center">-</td>
                                        </tr>
                                        <tr>
                                            <td><a href="#" class="btn-link">Order
                                                    #536584</a></td>
                                            <td>Scott S. Calabrese</td>
                                            <td><span class="text-body"><i class="demo-pli-clock"></i>
                                                    May 19, 2024</span></td>
                                            <td>$45.58</td>
                                            <td class="text-center fs-5">
                                                <div class="d-block badge bg-warning">Unpaid</div>
                                            </td>
                                            <td class="text-center">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <nav class="text-align-center mt-5" aria-label="Table navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#">Previous</a>
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
                    <!-- END : Table with toolbar -->

                </div>
            </div>

            <!-- FOOTER -->

            <footer class="mt-auto">
                <div class="content__boxed">
                    <div class="content__wrap py-3 py-md-1 d-flex flex-column flex-md-row align-items-md-center">
                        <div class="text-nowrap mb-4 mb-md-0">Copyright
                            &copy; 2024 <a href="#" class="ms-1 btn-link fw-bold">My
                                Company</a></div>
                        <nav class="nav flex-column gap-1 flex-md-row gap-md-3 ms-md-auto">
                            <a class="nav-link link-offset-3 link-underline-hover px-0" href="#">Policy Privacy</a>
                            <a class="nav-link link-offset-3 link-underline-hover px-0" href="#">Terms and conditions</a>
                            <a class="nav-link link-offset-3 link-underline-hover px-0" href="#">Contact Us</a>
                        </nav>
                    </div>
                </div>
            </footer>

            <!-- END - FOOTER -->

        </section>

        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <!-- END - CONTENTS -->

        <!-- HEADER -->
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <header class="header">
            <div class="header__inner">

                <!-- Brand -->
                <div class="header__brand">
                    <div class="brand-wrap">

                        <!-- Brand logo -->
                        <a href="index.html" class="brand-img stretched-link">
                            <img src="./assets/img/logo.svg" alt="Nifty Logo" class="Nifty logo" width="16" height="16">
                        </a>

                        <!-- Brand title -->
                        <div class="brand-title">Nifty</div>

                        <!-- You can also use IMG or SVG instead of a text element. -->
                        <!--
            <div class="brand-title">
               <img src="./assets/img/brand-title.svg" alt="Brand Title">
            </div>
            -->

                    </div>
                </div>
                <!-- End - Brand -->

                <div class="header__content">

                    <!-- Content Header - Left Side: -->
                    <div class="header__content-start">

                        <!-- Navigation Toggler -->
                        <button type="button" class="nav-toggler header__btn btn btn-icon btn-sm" aria-label="Nav Toggler">
                            <i class="demo-psi-list-view"></i>
                        </button>

                        <div class="vr mx-1 d-none d-md-block"></div>

                        <!-- Searchbox -->
                        <div class="header-searchbox">

                            <!-- Searchbox toggler for small devices -->
                            <label for="header-search-input" class="header__btn d-md-none btn btn-icon rounded-pill shadow-none border-0 btn-sm" type="button">
                                <i class="demo-psi-magnifi-glass"></i>
                            </label>

                            <!-- Searchbox input -->
                            <form class="searchbox searchbox--auto-expand searchbox--hide-btn input-group">
                                <input id="header-search-input" class="searchbox__input form-control bg-transparent" type="search" placeholder="Type for search . . ." aria-label="Search">
                                <div class="searchbox__backdrop">
                                    <button class="searchbox__btn header__btn btn btn-icon rounded shadow-none border-0 btn-sm" type="button">
                                        <i class="demo-pli-magnifi-glass"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- End - Content Header - Left Side -->

                    <!-- Content Header - Right Side: -->
                    <div class="header__content-end">

                        <!-- Mega Dropdown -->
                        <div class="dropdown">

                            <!-- Toggler -->
                            <button class="header__btn btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Megamenu dropdown"
                                aria-expanded="false">
                                <i class="demo-psi-layout-grid"></i>
                            </button>

                            <!-- Mega Dropdown Menu -->
                            <div class="dropdown-menu dropdown-menu-end p-3 mega-dropdown">
                                <div class="row">
                                    <div class="col-md-3">

                                        <!-- Pages List Group -->
                                        <div class="list-group list-group-borderless">
                                            <div class="list-group-item d-flex align-items-center border-bottom mb-2">
                                                <div class="flex-shrink-0 me-2">
                                                    <i class="demo-pli-file fs-4"></i>
                                                </div>
                                                <h5 class="flex-grow-1 m-0">Pages</h5>
                                            </div>
                                            <a href="#" class="list-group-item list-group-item-action">Profile</a>
                                            <a href="#" class="list-group-item list-group-item-action">Search
                                                Result</a>
                                            <a href="#" class="list-group-item list-group-item-action">FAQ</a>
                                            <a href="#" class="list-group-item list-group-item-action">Screen
                                                Lock</a>
                                            <a href="#" class="list-group-item list-group-item-action">Maintenance</a>
                                            <a href="#" class="list-group-item list-group-item-action">Invoices</a>
                                            <a href="#" class="list-group-item list-group-item-action disabled" tabindex="-1" aria-disabled="true">Disabled
                                                Item</a>
                                        </div>

                                    </div>
                                    <div class="col-md-3">

                                        <!-- Mailbox list group -->
                                        <div class="list-group list-group-borderless mb-3">
                                            <div class="list-group-item d-flex align-items-center border-bottom mb-2">
                                                <div class="flex-shrink-0 me-2">
                                                    <i class="demo-pli-mail fs-4"></i>
                                                </div>
                                                <h5 class="flex-grow-1 m-0">Mailbox</h5>
                                            </div>
                                            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                Inbox <span class="badge bg-danger rounded-pill">14</span>
                                            </a>
                                            <a href="#" class="list-group-item list-group-item-action">Read
                                                Messages</a>
                                            <a href="#" class="list-group-item list-group-item-action">Compose</a>
                                            <a href="#" class="list-group-item list-group-item-action">Template</a>
                                        </div>

                                        <!-- News -->
                                        <div class="list-group list-group-borderless bg-warning-subtle py-2">
                                            <div class="list-group-item d-flex align-items-center border-bottom text-warning-emphasis">
                                                <div class="flex-shrink-0 me-2">
                                                    <i class="demo-pli-calendar-4 fs-4"></i>
                                                </div>
                                                <h5 class="flex-grow-1 m-0 text-reset">News</h5>
                                            </div>
                                            <small class="list-group-item text-warning-emphasis">
                                                Lorem ipsum dolor sit amet,
                                                consectetur adipisicing
                                                elit. Hic dolore unde autem,
                                                molestiae eum laborum
                                                aliquid at commodi cum?
                                                Blanditiis.
                                            </small>
                                        </div>

                                    </div>
                                    <div class="col-md-3">

                                        <!-- List Group -->
                                        <div class="list-group list-group-borderless">
                                            <div class="list-group-item list-group-item-action d-flex align-items-start mb-3">
                                                <div class="flex-shrink-0 me-3">
                                                    <i class="demo-pli-data-settings fs-1"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <a href="#" class="h5 d-block mb-0 stretched-link text-decoration-none">Data
                                                            Backup</a>
                                                        <span class="badge bg-success rounded-pill ms-auto">40%</span>
                                                    </div>
                                                    <small class="text-body-secondary">Current
                                                        usage of disks for
                                                        backups.</small>
                                                </div>
                                            </div>

                                            <div class="list-group-item list-group-item-action d-flex align-items-start mb-3">
                                                <div class="flex-shrink-0 me-3">
                                                    <i class="demo-pli-support fs-1"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="#" class="h5 d-block mb-0 stretched-link text-decoration-none">Support</a>
                                                    <small class="text-body-secondary">Have
                                                        any questions ?
                                                        please don't
                                                        hesitate to
                                                        ask.</small>
                                                </div>
                                            </div>

                                            <div class="list-group-item list-group-item-action d-flex align-items-start mb-3">
                                                <div class="flex-shrink-0 me-3">
                                                    <i class="demo-pli-computer-secure fs-1"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="#" class="h5 d-block mb-0 stretched-link text-decoration-none">Security</a>
                                                    <small class="text-body-secondary">Our
                                                        devices are secure
                                                        and
                                                        up-to-date.</small>
                                                </div>
                                            </div>

                                            <div class="list-group-item list-group-item-action d-flex align-items-start">
                                                <div class="flex-shrink-0 me-3">
                                                    <i class="demo-pli-map-2 fs-1"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="#" class="h5 d-block mb-0 stretched-link text-decoration-none">Location</a>
                                                    <small class="text-body-secondary">From
                                                        our location up
                                                        here, we kept in
                                                        close touch.</small>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-3">

                                        <!-- Simple gallery -->
                                        <div class="d-grid gap-2 pt-4 pt-md-0">
                                            <div class="row g-1 rounded-3 overflow-hidden">
                                                <div class="col-6 mt-0">
                                                    <img class="img-fluid" src="./assets/img/megamenu/img-1.jpg" alt="thumbnails" loading="lazy">
                                                </div>
                                                <div class="col-6 mt-0">
                                                    <img class="img-fluid" src="./assets/img/megamenu/img-3.jpg" alt="thumbnails" loading="lazy">
                                                </div>
                                                <div class="col-6">
                                                    <img class="img-fluid" src="./assets/img/megamenu/img-2.jpg" alt="thumbnails" loading="lazy">
                                                </div>
                                                <div class="col-6">
                                                    <img class="img-fluid" src="./assets/img/megamenu/img-4.jpg" alt="thumbnails" loading="lazy">
                                                </div>
                                            </div>
                                            <a href="#" class="btn btn-primary">Browse
                                                Gallery</a>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End - Mega Dropdown -->

                        <!-- Notification Dropdown -->
                        <div class="dropdown">

                            <!-- Toggler -->
                            <button class="header__btn btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-label="Notification dropdown" aria-expanded="false">
                                <span class="d-block position-relative">
                                    <i class="demo-psi-bell"></i>

                                    <span class="badge badge-super rounded-pill bg-danger p-1">
                                        <span class="visually-hidden">unread
                                            messages</span>
                                    </span>

                                    <!-- Set custom notification count -->
                                    <!--
                     <span class="badge badge-super rounded-pill bg-danger p-1">
                     19<span class="visually-hidden">unread messages</span>
                     </span>
                     -->

                                </span>
                            </button>

                            <!-- Notification dropdown menu -->
                            <div class="dropdown-menu dropdown-menu-end w-md-300px">
                                <div class="border-bottom px-3 py-2 mb-3">
                                    <h5>Notifications</h5>
                                </div>

                                <div class="list-group list-group-borderless">

                                    <!-- List item -->
                                    <div class="list-group-item list-group-item-action d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="demo-psi-data-settings text-danger fs-2"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <a href="#" class="h6 fw-normal d-block mb-0 stretched-link text-decoration-none">Your
                                                storage is full</a>
                                            <small class="text-body-secondary">Local
                                                storage is nearly
                                                full.</small>
                                        </div>
                                    </div>

                                    <!-- List item -->
                                    <div class="list-group-item list-group-item-action d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="demo-psi-pen-5 text-info fs-2"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <a href="#" class="h6 fw-normal d-block mb-0 stretched-link text-decoration-none">Writing
                                                a New Article</a>
                                            <small class="text-body-secondary">Wrote
                                                a news article for the John
                                                Mike</small>
                                        </div>
                                    </div>

                                    <!-- List item -->
                                    <div class="list-group-item list-group-item-action d-flex align-items-start mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="demo-psi-speech-bubble-3 text-success fs-2"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <a href="#" class="h6 fw-normal mb-0 stretched-link text-decoration-none">Comment
                                                    sorting</a>
                                                <span class="badge bg-info rounded ms-auto">NEW</span>
                                            </div>
                                            <small class="text-body-secondary">You
                                                have 1,256 unsorted
                                                comments.</small>
                                        </div>
                                    </div>

                                    <!-- List item -->
                                    <div class="list-group-item list-group-item-action d-flex align-items-start mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <img class="img-xs rounded-circle" src="./assets/img/profile-photos/7.png" alt="Profile Picture" loading="lazy">
                                        </div>
                                        <div class="flex-grow-1">
                                            <a href="#" class="h6 fw-normal d-block mb-0 stretched-link text-decoration-none">Lucy
                                                Sent you a message</a>
                                            <small class="text-body-secondary">30
                                                minutes ago</small>
                                        </div>
                                    </div>

                                    <!-- List item -->
                                    <div class="list-group-item list-group-item-action d-flex align-items-start mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <img class="img-xs rounded-circle" src="./assets/img/profile-photos/3.png" alt="Profile Picture" loading="lazy">
                                        </div>
                                        <div class="flex-grow-1">
                                            <a href="#" class="h6 fw-normal d-block mb-0 stretched-link text-decoration-none">Jackson
                                                Sent you a message</a>
                                            <small class="text-body-secondary">1
                                                hours ago</small>
                                        </div>
                                    </div>

                                    <div class="text-center mb-2">
                                        <a href="#" class="btn-link text-primary icon-link icon-link-hover">
                                            Show all Notifications
                                            <i class="bi demo-psi-arrow-out-right"></i>
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- End - Notification dropdown -->

                        <!-- User dropdown -->
                        <div class="dropdown">

                            <!-- Toggler -->
                            <button class="header__btn btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-label="User dropdown" aria-expanded="false">
                                <i class="demo-psi-male"></i>
                            </button>

                            <!-- User dropdown menu -->
                            <div class="dropdown-menu dropdown-menu-end w-md-450px">

                                <!-- User dropdown header -->
                                <div class="d-flex align-items-center border-bottom px-3 py-2">
                                    <div class="flex-shrink-0">
                                        <img class="img-sm rounded-circle" src="{{ asset('assets/img/profile-photos/4.png') }}" alt="Profile Picture" loading="lazy">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-0">Aaron Chavez</h5>
                                        <span class="text-body-secondary fst-italic">aaron_chavez@example.com</span>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-7">

                                        <!-- Simple widget and reports -->
                                        <div class="list-group list-group-borderless mb-3">
                                            <div class="list-group-item text-center border-bottom mb-3">
                                                <p class="h1 display-1 text-primary fw-semibold">17</p>
                                                <p class="h6 mb-0"><i class="demo-pli-basket-coins fs-3 me-2"></i>
                                                    New orders</p>
                                                <small class="text-body-secondary">You
                                                    have new orders</small>
                                            </div>
                                            <div class="list-group-item py-0 d-flex justify-content-between align-items-center">
                                                Today Earning
                                                <small class="fw-bolder">$578</small>
                                            </div>
                                            <div class="list-group-item py-0 d-flex justify-content-between align-items-center">
                                                Tax
                                                <small class="fw-bolder text-danger">-
                                                    $28</small>
                                            </div>
                                            <div class="list-group-item py-0 d-flex justify-content-between align-items-center">
                                                Total Earning
                                                <span class="fw-bolder text-body-emphasis">$6,578</span>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-5">

                                        <!-- User menu link -->
                                        <div class="list-group list-group-borderless h-100 py-3">
                                            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                <span><i class="demo-pli-mail fs-5 me-2"></i>
                                                    Messages</span>
                                                <span class="badge bg-danger rounded-pill">14</span>
                                            </a>
                                            <a href="#" class="list-group-item list-group-item-action">
                                                <i class="demo-pli-male fs-5 me-2"></i>
                                                Profile
                                            </a>
                                            <a href="#" class="list-group-item list-group-item-action">
                                                <i class="demo-pli-gear fs-5 me-2"></i>
                                                Settings
                                            </a>

                                            <a href="#" class="list-group-item list-group-item-action mt-auto">
                                                <i class="demo-pli-computer-secure fs-5 me-2"></i>
                                                Lock screen
                                            </a>
                                            <a href="#" class="list-group-item list-group-item-action">
                                                <i class="demo-pli-unlock fs-5 me-2"></i>
                                                Logout
                                            </a>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                        <!-- End - User dropdown -->

                        <div class="vr mx-1 d-none d-md-block"></div>

                        <div class="form-check form-check-alt form-switch mx-md-2">
                            <input id="headerThemeToggler" class="form-check-input mode-switcher" type="checkbox" role="switch">
                            <label class="form-check-label ps-1 fw-bold d-none d-md-flex align-items-center " for="headerThemeToggler">
                                <i class="mode-switcher-icon icon-light demo-psi-sun fs-5"></i>
                                <i class="mode-switcher-icon icon-dark d-none demo-psi-half-moon"></i>
                            </label>
                        </div>

                        <div class="vr mx-1 d-none d-md-block"></div>

                        <!-- Sidebar Toggler -->
                        <button class="sidebar-toggler header__btn btn btn-icon btn-sm" type="button" aria-label="Sidebar button">
                            <i class="demo-psi-dot-vertical"></i>
                        </button>

                    </div>
                </div>
            </div>
        </header>
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <!-- END - HEADER -->

        <!-- MAIN NAVIGATION -->
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <nav id="mainnav-container" class="mainnav">
            <div class="mainnav__inner">

                <!-- Navigation menu -->
                <div class="mainnav__top-content scrollable-content pb-5">

                    <!-- Profile Widget -->
                    <div id="_dm-mainnavProfile" class="mainnav__widget my-3 hv-outline-parent">

                        <!-- Profile picture  -->
                        <div class="mininav-toggle text-center py-2">
                            <img class="mainnav__avatar img-md rounded-circle hv-oc" src="./assets/img/profile-photos/1.png" alt="Profile Picture">
                        </div>

                        <div class="mininav-content collapse d-mn-max">
                            <span data-popper-arrow class="arrow"></span>
                            <div class="d-grid">

                                <!-- User name and position -->
                                <button class="mainnav-widget-toggle d-block btn border-0 p-2" data-bs-toggle="collapse" data-bs-target="#usernav" aria-expanded="false" aria-controls="usernav">
                                    <span class="dropdown-toggle d-flex justify-content-center align-items-center">
                                        <h5 class="mb-0 me-3">Aaron
                                            Chavez</h5>
                                    </span>
                                    <small class="text-body-secondary">Administrator</small>
                                </button>

                                <!-- Collapsed user menu -->
                                <div id="usernav" class="nav flex-column collapse">
                                    <a href="#" class="nav-link d-flex justify-content-between align-items-center">
                                        <span><i class="demo-pli-mail fs-5 me-2"></i><span class="ms-1">Messages</span></span>
                                        <span class="badge bg-danger rounded-pill">14</span>
                                    </a>
                                    <a href="#" class="nav-link">
                                        <i class="demo-pli-male fs-5 me-2"></i>
                                        <span class="ms-1">Profile</span>
                                    </a>
                                    <a href="#" class="nav-link">
                                        <i class="demo-pli-gear fs-5 me-2"></i>
                                        <span class="ms-1">Settings</span>
                                    </a>
                                    <a href="#" class="nav-link">
                                        <i class="demo-pli-computer-secure fs-5 me-2"></i>
                                        <span class="ms-1">Lock
                                            screen</span>
                                    </a>
                                    <a href="#" class="nav-link">
                                        <i class="demo-pli-unlock fs-5 me-2"></i>
                                        <span class="ms-1">Logout</span>
                                    </a>
                                </div>

                            </div>
                        </div>

                    </div>
                    <!-- End - Profile widget -->

                    <!-- Navigation Category -->
                    <div class="mainnav__categoriy py-3">
                        <h6 class="mainnav__caption mt-0 fw-bold">Navigation</h6>
                        <ul class="mainnav__menu nav flex-column">

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link active"><i class="demo-pli-home fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Dashboard</span>
                                </a>

                                <!-- Dashboard submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./index.html" class="nav-link active">Dashboard
                                            1</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./dashboard-2.html" class="nav-link">Dashboard 2</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./dashboard-3.html" class="nav-link">Dashboard 3</a>
                                    </li>

                                </ul>
                                <!-- END : Dashboard submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-split-vertical-2 fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Layouts</span>
                                </a>

                                <!-- Layouts submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./layouts-mini-navigation.html" class="nav-link">Mini
                                            Navigation</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./layouts-push-navigation.html" class="nav-link">Push
                                            Navigation</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./layouts-slide-navigation.html" class="nav-link">Slide
                                            Navigation</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./layouts-reveal-navigation.html" class="nav-link">Reveal
                                            Navigation</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./layouts-stuck-sidebar.html" class="nav-link">Stuck
                                            Sidebar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./layouts-pinned-sidebar.html" class="nav-link">Pinned
                                            Sidebar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./layouts-unite-sidebar.html" class="nav-link">Unite
                                            Sidebar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./layouts-sticky-header.html" class="nav-link">Sticky
                                            Header</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./layouts-sticky-navigation.html" class="nav-link">Sticky
                                            Navigation</a>
                                    </li>

                                </ul>
                                <!-- END : Layouts submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Regular menu link -->
                            <li class="nav-item">
                                <a href="./widgets.html" class="nav-link mininav-toggle"><i class="demo-pli-gear fs-5 me-2"></i>

                                    <span class="nav-label mininav-content ms-1">
                                        <span data-popper-arrow class="arrow"></span>
                                        Widgets
                                    </span>
                                </a>
                            </li>
                            <!-- END : Regular menu link -->

                        </ul>
                    </div>
                    <!-- END : Navigation Category -->

                    <!-- Components Category -->
                    <div class="mainnav__categoriy py-3">
                        <h6 class="mainnav__caption mt-0 fw-bold">Components</h6>
                        <ul class="mainnav__menu nav flex-column">

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-boot-2 fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Ui
                                        Elements</span>
                                </a>

                                <!-- Ui Elements submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./ui-elements-buttons.html" class="nav-link">Buttons</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./ui-elements-cards.html" class="nav-link">Cards</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./ui-elements-dropdowns.html" class="nav-link">Dropdowns</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./ui-elements-components.html" class="nav-link">Components</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./ui-elements-list-group.html" class="nav-link">List Group</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./ui-elements-typography.html" class="nav-link">Typography</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./ui-elements-modals.html" class="nav-link">Modals</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./ui-elements-progress.html" class="nav-link">Progress</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./ui-elements-placeholders.html" class="nav-link d-flex align-items-center">Placeholders<span class="badge bg-danger ms-auto">NEW</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./ui-elements-tabs-and-accordions.html" class="nav-link">Tabs &amp;
                                            Accordions</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./ui-elements-tooltips-and-popover.html" class="nav-link">Tooltips &amp;
                                            Popover</a>
                                    </li>

                                </ul>
                                <!-- END : Ui Elements submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-pen-5 fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Forms</span>
                                </a>

                                <!-- Forms submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./forms-general.html" class="nav-link">General</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./forms-tags.html" class="nav-link">Tags</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./forms-date-time-picker.html" class="nav-link">Date Time
                                            Picker</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./forms-validation.html" class="nav-link">Validation</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./forms-wizard.html" class="nav-link">Wizard</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./forms-file-uploads.html" class="nav-link">File
                                            Uploads</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./forms-text-editor.html" class="nav-link">Text Editor</a>
                                    </li>

                                </ul>
                                <!-- END : Forms submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-receipt-4 fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Tables</span>
                                </a>

                                <!-- Tables submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./tables-static-tables.html" class="nav-link">Static
                                            Tables</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./tables-gridjs.html" class="nav-link">Gridjs</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./tables-tabulator.html" class="nav-link">Tabulator</a>
                                    </li>

                                </ul>
                                <!-- END : Tables submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-bar-chart fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Charts</span>
                                </a>

                                <!-- Charts submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./charts-chartjs.html" class="nav-link">ChartJS</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./charts-chartscss.html" class="nav-link">ChartsCSS</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./charts-sparklines.html" class="nav-link">Sparklines</a>
                                    </li>

                                </ul>
                                <!-- END : Charts submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-repair fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Miscellaneous</span>
                                </a>

                                <!-- Miscellaneous submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./miscellaneous-timeline.html" class="nav-link">Timeline</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./miscellaneous-loader.css.html" class="nav-link">Loader.CSS</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./miscellaneous-spinkit.html" class="nav-link">Spinkit</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./miscellaneous-clipboard.html" class="nav-link">Clipboard</a>
                                    </li>

                                </ul>
                                <!-- END : Miscellaneous submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                        </ul>
                    </div>
                    <!-- END : Components Category -->

                    <!-- More Category -->
                    <div class="mainnav__categoriy py-3">
                        <h6 class="mainnav__caption mt-0 fw-bold">More</h6>
                        <ul class="mainnav__menu nav flex-column">

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-computer-secure fs-5 me-2"></i>
                                    <span class="nav-label ms-1">App
                                        Views</span>
                                </a>

                                <!-- App Views submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./app-views-file-manager.html" class="nav-link">File
                                            Manager</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./app-views-users.html" class="nav-link">Users</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./app-views-users-2.html" class="nav-link">Users 2</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./app-views-profile.html" class="nav-link">Profile</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./app-views-calendar.html" class="nav-link">Calendar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./app-views-taskboard.html" class="nav-link">Taskboard</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./app-views-chat.html" class="nav-link">Chat</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./app-views-contact-us.html" class="nav-link">Contact Us</a>
                                    </li>

                                </ul>
                                <!-- END : App Views submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-speech-bubble-5 fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Blog
                                        Apps</span>
                                </a>

                                <!-- Blog Apps submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./blog-apps-blog.html" class="nav-link">Blog</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./blog-apps-blog-list.html" class="nav-link">Blog List</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./blog-apps-blog-list-2.html" class="nav-link">Blog List 2</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./blog-apps-blog-article.html" class="nav-link">Blog
                                            Article</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./blog-apps-manage-posts.html" class="nav-link">Manage
                                            Posts</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./blog-apps-add-edit-posts.html" class="nav-link">Add Edit
                                            Posts</a>
                                    </li>

                                </ul>
                                <!-- END : Blog Apps submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-mail fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Email</span>
                                </a>

                                <!-- Email submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./email-inbox.html" class="nav-link">Inbox</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./email-view-message.html" class="nav-link">View
                                            Message</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./email-compose-message.html" class="nav-link">Compose
                                            Message</a>
                                    </li>

                                </ul>
                                <!-- END : Email submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-file-html fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Other
                                        Pages</span>
                                </a>

                                <!-- Other Pages submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./other-pages-blank-page.html" class="nav-link">Blank Page</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./other-pages-invoice.html" class="nav-link">Invoice</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./other-pages-search-results.html" class="nav-link">Search
                                            Results</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./other-pages-faq.html" class="nav-link">FAQ</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./other-pages-pricing-tables.html" class="nav-link">Pricing
                                            Tables</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./other-pages-error-404.html" class="nav-link">Error 404</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./other-pages-error-500.html" class="nav-link">Error 500</a>
                                    </li>

                                </ul>
                                <!-- END : Other Pages submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-window-2 fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Front
                                        Pages</span>
                                </a>

                                <!-- Front Pages submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./front-pages-error-404.html" class="nav-link">Error 404</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./front-pages-error-500.html" class="nav-link">Error 500</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./front-pages-maintenance.html" class="nav-link">Maintenance</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./front-pages-login.html" class="nav-link">Login</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./front-pages-register.html" class="nav-link">Register</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./front-pages-password-reminder.html" class="nav-link">Password
                                            Reminder</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./front-pages-lock-screen.html" class="nav-link">Lock Screen</a>
                                    </li>

                                </ul>
                                <!-- END : Front Pages submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-tactic fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Menu
                                        Levels</span>
                                </a>

                                <!-- Menu Levels submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">Menu
                                            Link</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">Menu
                                            Link</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">Menu
                                            Link</a>
                                    </li>
                                    <li class="nav-item has-sub">
                                        <a href="#" class="mininav-toggle nav-link collapsed">Submenu</a>
                                        <ul class="mininav-content nav collapse">
                                            <li data-popper-arrow class="arrow"></li>
                                            <li class="nav-item">
                                                <a href="#" class="nav-link">Menu
                                                    Link</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#" class="nav-link">Menu
                                                    Link</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#" class="nav-link">Menu
                                                    Link</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#" class="nav-link">Menu
                                                    Link</a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="nav-item has-sub">
                                        <a href="#" class="mininav-toggle nav-link collapsed">Submenu</a>
                                        <ul class="mininav-content nav collapse">
                                            <li data-popper-arrow class="arrow"></li>
                                            <li class="nav-item">
                                                <a href="#" class="nav-link">Menu
                                                    Link</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#" class="nav-link">Menu
                                                    Link</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#" class="nav-link">Menu
                                                    Link</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#" class="nav-link">Menu
                                                    Link</a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                                <!-- END : Menu Levels submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                        </ul>
                    </div>
                    <!-- END : More Category -->

                    <!-- Extras Category -->
                    <div class="mainnav__categoriy py-3">
                        <h6 class="mainnav__caption mt-0 fw-bold">Extras</h6>
                        <ul class="mainnav__menu nav flex-column">

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-happy fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Icons
                                        Pack</span>
                                </a>

                                <!-- Icons Pack submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./icons-pack-ionicons.html" class="nav-link">Ionicons</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./icons-pack-themify-icons.html" class="nav-link">Themify
                                            Icons</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./icons-pack-flag-icons.html" class="nav-link">Flag Icons</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./icons-pack-weather-icons.html" class="nav-link">Weather
                                            Icons</a>
                                    </li>

                                </ul>
                                <!-- END : Icons Pack submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-medal-2 fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Premium
                                        Icons</span>
                                </a>

                                <!-- Premium Icons submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./premium-icons-line-icons-pack.html" class="nav-link">Line Icons
                                            Pack</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./premium-icons-solid-icons-pack.html" class="nav-link">Solid Icons
                                            Pack</a>
                                    </li>

                                </ul>
                                <!-- END : Premium Icons submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                            <!-- Link with submenu -->
                            <li class="nav-item has-sub">

                                <a href="#" class="mininav-toggle nav-link collapsed"><i class="demo-pli-love fs-5 me-2"></i>
                                    <span class="nav-label ms-1">Helper
                                        Classes</span>
                                </a>

                                <!-- Helper Classes submenu list -->
                                <ul class="mininav-content nav collapse">
                                    <li data-popper-arrow class="arrow"></li>
                                    <li class="nav-item">
                                        <a href="./helper-classes-background.html" class="nav-link">Background</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./helper-classes-link.html" class="nav-link">Link</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./helper-classes-text.html" class="nav-link">Text</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./helper-classes-borders.html" class="nav-link">Borders</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./helper-classes-images.html" class="nav-link">Images</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./helper-classes-sizing.html" class="nav-link">Sizing</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="./helper-classes-more.html" class="nav-link">More</a>
                                    </li>

                                </ul>
                                <!-- END : Helper Classes submenu list -->

                            </li>
                            <!-- END : Link with submenu -->

                        </ul>
                    </div>
                    <!-- END : Extras Category -->

                    <!-- Widget -->
                    <div class="mainnav__widget">

                        <!-- Widget buttton form small navigation -->
                        <div class="mininav-toggle text-center py-2 d-mn-min">
                            <i class="demo-pli-monitor-2"></i>
                        </div>

                        <div class="d-mn-max mt-5"></div>

                        <!-- Widget content -->
                        <div class="mininav-content collapse d-mn-max">
                            <span data-popper-arrow class="arrow"></span>
                            <h6 class="mainnav__caption fw-bold">Server
                                Status</h6>
                            <ul class="list-group list-group-borderless">
                                <li class="list-group-item text-reset">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <p class="mb-2 me-auto">CPU
                                            Usage</p>
                                        <span class="badge bg-info rounded">35%</span>
                                    </div>
                                    <div class="progress progress-md">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 35%" aria-label="CPU Progress" aria-valuenow="35" aria-valuemin="0"
                                            aria-valuemax="100"></div>
                                    </div>
                                </li>
                                <li class="list-group-item text-reset">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <p class="mb-2 me-auto">Bandwidth</p>
                                        <span class="badge bg-warning rounded">73%</span>
                                    </div>
                                    <div class="progress progress-md">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 73%" aria-label="Bandwidth Progress" aria-valuenow="73" aria-valuemin="0"
                                            aria-valuemax="100"></div>
                                    </div>
                                </li>
                            </ul>
                            <div class="d-grid px-3 mt-3">
                                <a href="#" class="btn btn-sm btn-success">View
                                    Details</a>
                            </div>
                        </div>
                    </div>
                    <!-- End - Profile widget -->

                </div>
                <!-- End - Navigation menu -->

                <!-- Bottom navigation menu -->
                <div class="mainnav__bottom-content border-top pb-2">
                    <ul id="mainnav" class="mainnav__menu nav flex-column">
                        <li class="nav-item has-sub">
                            <a href="#" class="nav-link mininav-toggle collapsed" aria-expanded="false">
                                <i class="demo-pli-unlock fs-5 me-2"></i>
                                <span class="nav-label ms-1">Logout</span>
                            </a>
                            <ul class="mininav-content nav flex-column collapse">
                                <li data-popper-arrow class="arrow"></li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link">This device
                                        only</a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link">All
                                        Devices</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Lock screen</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <!-- End - Bottom navigation menu -->

            </div>
        </nav>
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <!-- END - MAIN NAVIGATION -->

        <!-- SIDEBAR -->
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <aside class="sidebar">
            <div class="sidebar__inner scrollable-content">

                <!-- This element is only visible when sidebar Stick mode is active. -->
                <div class="sidebar__stuck align-items-center mb-3 px-3">
                    <button type="button" class="sidebar-toggler btn-close btn-lg rounded-circle" aria-label="Close"></button>
                    <p class="m-0 text-danger fw-bold">&lt;= Close the
                        sidebar</p>
                </div>

                <!-- Sidebar tabs nav -->
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
                <!-- End - Sidebar tabs nav -->

                <!-- Sideabar tabs content -->
                <div class="tab-content sidebar__wrap" id="nav-tabContent">

                    <!-- Chat tab Content -->
                    <div id="nav-chat" class="tab-pane fade py-4 show active" role="tabpanel" aria-labelledby="nav-chat-tab">

                        <!-- Family list group -->
                        <h5 class="px-3">Family</h5>
                        <div class="list-group list-group-borderless">

                            <div class="list-group-item list-group-item-action d-flex align-items-start mb-2">
                                <div class="flex-shrink-0 me-3">
                                    <img class="img-xs rounded-circle" src="./assets/img/profile-photos/2.png" alt="Profile Picture" loading="lazy">
                                </div>
                                <div class="flex-grow-1 ">
                                    <a href="#" class="h6 d-block mb-0 stretched-link text-decoration-none">Stephen
                                        Tran</a>
                                    <small class="text-body-secondary">Available</small>
                                </div>
                            </div>

                            <div class="list-group-item list-group-item-action d-flex align-items-start mb-2">
                                <div class="flex-shrink-0 me-3">
                                    <img class="img-xs rounded-circle" src="./assets/img/profile-photos/8.png" alt="Profile Picture" loading="lazy">
                                </div>
                                <div class="flex-grow-1 ">
                                    <a href="#" class="h6 d-block mb-0 stretched-link text-decoration-none">Betty
                                        Murphy</a>
                                    <small class="text-body-secondary">Iddle</small>
                                </div>
                            </div>

                            <div class="list-group-item list-group-item-action d-flex align-items-start mb-2">
                                <div class="flex-shrink-0 me-3">
                                    <img class="img-xs rounded-circle" src="./assets/img/profile-photos/7.png" alt="Profile Picture" loading="lazy">
                                </div>
                                <div class="flex-grow-1 ">
                                    <a href="#" class="h6 d-block mb-0 stretched-link text-decoration-none">Brittany
                                        Meyer</a>
                                    <small class="text-body-secondary">I
                                        think so!</small>
                                </div>
                            </div>

                            <div class="list-group-item list-group-item-action d-flex align-items-start mb-2">
                                <div class="flex-shrink-0 me-3">
                                    <img class="img-xs rounded-circle" src="{{ asset('assets/img/profile-photos/4.png') }}" alt="Profile Picture" loading="lazy">
                                </div>
                                <div class="flex-grow-1 ">
                                    <a href="#" class="h6 d-block mb-0 stretched-link text-decoration-none">Jack
                                        George</a>
                                    <small class="text-body-secondary">Last
                                        seen 2 hours ago</small>
                                </div>
                            </div>

                        </div>
                        <!-- End - Family list group -->

                        <!-- Friends Group -->
                        <h5 class="d-flex mt-5 px-3">Friends <span class="badge bg-success ms-auto">587
                                +</span></h5>
                        <div class="list-group list-group-borderless">
                            <a href="#" class="list-group-item list-group-item-action">
                                <span class="d-inline-block bg-success rounded-circle p-1 me-2"></span>
                                Joey K. Greyson
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <span class="d-inline-block bg-info rounded-circle p-1 me-2"></span>
                                Andrea Branden
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <span class="d-inline-block bg-warning rounded-circle p-1 me-2"></span>
                                Johny Juan
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <span class="d-inline-block bg-secondary rounded-circle p-1 me-2"></span>
                                Susan Sun
                            </a>
                        </div>
                        <!-- End - Friends Group -->

                        <!-- Simple news widget -->
                        <div class="p-3 mt-5 rounded bg-info-subtle text-info-emphasis">
                            <h5 class="text-info-emphasis">News</h5>
                            <p>Lorem ipsum, dolor sit amet consectetur
                                adipisicing elit. Qui consequatur ipsum
                                porro a repellat eaque exercitationem
                                necessitatibus esse voluptate corporis.</p>
                            <small class="fst-italic">Last Update : Today
                                13:54</small>
                        </div>
                        <!-- End - Simple news widget -->

                    </div>
                    <!-- End - Chat tab content -->

                    <!-- Reports tab content -->
                    <div id="nav-reports" class="tab-pane fade py-4" role="tabpanel" aria-labelledby="nav-reports-tab">

                        <!-- Billing and Resports -->
                        <div class="px-3">
                            <h5 class="mb-3">Billing &amp; Reports</h5>
                            <p>Get <span class="badge bg-danger">$15.00
                                    off</span> your next bill by making sure
                                your full payment reaches us before August
                                5th.</p>

                            <h5 class="mt-5 mb-0">Amount Due On</h5>
                            <p>August 17, 2028</p>
                            <p class="h1">$83.09</p>

                            <div class="d-grid">
                                <button class="btn btn-success" type="button">Pay now</button>
                            </div>
                        </div>
                        <!-- End - Billing and Resports -->

                        <!-- Additional actions nav -->
                        <h5 class="mt-5 px-3">Additional Actions</h5>
                        <div class="list-group list-group-borderless">
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="demo-pli-information me-2 fs-5"></i>
                                Services Information
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="demo-pli-mine me-2 fs-5"></i>
                                Usage
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="demo-pli-credit-card-2 me-2 fs-5"></i>
                                Payment Options
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="demo-pli-support me-2 fs-5"></i>
                                Messages Center
                            </a>
                        </div>
                        <!-- End - Additional actions nav -->

                        <!-- Contact widget -->
                        <div class="px-3 mt-5 text-center">
                            <div class="mb-3">
                                <i class="demo-pli-old-telephone display-4 text-primary"></i>
                            </div>
                            <p>Have a question ?</p>
                            <p class="h5 mb-0"> (415) 234-53454 </p>
                            <small><em>We are here 24/7</em></small>
                        </div>
                        <!-- End - Contact widget -->

                    </div>
                    <!-- End - Reports tab content -->

                    <!-- Settings content -->
                    <div id="nav-settings" class="tab-pane fade py-4" role="tabpanel" aria-labelledby="nav-settings-tab">

                        <!-- Account settings -->
                        <h5 class="px-3">Account Settings</h5>
                        <div class="list-group list-group-borderless">

                            <div class="list-group-item mb-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <label class="form-check-label text-body-emphasis stretched-link" for="_dm-sbPersonalStatus">Show my
                                        personal status</label>
                                    <div class="form-check form-switch">
                                        <input id="_dm-sbPersonalStatus" class="form-check-input" type="checkbox" checked>
                                    </div>
                                </div>
                                <small class="text-body-secondary">Lorem
                                    ipsum dolor sit amet, consectetuer
                                    adipiscing elit.</small>
                            </div>

                            <div class="list-group-item mb-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <label class="form-check-label text-body-emphasis stretched-link" for="_dm-sbOfflineContact">Show
                                        offline contact</label>
                                    <div class="form-check form-switch">
                                        <input id="_dm-sbOfflineContact" class="form-check-input" type="checkbox">
                                    </div>
                                </div>
                                <small class="text-body-secondary">Aenean
                                    commodo ligula eget dolor. Aenean
                                    massa.</small>
                            </div>

                            <div class="list-group-item mb-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <label class="form-check-label text-body-emphasis stretched-link" for="_dm-sbInvisibleMode">Invisible
                                        Mode</label>
                                    <div class="form-check form-switch">
                                        <input id="_dm-sbInvisibleMode" class="form-check-input" type="checkbox">
                                    </div>
                                </div>
                                <small class="text-body-secondary">Cum
                                    sociis natoque penatibus et magnis dis
                                    parturient montes, nascetur ridiculus
                                    mus.</small>
                            </div>

                        </div>
                        <!-- End - Account settings -->

                        <!-- Public Settings -->
                        <h5 class="mt-5 px-3">Public Settings</h5>
                        <div class="list-group list-group-borderless">

                            <div class="list-group-item d-flex justify-content-between mb-1">
                                <label class="form-check-label" for="_dm-sbOnlineStatus">Online
                                    Status</label>
                                <div class="form-check form-switch">
                                    <input id="_dm-sbOnlineStatus" class="form-check-input" type="checkbox" checked>
                                </div>
                            </div>

                            <div class="list-group-item d-flex justify-content-between mb-1">
                                <label class="form-check-label" for="_dm-sbMuteNotifications">Mute
                                    Notifications</label>
                                <div class="form-check form-switch">
                                    <input id="_dm-sbMuteNotifications" class="form-check-input" type="checkbox" checked>
                                </div>
                            </div>

                            <div class="list-group-item d-flex justify-content-between mb-1">
                                <label class="form-check-label" for="_dm-sbMyDevicesName">Show my device
                                    name</label>
                                <div class="form-check form-switch">
                                    <input id="_dm-sbMyDevicesName" class="form-check-input" type="checkbox" checked>
                                </div>
                            </div>

                        </div>
                        <!-- End - Public Settings -->

                    </div>
                    <!-- End - Settings content -->

                </div>
                <!-- End - Sidebar tabs content -->

            </div>
        </aside>
        <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
        <!-- END - SIDEBAR -->

    </div>
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <!-- END - PAGE CONTAINER -->

    <!-- SCROLL TO TOP BUTTON -->
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <div class="scroll-container">
        <a href="#root" class="scroll-page ratio ratio-1x1" aria-label="Scroll button"></a>
    </div>
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <!-- END - SCROLL TO TOP BUTTON -->

    <!-- BOXED LAYOUT : BACKGROUND IMAGES CONTENT [ DEMO ] -->
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <div id="_dm-boxedBgContent" class="_dm-boxbg offcanvas offcanvas-bottom" data-bs-scroll="true" tabindex="-1">
        <div class="offcanvas-body px-4">

            <!-- Content Header -->
            <div class="offcanvas-header border-bottom p-0 pb-3">
                <div>
                    <h4 class="offcanvas-title">Background Images</h4>
                    <span class="text-body-secondary">Add an image to
                        replace the solid background color</span>
                </div>
                <button type="button" class="btn-close btn-lg text-reset shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <!-- End - Content header -->

            <!-- Collection Of Images -->
            <div id="_dm-boxedBgContainer" class="row mt-3">

                <!-- Blurred Background Images -->
                <div class="col-lg-4">
                    <h5 class="mb-2">Blurred</h5>
                    <div class="_dm-boxbg__img-container d-flex flex-wrap">
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/1.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/2.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/3.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/4.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/5.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/6.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/7.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/8.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/9.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/10.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/11.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/12.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/13.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/14.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/15.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/blurred/thumbs/16.jpg" alt="Background Image" loading="lazy">
                        </a>
                    </div>
                </div>
                <!-- End - Blurred Background Images -->

                <!-- Polygon Background Images -->
                <div class="col-lg-4">
                    <h5 class="mb-2">Polygon &amp; Geometric</h5>
                    <div class="_dm-boxbg__img-container d-flex flex-wrap mb-4">
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/1.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/2.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/3.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/4.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/5.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/6.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/7.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/8.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/9.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/10.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/11.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/12.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/13.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/14.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/15.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/polygon/thumbs/16.jpg" alt="Background Image" loading="lazy">
                        </a>
                    </div>
                </div>
                <!-- End - Polygon Background Images -->

                <!-- Abstract Background Images -->
                <div class="col-lg-4">
                    <h5 class="mb-2">Abstract</h5>
                    <div class="_dm-boxbg__img-container d-flex flex-wrap">
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/1.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/2.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/3.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/4.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/5.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/6.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/7.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/8.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/9.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/10.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/11.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/12.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/13.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/14.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/15.jpg" alt="Background Image" loading="lazy">
                        </a>
                        <a href="#" class="_dm-boxbg__thumb ratio ratio-16x9">
                            <img class="img-responsive" src="./assets/premium/boxed-bg/abstract/thumbs/16.jpg" alt="Background Image" loading="lazy">
                        </a>
                    </div>
                </div>
                <!-- End - Abstract Background Images -->

            </div>
            <!-- End - Collection Of Images -->

        </div>
    </div>
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <!-- END - BOXED LAYOUT : BACKGROUND IMAGES CONTENT [ DEMO ] -->

    <!-- SETTINGS CONTAINER [ DEMO ] -->
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->
    <div id="_dm-settingsContainer" class="_dm-settings-container offcanvas offcanvas-end rounded-start" tabindex="-1">
        <button id="_dm-settingsToggler" class="_dm-btn-settings btn btn-sm btn-danger p-2 rounded-0 rounded-start shadow-none" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#_dm-settingsContainer" aria-label="Customization button" aria-controls="#_dm-settingsContainer">
            <i class="demo-psi-gear fs-1"></i>
        </button>

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
                    <div class="d-flex align-items-center pt-1 mb-2">
                        <label class="form-check-label flex-fill" for="_dm-boxedLayoutRadio">Boxed Layout</label>
                        <div class="form-check form-switch">
                            <input id="_dm-boxedLayoutRadio" class="form-check-input ms-0" type="radio" name="settingLayouts" autocomplete="off">
                        </div>
                    </div>

                    <!-- OPTION : Boxed layout with background images -->
                    <div id="_dm-boxedBgOption" class="opacity-50 d-flex align-items-center pt-1 mb-2">
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
                    <h6 class="mt-4 mb-2 py-1">Additional Offcanvas</h6>
                    <p>Select the offcanvas placement.</p>
                    <div class="text-nowrap">
                        <button type="button" class="_dm-offcanvasBtn btn btn-sm btn-primary" value="offcanvas-top">Top</button>
                        <button type="button" class="_dm-offcanvasBtn btn btn-sm btn-primary" value="offcanvas-end">Right</button>
                        <button type="button" class="_dm-offcanvasBtn btn btn-sm btn-primary" value="offcanvas-bottom">Btm</button>
                        <button type="button" class="_dm-offcanvasBtn btn btn-sm btn-primary" value="offcanvas-start">Left</button>
                    </div>

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

    <!-- JAVASCRIPTS -->
    <!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->

    <!-- Popper JS [ OPTIONAL ] -->
    <script src="{{ asset('assets/vendors/popperjs/popper.min.js') }}"></script>

    <!-- Bootstrap JS [ OPTIONAL ] -->
    <script src="{{ asset('assets/vendors/bootstrap/bootstrap.min.js') }}"></script>

    <!-- Nifty JS [ OPTIONAL ] -->
    <script src="{{ asset('assets/js/nifty.js') }}"></script>

    <!-- Nifty Settings [ DEMO ] -->
    <script src="{{ asset('assets/js/demo-purpose-only.js') }}"></script>

    <!-- Chart JS Scripts [ OPTIONAL ] -->
    <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>

    <!-- Initialize [ SAMPLE ] -->
    <script src="{{ asset('assets/pages/dashboard-1.js') }}"></script>

</body>

</html>
