<div>
    @can('sale.dashboard weekly summary')
        <div class="row g-3 mb-4">
            <!-- Weekly Sales Card -->
            <div class="col-sm-6 col-xl-3">
                <div class="card bg-indigo bg-gradient h-100 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                                <i class="fa fa-dollar text-white"></i>
                            </div>
                            <div>
                                <h3 class="card-title h5 mb-0 text-white">
                                    {{ currency($weeklySale ?? 0) }}
                                    <span class="trend-indicator badge ms-2 {{ $sale_percentage >= 0 ? 'bg-light text-success' : 'bg-light text-danger' }}">
                                        <i class="fa fa-{{ $sale_percentage >= 0 ? 'arrow-up' : 'arrow-down' }} me-1"></i>
                                        {{ abs(round($sale_percentage, 1)) }}%
                                    </span>
                                </h3>
                                <p class="card-text text-white-50 small mb-0">Weekly Sales</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Purchase Card -->
            <div class="col-sm-6 col-xl-3">
                <div class="card bg-teal bg-gradient h-100 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                                <i class="fa fa-cart-arrow-down text-white"></i>
                            </div>
                            <div>
                                <h3 class="card-title h5 mb-0 text-white">
                                    {{ currency($weeklyPurchase ?? 0) }}
                                    <span class="trend-indicator badge ms-2 {{ $purchase_percentage >= 0 ? 'bg-light text-success' : 'bg-light text-danger' }}">
                                        <i class="fa fa-{{ $purchase_percentage >= 0 ? 'arrow-up' : 'arrow-down' }} me-1"></i>
                                        {{ abs(round($purchase_percentage, 1)) }}%
                                    </span>
                                </h3>
                                <p class="card-text text-white-50 small mb-0">Weekly Purchase</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Expense Card -->
            <div class="col-sm-6 col-xl-3">
                <div class="card bg-purple bg-gradient h-100 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                                <i class="fa fa-bar-chart text-white"></i>
                            </div>
                            <div>
                                <h3 class="card-title h5 mb-0 text-white">
                                    {{ currency($weeklyExpense ?? 0) }}
                                    <span class="trend-indicator badge ms-2 {{ $expense_percentage >= 0 ? 'bg-light text-success' : 'bg-light text-danger' }}">
                                        <i class="fa fa-{{ $expense_percentage >= 0 ? 'arrow-up' : 'arrow-down' }} me-1"></i>
                                        {{ abs(round($expense_percentage, 1)) }}%
                                    </span>
                                </h3>
                                <p class="card-text text-white-50 small mb-0">Weekly Expense</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Income Card -->
            <div class="col-sm-6 col-xl-3">
                <div class="card bg-orange bg-gradient h-100 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                                <i class="fa fa-dollar text-white"></i>
                            </div>
                            <div>
                                <h3 class="card-title h5 mb-0 text-white">
                                    {{ currency($weeklyIncome ?? 0) }}
                                    <span class="trend-indicator badge ms-2 {{ $income_percentage >= 0 ? 'bg-light text-success' : 'bg-light text-danger' }}">
                                        <i class="fa fa-{{ $income_percentage >= 0 ? 'arrow-up' : 'arrow-down' }} me-1"></i>
                                        {{ abs(round($income_percentage, 1)) }}%
                                    </span>
                                </h3>
                                <p class="card-text text-white-50 small mb-0">Weekly Income</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('inventory.dashboard status')
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <!-- Header Section -->
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
                    <div class="mb-3 mb-md-0">
                        <h3 class="h4 mb-1 text-primary">Inventory Overview</h3>
                        <p class="text-muted mb-0">Total stock value and category distribution</p>
                    </div>
                    <a href="{{ route('inventory::index') }}" class="btn btn-primary btn-sm px-3">
                        <i class="fa fa-arrow-right me-1"></i> View Details
                    </a>
                </div>

                <!-- Main Stats Grid -->
                <div class="row g-4">
                    <!-- Total Stock Value Card -->
                    <div class="col-md-4">
                        <div class="card bg-cyan bg-gradient h-100 border-0">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                                        <i class="fa fa-cubes text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="h3 mb-1 text-white">{{ currency($stockCost, 0) }}</h4>
                                        <p class="text-white-50 small mb-0">Total Stock Value</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Stats -->
                    <div class="col-md-8">
                        <div class="row g-3">
                            <!-- Categories -->
                            <div class="col-sm-4">
                                <div class="card bg-pink bg-gradient h-100 border-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                                                <i class="fa fa-folder text-white"></i>
                                            </div>
                                            <div>
                                                <h4 class="h3 mb-1 text-white">{{ $category }}</h4>
                                                <p class="text-white-50 small mb-0">Categories</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Products -->
                            <div class="col-sm-4">
                                <div class="card bg-green-500 bg-gradient h-100 border-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                                                <i class="fa fa-shopping-cart text-white"></i>
                                            </div>
                                            <div>
                                                <h4 class="h3 mb-1 text-white">{{ $product }}</h4>
                                                <p class="text-white-50 small mb-0">Products</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Services -->
                            <div class="col-sm-4">
                                <div class="card bg-yellow-700 bg-gradient h-100 border-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                                                <i class="fa fa-cogs text-white"></i>
                                            </div>
                                            <div>
                                                <h4 class="h3 mb-1 text-white">{{ $service }}</h4>
                                                <p class="text-white-50 small mb-0">Services</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>
