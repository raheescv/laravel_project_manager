<div class="card shadow-sm border-0">
    <!-- Header Section -->
    <div class="card-header bg-primary text-white py-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="demo-psi-search fs-4"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="d-flex align-items-center justify-content-md-end gap-3">
                    <div class="text-center">
                        <div class="text-white-50 small">Total Products</div>
                        <div class="fs-4 fw-bold">{{ $totalProducts }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-white-50 small">In Stock</div>
                        <div class="fs-4 fw-bold text-success">{{ $inStockCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters Section -->
    <div class="card-body bg-light">
        <div class="row g-3 align-items-end">
            <!-- Product Name Filter -->
            <div class="col-md-7">
                <label class="form-label fw-semibold mb-2">
                    <i class="demo-psi-user me-1 text-warning"></i> Product Name
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="demo-psi-tag text-muted"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="productName" class="form-control border-start-0" placeholder="Enter product name..." autocomplete="off">
                </div>
            </div>
            <!-- Product Code Filter -->
            <div class="col-md-3">
                <label class="form-label fw-semibold mb-2">
                    <i class="demo-psi-tag me-1 text-info"></i> Product Code
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="demo-psi-tag text-muted"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="productCode" class="form-control border-start-0" placeholder="Enter product code..." autocomplete="off">
                </div>
            </div>

            <!-- Branch Filter -->
            <div class="col-md-2" wire:ignore>
                <label class="form-label fw-semibold mb-2">
                    <i class="demo-psi-building me-1 text-success"></i> Branch
                </label>
                <select wire:model.live="selectedBranch" class="form-select">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Filter Actions -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-2">
                        <button wire:click="clearFilters" class="btn btn-outline-secondary btn-sm">
                            <i class="demo-psi-close me-1"></i> Clear Filters
                        </button>
                        @if ($loading)
                            <div class="d-flex align-items-center text-muted">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <small>Searching...</small>
                            </div>
                        @endif
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <label class="form-label mb-0 me-2 small">Show:</label>
                        <select wire:model.live="limit" class="form-select form-select-sm" style="width: auto;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="card-body p-0">
        @if (count($products) > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">
                                <i class="demo-psi-building me-1 text-muted"></i> Branch
                            </th>
                            <th class="border-0">
                                <i class="demo-psi-box me-1 text-muted"></i> Product Name
                            </th>
                            <th class="border-0">
                                <i class="demo-psi-box me-1 text-muted"></i> Brand
                            </th>
                            <th class="border-0 text-end">
                                <i class="demo-psi-tag me-1 text-muted"></i> Code
                            </th>
                            <th class="border-0">
                                <i class="demo-psi-ruler me-1 text-muted"></i> Size
                            </th>
                            <th class="border-0 text-end">
                                <i class="demo-psi-inbox me-1 text-muted"></i> Quantity
                            </th>
                            <th class="border-0 text-end">
                                <i class="demo-psi-money me-1 text-muted"></i> Price
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $item)
                            <tr class="align-middle">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                                            <i class="demo-psi-building text-primary"></i>
                                        </div>
                                        <span class="fw-medium">{{ $item->branch->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="fw-medium">{{ $item->product->name }} </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="fw-medium">{{ $item->product->brand }} </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <code class="text-primary">{{ $item->product->code }}</code>
                                </td>
                                <td>
                                    @if ($item->product->size)
                                        <span class="badge bg-light text-dark">{{ $item->product->size }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="badge {{ $item->quantity > 0 ? 'bg-success' : 'bg-danger' }} fs-6">
                                        {{ $item->quantity }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if ($item->product->mrp)
                                        <div class="fw-medium">₹{{ currency($item->product->mrp) }}</div>
                                        @if ($item->product->cost)
                                            <small class="text-muted">Cost: ₹{{ currency($item->product->cost) }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="demo-psi-search fs-1 text-muted"></i>
                </div>
                <h5 class="text-muted">No products found</h5>
                <p class="text-muted mb-0">Try adjusting your search criteria or filters.</p>
            </div>
        @endif
    </div>

    <!-- Pagination -->
    @if ($products && $products->hasPages())
        {{ $products->links() }}
    @endif
</div>
