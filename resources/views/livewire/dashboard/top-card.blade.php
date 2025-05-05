<div>
    @can('sale.dashboard weekly summary')
        <div class="row">
            <div class="col-sm-6">
                <div class="card bg-info text-white overflow-hidden mb-2">
                    <div class="p-3 pb-2">
                        <h5 class=""><i class="demo-psi-coin text-reset text-opacity-75 fs-2 me-2"></i> Earning</h5>
                        <ul class="list-group list-group-borderless">
                            <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                <div class="me-auto">Today</div>
                                <span class="fw-bold">{{ currency($todayPayment, 0) }}</span>
                            </li>
                            <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                <div class="me-auto">Last 7 Day</div>
                                <span class="fw-bold">{{ currency($weeklyPayment, 0) }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="py-0" style="height: 70px; margin: 0 -5px -5px;">
                        <canvas id="_dm-earningChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card bg-purple text-white overflow-hidden mb-2">
                    <div class="p-3 pb-2">
                        <h5 class=""><i class="demo-psi-basket-coins text-reset text-opacity-75 fs-2 me-2"></i>
                            Sales
                        </h5>
                        <ul class="list-group list-group-borderless">
                            <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                <div class="me-auto">Today</div>
                                <span class="fw-bold">{{ currency($todaySale, 0) }}</span>
                            </li>
                            <li class="list-group-item p-0 text-reset d-flex justify-content-between align-items-start">
                                <div class="me-auto">Last 7 Day</div>
                                <span class="fw-bold">{{ currency($weeklyPayment, 0) }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="py-0" style="height: 70px; margin: 0 -5px -5px;">
                        <canvas id="_dm-salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endcan
    @can('inventory.dashboard status')
        <div class="card mb-1">
            <div class="card-body text-center">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 p-3">
                        <div class="h display-3">{{ currency($stockCost, 0) }}</div>
                        <span class="h6">Total Stocks</span>
                    </div>
                    <div class="flex-grow-1 text-center ms-3">
                        <a href="{{ route('inventory::index') }}" class="btn btn-sm btn-danger">View Details</a>
                        <div class="mt-4 pt-3 d-flex justify-content-around border-top">
                            <div class="text-center">
                                <h4 class="mb-1">{{ $category }}</h4>
                                <small class="text-body-secondary">Category</small>
                            </div>
                            <div class="text-center">
                                <h4 class="mb-1">{{ $product }}</h4>
                                <small class="text-body-secondary">Products</small>
                            </div>
                            <div class="text-center">
                                <h4 class="mb-1">{{ $service }}</h4>
                                <small class="text-body-secondary">services</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>
