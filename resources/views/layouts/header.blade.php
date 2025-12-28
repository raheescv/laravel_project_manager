<header class="header">
    <div class="header__inner">
        <!-- Brand -->
        <div class="header__brand">
            <div class="brand-wrap">
                <a href="{{ route('dashboard') }}" class="brand-img stretched-link">
                    <img src="{{ cache('logo', asset('assets/img/logo.svg')) }}" alt="Nifty Logo" class="Nifty logo" width="150%" height="150%">
                </a>
                <div class="brand-title d-none d-sm-block">{{ config('app.name', 'Astra') }}</div>
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
                <div class="d-flex align-items-center px-3 py-1 rounded bg-light border text-dark shadow-sm">
                    <i class="fa fa-code-fork me-2 text-primary"></i>
                    <span class="fw-semibold small" id="branch_selection">
                        <span class="d-none d-sm-inline">Branch: </span>{{ session('branch_name') }}
                    </span>
                </div>
            </div>
            <!-- End - Content Header - Left Side -->

            <!-- Content Header - Right Side: -->
            <div class="header__content-end">
                <i class="fa fa-2x fa-arrows d-none d-md-inline-block" aria-hidden="true" id="btnFullscreen"></i>
                <div class="vr mx-1 d-none d-md-block"></div>
                <div class="dropdown">
                    <button class="header__btn btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Megamenu dropdown" aria-expanded="false">
                        <i class="fa fa-bar-chart"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-3 mega-dropdown">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="list-group list-group-borderless">
                                    <div class="list-group-item d-flex align-items-center border-bottom mb-2">
                                        <div class="flex-shrink-0 me-2">
                                            <i class="fa fa-file-text fs-4"></i>
                                        </div>
                                        <h5 class="flex-grow-1 m-0">Report</h5>
                                    </div>
                                    @can('report.customer')
                                        <a href="{{ route('report::customer') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-users fs-5 me-2"></i>Customer Report
                                        </a>
                                    @endcan
                                    @can('report.employee')
                                        <a href="{{ route('report::employee') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-user fs-5 me-2"></i>Employee Report
                                        </a>
                                    @endcan
                                    @can('report.sale summary')
                                        <a href="{{ route('report::sale_summary') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-file-text-o fs-5 me-2"></i>Sale Summary
                                        </a>
                                    @endcan
                                    @can('report.sales overview')
                                        <a href="{{ route('report::sales_overview') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-bar-chart fs-5 me-2"></i>Sales Overview
                                        </a>
                                    @endcan
                                    @can('report.sale and sales return items')
                                        <a href="{{ route('report::sale_mixed_items') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-bar-chart fs-5 me-2"></i>Sale & Sales Return Items
                                        </a>
                                    @endcan
                                    @can('report.sale calendar')
                                        <a href="{{ route('report::sale_calendar') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-calendar fs-5 me-2"></i>Sales Calendar
                                        </a>
                                    @endcan
                                    @can('report.profit loss')
                                        <a href="{{ route('report::profit_loss') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-usd fs-5 me-2"></i>Profit & Loss
                                        </a>
                                    @endcan
                                    @can('report.trial balance')
                                        <a href="{{ route('report::trial_balance') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-calculator fs-5 me-2"></i>Trial Balance
                                        </a>
                                    @endcan
                                    @can('report.balance sheet')
                                        <a href="{{ route('report::balance_sheet') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-file-o fs-5 me-2"></i>Balance Sheet
                                        </a>
                                    @endcan
                                    @can('report.stock analysis')
                                        <a href="{{ route('report::stock_analysis') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-cubes fs-5 me-2"></i>Stock Analysis
                                        </a>
                                    @endcan
                                    @can('report.employee productivity')
                                        <a href="{{ route('report::employee_productivity') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-trophy fs-5 me-2"></i>Employee Productivity
                                        </a>
                                    @endcan
                                    @can('report.customer callback reminder')
                                        <a href="{{ route('report::customer_callback_reminder') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-phone fs-5 me-2"></i>Customer Callback Reminder
                                        </a>
                                    @endcan
                                    @can('report.customer aging')
                                        <a href="{{ route('report::customer_aging') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-calendar fs-5 me-2"></i>Customer Aging
                                        </a>
                                    @endcan
                                    @can('report.day wise sale')
                                        <a href="{{ route('report::day_wise_sale') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-calendar fs-5 me-2"></i>Day Wise Sale Report
                                        </a>
                                    @endcan
                                    @can('report.vendor aging')
                                        <a href="{{ route('report::vendor_aging') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-calendar fs-5 me-2"></i>Vendor Aging
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="vr mx-1 d-none d-md-block"></div>
                <div class="dropdown">
                    <button class="header__btn btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Megamenu dropdown" aria-expanded="false">
                        <i class="fa fa-th"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-3 mega-dropdown">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="list-group list-group-borderless">
                                    <div class="list-group-item d-flex align-items-center border-bottom mb-2">
                                        <div class="flex-shrink-0 me-2">
                                            <i class="fa fa-cog fs-4"></i>
                                        </div>
                                        <h5 class="flex-grow-1 m-0">Settings</h5>
                                    </div>
                                    @can('customer.view')
                                        <a href="{{ route('account::customer::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-users fs-5 me-2"></i>Customers
                                        </a>
                                    @endcan
                                    @can('vendor.view')
                                        <a href="{{ route('account::vendor::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-truck fs-5 me-2"></i>Vendors
                                        </a>
                                    @endcan
                                    @can('product.view')
                                        <a href="{{ route('product::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-cubes fs-5 me-2"></i>Product
                                        </a>
                                    @endcan
                                    @can('service.view')
                                        <a href="{{ route('service::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-wrench fs-5 me-2"></i>Service
                                        </a>
                                    @endcan
                                    @can('combo offer.view')
                                        <a href="{{ route('combo_offer::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-gift fs-5 me-2"></i>Combo Offer
                                        </a>
                                    @endcan
                                    @can('branch.view')
                                        <a href="{{ route('settings::branch::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-building fs-5 me-2"></i>Branch
                                        </a>
                                    @endcan
                                    @can('category.view')
                                        <a href="{{ route('settings::category::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-folder fs-5 me-2"></i>Category
                                        </a>
                                    @endcan
                                    @can('account category.view')
                                        <a href="{{ route('settings::account_category::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-folder-open fs-5 me-2"></i>Account Category
                                        </a>
                                    @endcan
                                    @can('country.view')
                                        <a href="{{ route('settings::country::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-globe fs-5 me-2"></i>Country
                                        </a>
                                    @endcan
                                    @can('package category.view')
                                        <a href="{{ route('settings::package_category::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-globe fs-5 me-2"></i>Package Category
                                        </a>
                                    @endcan
                                    @can('customer type.view')
                                        <a href="{{ route('settings::customer_type::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-tags fs-5 me-2"></i>Customer Type
                                        </a>
                                    @endcan
                                    @can('unit.view')
                                        <a href="{{ route('settings::unit::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-cubes fs-5 me-2"></i>Unit
                                        </a>
                                    @endcan
                                    @can('department.view')
                                        <a href="{{ route('settings::department::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-sitemap fs-5 me-2"></i>Department
                                        </a>
                                    @endcan
                                    @can('brand.view')
                                        <a href="{{ route('settings::brand::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-certificate fs-5 me-2"></i>Brand
                                        </a>
                                    @endcan
                                    @can('configuration.barcode')
                                        <a href="{{ route('inventory::barcode::configuration') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-barcode fs-5 me-2"></i>Barcode Configuration
                                        </a>
                                    @endcan
                                    @can('configuration.cheque')
                                        <a href="{{ route('account::cheque::configuration') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-money-check fs-5 me-2"></i>Cheque Configuration
                                        </a>
                                    @endcan
                                    @can('api_log.moq settings')
                                        <a href="{{ route('api_log::moq-settings') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-cog fs-5 me-2"></i>Moq Settings
                                        </a>
                                    @endcan
                                    @can('configuration.settings')
                                        <a href="{{ route('settings::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-cogs fs-5 me-2"></i>Settings
                                        </a>
                                    @endcan
                                    @can('backup.view')
                                        <a href="{{ route('backup::index') }}" class="list-group-item list-group-item-action">
                                            <i class="fa fa-database fs-5 me-2"></i>Backup
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="header__btn btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-label="Notification dropdown" aria-expanded="false">
                        <span class="d-block position-relative">
                            <i class="fa fa-bell"></i>
                            <span class="badge badge-super rounded-pill bg-danger p-1">
                                <span class="visually-hidden">unread messages</span>
                            </span>
                            <span class="badge badge-super rounded-pill bg-danger p-1">
                                {{ auth()->user()->unreadNotifications()->count() }}
                                <span class="visually-hidden">unread messages</span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end w-md-300px">
                        <div class="border-bottom px-3 py-2 mb-3">
                            <h5>Notifications</h5>
                        </div>
                        <div class="list-group list-group-borderless">
                            @foreach (auth()->user()->unreadNotifications()->limit(5)->get() as $item)
                                <a href="{{ route('notification::index') }}" class="list-group-item list-group-item-action d-flex align-items-start mb-3 text-decoration-none">
                                    @switch($item['type'])
                                        @case('App\Notifications\ImportErrorsNotification')
                                            <div class="flex-shrink-0 me-3">
                                                <i class="fa fa-exclamation-triangle text-danger fs-2"></i>
                                            </div>
                                        @break

                                        @default
                                            <div class="flex-shrink-0 me-3">
                                                <i class="fa fa-info-circle text-success fs-2"></i>
                                            </div>
                                    @endswitch
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="h6 fw-normal mb-0">{{ $item['data']['title'] }}</span>
                                            @if (!$item['read_at'])
                                                <span class="badge bg-info rounded ms-auto">NEW</span>
                                            @endif
                                        </div>
                                        <small class="text-body-secondary">{{ $item['data']['message'] }}</small>
                                    </div>
                                </a>
                            @endforeach
                            <div class="text-center mb-2">
                                <a href="{{ route('notification::index') }}" class="btn-link text-primary icon-link icon-link-hover">
                                    Show all Notifications
                                    <i class="fa fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- User dropdown -->
                <div class="dropdown">
                    <!-- Toggler -->
                    <button class="header__btn btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-label="User dropdown" aria-expanded="false">
                        <i class="fa fa-user"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end w-md-450px">
                        <!-- User dropdown header -->
                        <div class="d-flex align-items-center border-bottom px-3 py-2">
                            <div class="flex-shrink-0">
                                <img class="img-sm rounded-circle" src="{{ secure_asset('assets/img/profile-photos/4.png') }}" alt="Profile Picture" loading="lazy">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-0">{{ auth()->user()->name }}</h5>
                                <span class="text-body-secondary fst-italic">{{ auth()->user()->email }}</span> <br>
                                <span class="text-body-secondary fst-italic">{{ getUserRoles(auth()->user()) }}</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-7">
                                <!-- Simple widget and reports -->
                                <div class="list-group list-group-borderless mb-3">
                                    <div class="list-group-item text-center border-bottom mb-3">
                                        <p class="h1 display-1 text-primary fw-semibold">17</p>
                                        <p class="h6 mb-0"><i class="fa fa-shopping-cart fs-3 me-2"></i> New orders</p>
                                        <small class="text-body-secondary">You have new orders</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">

                                <!-- User menu link -->
                                <div class="list-group list-group-borderless h-100 py-3">
                                    <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action">
                                        <i class="fa fa-user fs-5 me-2"></i>
                                        Profile
                                    </a>
                                    <a href="{{ route('settings::index') }}" class="list-group-item list-group-item-action">
                                        <i class="fa fa-cog fs-5 me-2"></i>
                                        Settings
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <a href="#" onclick="event.preventDefault(); this.closest('form').submit();" class="list-group-item list-group-item-action">
                                            <i class="fa fa-sign-out fs-5 me-2"></i>
                                            {{ __('Log Out') }}
                                        </a>
                                    </form>
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
                        <i class="mode-switcher-icon icon-light fa fa-sun fs-5"></i>
                        <i class="mode-switcher-icon icon-dark d-none fa fa-moon"></i>
                    </label>
                </div>

                <div class="vr mx-1 d-none d-md-block"></div>

                <!-- Sidebar Toggler -->
                <button class="sidebar-toggler header__btn btn btn-icon btn-sm" type="button" aria-label="Sidebar button">
                    <i class="fa fa-ellipsis-v"></i>
                </button>

            </div>
        </div>
    </div>
</header>
