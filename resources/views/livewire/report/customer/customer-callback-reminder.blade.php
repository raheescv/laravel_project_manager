<div>
    <div class="card shadow-sm border">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fa fa-phone me-2 text-primary"></i>
                        Customer Reminder Callback Report
                    </h5>
                    <small class="text-muted">Identify customers who haven't purchased sale items and need follow-up</small>
                </div>
                <div class="d-flex gap-2">
                    <button wire:click="exportData" class="btn btn-outline-primary btn-sm" @if ($loading) disabled @endif>
                        @if ($loading)
                            <i class="fa fa-spinner fa-spin me-1"></i>
                        @else
                            <i class="fa fa-download me-1"></i>
                        @endif
                        Export
                    </button>
                </div>
            </div>

            <!-- Enhanced Filter Section -->
            <div class="row g-3">
                <div class="col-lg-2 col-md-4 col-6">
                    <label for="priority_filter" class="form-label text-dark fw-semibold">
                        <i class="fa fa-filter me-1 text-primary"></i>Priority
                    </label>
                    <select wire:model.live="priorityFilter" class="form-select form-select-sm" id="priority_filter">
                        <option value="all">All Priorities</option>
                        <option value="high">High (90+ days)</option>
                        <option value="medium">Medium (60-90 days)</option>
                        <option value="low">Low (30-60 days)</option>
                        <option value="recent">Recent (< 30 days)</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <label for="per_page" class="form-label text-dark fw-semibold">
                        <i class="fa fa-list me-1 text-primary"></i>Per Page
                    </label>
                    <select wire:model.live="perPage" class="form-select form-select-sm" id="per_page">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label for="reminder_cutoff_date" class="form-label text-dark fw-semibold">
                        <i class="fa fa-calendar-alt me-1 text-primary"></i>Cutoff Date
                    </label>
                    <input type="date" wire:model.live="reminder_cutoff_date" class="form-control form-control-sm" id="reminder_cutoff_date">
                </div>
                <div class="col-lg-5 col-md-12 col-sm-6 d-flex align-items-end">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <button type="button" wire:click="refreshData" class="btn btn-outline-success" @if ($loading) disabled @endif>
                            @if ($loading)
                                <i class="fa fa-spinner fa-spin me-1"></i>
                            @else
                                <i class="fa fa-refresh me-1"></i>
                            @endif
                            <span class="d-none d-sm-inline">Refresh</span>
                        </button>
                        <button type="button" onclick="resetAllFilters()" class="btn btn-outline-secondary">
                            <i class="fa fa-undo me-1"></i><span class="d-none d-sm-inline">Reset</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-lg-3 col-md-6">
                    <label for="search_term" class="form-label text-dark fw-semibold">
                        <i class="fa fa-search me-1 text-primary"></i>Search Customers
                    </label>
                    <input type="text" wire:model.live.debounce.300ms="searchTerm" autofocus class="form-control form-control-sm" id="search_term" placeholder="Name, mobile, or email...">
                </div>
                <div class="col-lg-3 col-md-6" wire:ignore>
                    <label for="reminder_category_id" class="form-label text-dark fw-semibold">
                        <i class="fa fa-tags me-1 text-primary"></i>Category
                    </label>
                    {{ html()->select('reminder_category_id', [])->value('')->class('select-category_id-list')->id('reminder_category_id')->attribute('style', '100%')->placeholder('All Categories') }}
                </div>
                <div class="col-lg-6 col-md-12" wire:ignore>
                    <label for="reminder_product_id" class="form-label text-dark fw-semibold">
                        <i class="fa fa-cube me-1 text-primary"></i>Product
                    </label>
                    {{ html()->select('reminder_product_id', [])->value('')->class('select-product_id-list')->id('reminder_product_id')->attribute('style', '100%')->placeholder('All Products') }}
                </div>
            </div>

            <!-- Enhanced Summary Cards -->
            <div class="row g-3 mt-3">
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="card-body text-white">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-white bg-opacity-20 rounded-circle p-3">
                                        <i class="fa fa-users fs-4"></i>
                                    </div>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="fs-3 fw-bold mb-0">
                                        @if ($loading)
                                            <i class="fa fa-spinner fa-spin"></i>
                                        @else
                                            {{ number_format($totalCustomers) }}
                                        @endif
                                    </div>
                                    <div class="small opacity-75">Total Customers</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="card-body text-white">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-white bg-opacity-20 rounded-circle p-3">
                                        <i class="fa fa-exclamation-triangle fs-4"></i>
                                    </div>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="fs-3 fw-bold mb-0">{{ $stats['high_priority'] ?? 0 }}</div>
                                    <div class="small opacity-75">High Priority (90+ days)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="card-body text-white">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-white bg-opacity-20 rounded-circle p-3">
                                        <i class="fa fa-phone fs-4"></i>
                                    </div>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="fs-3 fw-bold mb-0">{{ $stats['with_mobile'] ?? 0 }}</div>
                                    <div class="small opacity-75">With Mobile</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <div class="card-body text-white">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-white bg-opacity-20 rounded-circle p-3">
                                        <i class="fa fa-envelope fs-4"></i>
                                    </div>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="fs-3 fw-bold mb-0">{{ $stats['with_email'] ?? 0 }}</div>
                                    <div class="small opacity-75">With Email</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            @if ($loading)
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 text-muted">Loading customer data...</div>
                </div>
            @else
                <!-- Toggle button for card/table view -->
                <div class="d-flex justify-content-center justify-content-md-end mb-4">
                    <button type="button" class="btn toggle-view-btn"
                        style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none; border-radius: 50px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15); padding: 10px 20px; font-weight: 500;">
                        <i class="fa fa-th-list me-1"></i><span class="view-text">Switch to Card View</span>
                    </button>
                </div>

                <div class="table-responsive mobile-table-view w-100">
                    <table class="table table-sm table-hover align-middle w-100">
                        <thead class="bg-primary">
                            <tr>
                                <th class="text-white text-center" style="width: 50px;">#</th>
                                <th class="text-white sortable" style="min-width: 200px;" wire:click="sortBy('name')">
                                    <i class="fa fa-user me-2"></i>Name
                                    @if ($sortField === 'name')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                                <th class="text-white sortable" style="min-width: 130px;" wire:click="sortBy('mobile')">
                                    <i class="fa fa-phone me-2"></i>Contact
                                    @if ($sortField === 'mobile')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                                <th class="text-white sortable" style="min-width: 200px;" wire:click="sortBy('email')">
                                    <i class="fa fa-envelope me-2"></i>Email
                                    @if ($sortField === 'email')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                                <th class="text-white sortable text-center text-nowrap" style="min-width: 100px;" wire:click="sortBy('nationality')">
                                    <i class="fa fa-flag me-2"></i>Nationality
                                    @if ($sortField === 'nationality')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                                <th class="text-white sortable text-center text-nowrap" style="min-width: 130px;" wire:click="sortBy('last_purchase_date')">
                                    <i class="fa fa-shopping-cart me-2"></i>Last Purchase
                                    @if ($sortField === 'last_purchase_date')
                                        <i class="fa fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @endif
                                </th>
                                <th class="text-white text-center" style="min-width: 100px;">
                                    <i class="fa fa-hashtag me-2"></i>Orders
                                </th>
                                <th class="text-white text-center" style="min-width: 120px;">
                                    <i class="fa fa-dollar-sign me-2"></i>Total Spent
                                </th>
                                <th class="text-white text-center" style="min-width: 120px;">
                                    <i class="fa fa-clock me-2"></i>Days Ago
                                </th>
                                <th class="text-white text-center" style="min-width: 100px;">
                                    <i class="fa fa-star me-2"></i>Priority
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $index => $customer)
                                @php
                                    $daysSinceLastPurchase = $customer->last_purchase_date ? abs(now()->diffInDays($customer->last_purchase_date)) : 0;
                                    $priorityClass = '';
                                    $priorityLabel = '';
                                    $priorityIcon = '';
                                    $priorityBadge = '';

                                    if ($daysSinceLastPurchase > 90) {
                                        $priorityClass = 'table-danger';
                                        $priorityLabel = 'High';
                                        $priorityIcon = 'exclamation-triangle';
                                        $priorityBadge = 'bg-danger';
                                    } elseif ($daysSinceLastPurchase > 60) {
                                        $priorityClass = 'table-warning';
                                        $priorityLabel = 'Medium';
                                        $priorityIcon = 'exclamation-circle';
                                        $priorityBadge = 'bg-warning';
                                    } elseif ($daysSinceLastPurchase > 30) {
                                        $priorityClass = 'table-info';
                                        $priorityLabel = 'Low';
                                        $priorityIcon = 'info-circle';
                                        $priorityBadge = 'bg-info';
                                    } else {
                                        $priorityLabel = 'Recent';
                                        $priorityIcon = 'check-circle';
                                        $priorityBadge = 'bg-success';
                                    }
                                @endphp
                                <tr class="{{ $priorityClass }} border-bottom">
                                    <td class="text-center fw-bold text-muted" data-label="#">
                                        {{ ($customers->currentPage() - 1) * $customers->perPage() + $index + 1 }}
                                    </td>
                                    <td data-label="Name">
                                        <div class="d-flex align-items-center">
                                            <div class="fw-bold text-dark">
                                                <a href="{{ route('account::customer::view', $customer->id) }}" class="text-decoration-none">
                                                    {{ $customer->name }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Contact">
                                        @if ($customer->mobile)
                                            <a href="tel:{{ $customer->mobile }}" class="text-decoration-none">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-success bg-opacity-10 rounded p-2 me-2">
                                                        <i class="fa fa-phone text-success"></i>
                                                    </div>
                                                    <span class="text-dark">{{ $customer->mobile }}</span>
                                                </div>
                                            </a>
                                        @else
                                            <span class="text-muted">
                                                <i class="fa fa-phone-slash me-1"></i>No mobile
                                            </span>
                                        @endif
                                    </td>
                                    <td data-label="Email">
                                        @if ($customer->email)
                                            <a href="mailto:{{ $customer->email }}" class="text-decoration-none">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-info bg-opacity-10 rounded p-2 me-2">
                                                        <i class="fa fa-envelope text-info"></i>
                                                    </div>
                                                    <span class="text-dark text-truncate" style="max-width: 150px;" title="{{ $customer->email }}">{{ $customer->email }}</span>
                                                </div>
                                            </a>
                                        @else
                                            <span class="text-muted">
                                                <i class="fa fa-envelope-open me-1"></i>No email
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center" data-label="Nationality">
                                        @if ($customer->nationality)
                                            <span class="badge bg-light text-dark border">
                                                <i class="fa fa-flag me-1"></i>{{ $customer->nationality }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                <i class="fa fa-question-circle"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center" data-label="Last Purchase">
                                        @if ($customer->last_purchase_date)
                                            <div class="text-dark fw-semibold">{{ systemDate($customer->last_purchase_date) }}</div>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="fa fa-ban me-1"></i>No purchases
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center" data-label="Orders">
                                        <span class="badge bg-primary fs-6 px-3 py-2">
                                            {{ number_format($customer->total_purchases) }}
                                        </span>
                                    </td>
                                    <td class="text-center" data-label="Total Spent">
                                        <span class="fw-bold text-success fs-6">
                                            {{ currency($customer->total_spent) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($daysSinceLastPurchase !== null)
                                            @php
                                                $badgeClass = 'bg-info';
                                                $icon = 'clock';
                                                if ($daysSinceLastPurchase > 90) {
                                                    $badgeClass = 'bg-danger';
                                                    $icon = 'exclamation-triangle';
                                                } elseif ($daysSinceLastPurchase > 60) {
                                                    $badgeClass = 'bg-warning';
                                                    $icon = 'exclamation-circle';
                                                } elseif ($daysSinceLastPurchase > 30) {
                                                    $badgeClass = 'bg-info';
                                                    $icon = 'info-circle';
                                                }
                                            @endphp
                                            <span class="badge {{ $badgeClass }} fs-6 px-3 py-2">
                                                <i class="fa fa-{{ $icon }} me-1"></i>
                                                {{ round($daysSinceLastPurchase) }} days
                                            </span>
                                        @else
                                            <span class="badge bg-secondary fs-6 px-3 py-2">
                                                <i class="fa fa-question me-1"></i>N/A
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center" data-label="Priority">
                                        <span class="badge {{ $priorityBadge }} fs-6 px-3 py-2">
                                            <i class="fa fa-{{ $priorityIcon }} me-1"></i>
                                            {{ $priorityLabel }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <div class="empty-state">
                                            <div class="mb-3">
                                                <i class="fa fa-search fs-1 text-muted opacity-50"></i>
                                            </div>
                                            <h5 class="text-muted">No customers found</h5>
                                            <p class="text-muted mb-0">
                                                @if ($searchTerm || $priorityFilter !== 'all')
                                                    No customers match your search criteria "<strong>{{ $searchTerm }}</strong>"
                                                    @if ($priorityFilter !== 'all')
                                                        with "{{ ucfirst($priorityFilter) }} Priority" filter
                                                    @endif
                                                    .<br>
                                                    <small>Try adjusting your search terms or filters to see more results.</small>
                                                @else
                                                    No customers match your current filter criteria.<br>
                                                    <small>Try adjusting your filters or date range to see more results.</small>
                                                @endif
                                            </p>
                                            <div class="mt-3">
                                                <button class="btn btn-outline-primary btn-sm" onclick="resetAllFilters()">
                                                    <i class="fa fa-undo me-1"></i>Reset All Filters
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($customers)
                    <div class="d-flex justify-content-between align-items-center flex-wrap mt-4 pt-3 border-top">
                        <div class="text-muted mb-2 mb-md-0">
                            <small>
                                Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ number_format($customers->total()) }} customers
                                @if ($searchTerm)
                                    <span class="d-inline d-md-inline">(filtered by "{{ $searchTerm }}")</span>
                                @endif
                                @if ($priorityFilter !== 'all')
                                    <span class="d-inline d-md-inline">({{ ucfirst($priorityFilter) }} priority only)</span>
                                @endif
                            </small>
                        </div>
                        <div class="w-100 d-md-none mt-2 mb-2"></div> <!-- Force line break on mobile -->
                        <div class="ms-auto">
                            {{ $customers->links() }}
                        </div>
                    </div>
                @endif

                @if (isset($error))
                    <div class="alert alert-danger mt-3" role="alert">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        {{ $error }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Custom Styles -->
    @push('styles')
        <style>
            .avatar {
                width: 40px;
                height: 40px;
            }

            .table> :not(caption)>*>* {
                padding: 0.75rem 0.5rem;
            }

            .empty-state {
                padding: 2rem;
            }

            .btn-group .btn {
                border-radius: 0.375rem !important;
                margin-right: 0.25rem;
            }

            .btn-group .btn:last-child {
                margin-right: 0;
            }

            .table-hover>tbody>tr:hover>* {
                background-color: rgba(0, 0, 0, 0.025);
                transition: background-color 0.15s ease-in-out;
            }

            .card {
                border-radius: 1rem;
            }

            .card-header {
                border-radius: 1rem 1rem 0 0 !important;
            }

            /* Sortable column styles */
            .sortable {
                cursor: pointer;
                user-select: none;
                transition: background-color 0.15s ease-in-out;
            }

            .sortable:hover {
                background-color: rgba(255, 255, 255, 0.1) !important;
            }

            .sortable:active {
                transform: translateY(1px);
            }

            /* Loading overlay */
            .loading-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }

            /* Enhanced badges */
            .badge {
                font-weight: 500;
                letter-spacing: 0.025em;
            }

            /* Priority row animations */
            .table-danger {
                background-color: rgba(220, 53, 69, 0.1) !important;
                border-left: 4px solid #dc3545;
            }

            .table-warning {
                background-color: rgba(255, 193, 7, 0.1) !important;
                border-left: 4px solid #ffc107;
            }

            .table-info {
                background-color: rgba(13, 202, 240, 0.1) !important;
                border-left: 4px solid #0dcaf0;
            }

            /* Search highlight */
            .search-highlight {
                background-color: yellow;
                padding: 0.1em 0.2em;
                border-radius: 0.2em;
            }

            /* Enhanced Mobile Responsiveness */
            @media (max-width: 768px) {
                .table-responsive {
                    font-size: 0.875rem;
                }

                .btn-group {
                    flex-direction: column;
                    gap: 0.5rem;
                }

                .card-header {
                    padding: 0.75rem;
                }

                .card-body {
                    padding: 0.75rem !important;
                }

                .row.g-3 {
                    row-gap: 0.5rem !important;
                }

                /* Stack the title and export button on mobile */
                .d-flex.justify-content-between {
                    flex-direction: column;
                    gap: 0.75rem;
                }

                /* Make badges more compact on mobile */
                .badge.fs-6 {
                    font-size: 0.75rem !important;
                    padding: 0.25rem 0.5rem !important;
                }

                /* Make summary cards stack properly */
                .col-xl-3.col-md-6 {
                    margin-bottom: 0.5rem;
                }

                /* Adjust table cell spacing */
                .table td,
                .table th {
                    padding: 0.5rem 0.25rem;
                }

                /* Simplified mobile view for complex cells */
                .d-flex.align-items-center {
                    flex-wrap: wrap;
                }

                /* Fix pagination on mobile */
                .d-flex.justify-content-between.align-items-center {
                    flex-direction: column;
                    gap: 1rem;
                    align-items: center !important;
                }

                /* Hide less important columns on mobile */
                @media (max-width: 576px) {
                    .mobile-hide {
                        display: none;
                    }

                    .table-responsive {
                        margin-left: -0.75rem;
                        margin-right: -0.75rem;
                        width: calc(100% + 1.5rem);
                    }

                    /* More compact name display */
                    .fw-bold.text-dark {
                        font-size: 0.9rem;
                    }
                }
            }

            /* Card View Alternative - Enhanced Design (Full Width) */
            /* Removed media query to enable card view at all screen sizes */
            .mobile-card-view {
                width: 100% !important;
                max-width: 100% !important;
                overflow-x: visible !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .mobile-card-view .table {
                display: block;
                width: 100%;
                table-layout: fixed;
                margin: 0;
                padding: 0;
            }

            .mobile-card-view tbody {
                display: grid;
                width: 100%;
                padding: 0;
                margin: 0;
            }

            .mobile-card-view thead {
                display: none;
            }

            .mobile-card-view tr {
                display: block;
                width: 100%;
                margin-bottom: 1.25rem;
                border: none;
                border-radius: 0.75rem;
                padding: 0;
                position: relative;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
                overflow: hidden;
                transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            }

            .mobile-card-view tr:active {
                transform: scale(0.98);
            }

            .mobile-card-view tr.table-danger {
                border-left: none !important;
                background: linear-gradient(to right, #dc3545 5px, white 5px) !important;
            }

            .mobile-card-view tr.table-warning {
                border-left: none !important;
                background: linear-gradient(to right, #ffc107 5px, white 5px) !important;
            }

            .mobile-card-view tr.table-info {
                border-left: none !important;
                background: linear-gradient(to right, #0dcaf0 5px, white 5px) !important;
            }

            /* Customer name header for each card */
            .mobile-card-view td[data-label="Name"] {
                background-color: #f8f9fa;
                padding: 0.75rem !important;
                border-bottom: 1px solid #eee !important;
                margin-bottom: 0.5rem;
                text-align: left;
            }

            .mobile-card-view td[data-label="Name"]:before {
                display: none;
            }

            .mobile-card-view td[data-label="Name"] .fw-bold {
                font-size: 1rem !important;
            }

            /* Make the # column disappear - unnecessary in card view */
            .mobile-card-view td[data-label="#"] {
                display: none;
            }

            /* Grid layout for all screens */
            @media (max-width: 576px) {
                .mobile-card-view tbody {
                    grid-template-columns: 1fr;
                    gap: 1rem;
                }
            }

            @media (min-width: 577px) and (max-width: 991px) {
                .mobile-card-view tbody {
                    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                    gap: 1rem;
                }
            }

            @media (min-width: 992px) {
                .mobile-card-view tbody {
                    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
                    gap: 1.5rem;
                }
            }

            @media (min-width: 1200px) {
                .mobile-card-view tbody {
                    grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
                    gap: 1.5rem;
                }
            }

            @media (min-width: 1600px) {
                .mobile-card-view tbody {
                    grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
                    gap: 2rem;
                }
            }

            .mobile-card-view tr {
                height: 100%;
                width: 100%;
                margin: 0;
            }

            .mobile-card-view td {
                display: flex;
                align-items: center;
                justify-content: space-between;
                text-align: right;
                border: none !important;
                padding: 0.5rem 0.75rem !important;
                border-bottom: 1px dashed rgba(0, 0, 0, 0.05) !important;
            }

            .mobile-card-view td:last-child {
                border-bottom: none !important;
            }

            .mobile-card-view td:before {
                content: attr(data-label);
                font-weight: 600;
                font-size: 0.75rem;
                color: #6c757d;
                padding-right: 0.5rem;
            }

            /* Priority badge placement */
            .mobile-card-view td[data-label="Priority"] {
                background-color: #f8f9fa;
                padding: 0.75rem !important;
                border-top: 1px solid #eee !important;
                margin-top: 0.5rem;
            }

            /* Center align certain cells */
            .mobile-card-view td[data-label="Total Spent"],
            .mobile-card-view td[data-label="Orders"],
            .mobile-card-view td[data-label="Days Ago"],
            .mobile-card-view td[data-label="Priority"] {
                display: flex;
                justify-content: space-between;
            }

            /* Adjust specific elements in card view */
            .mobile-card-view .badge {
                display: inline-block;
                margin-left: auto;
            }

            /* Styles for contact and email in card view */
            .mobile-card-view td[data-label="Contact"] .d-flex,
            .mobile-card-view td[data-label="Email"] .d-flex {
                margin-left: auto;
                max-width: 70%;
            }

            /* Improved interaction feedback */
            .mobile-card-view tr:hover {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Initialize Select2 fields
                $('#reminder_product_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('product_id', value);
                });
                $('#reminder_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('category_id', value);
                });

                // Toggle between table and card view on mobile with animation
                $('.toggle-view-btn').on('click', function() {
                    const $tableContainer = $('.table-responsive');
                    const $viewText = $('.view-text');
                    const $icon = $(this).find('i');
                    const $button = $(this);

                    // Add animation
                    $tableContainer.fadeOut(200, function() {
                        if ($tableContainer.hasClass('mobile-card-view')) {
                            // Switch to table view
                            $tableContainer.removeClass('mobile-card-view');
                            $tableContainer.addClass('mobile-table-view');
                            $viewText.text('Switch to Card View');
                            $icon.removeClass('fa-table').addClass('fa-th-list');
                            $button.css('background', 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)');
                        } else {
                            // Switch to card view
                            $tableContainer.removeClass('mobile-table-view');
                            $tableContainer.addClass('mobile-card-view');
                            $viewText.text('Switch to Table View');
                            $icon.removeClass('fa-th-list').addClass('fa-table');
                            $button.css('background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)');
                        }

                        // Store user preference in localStorage
                        localStorage.setItem('customerCallback_viewMode',
                            $tableContainer.hasClass('mobile-card-view') ? 'card' : 'table');

                        // Show with animation
                        $tableContainer.fadeIn(200);
                    });
                });

                // Enhanced loading state with better UX
                function showLoadingState() {
                    const tableBody = $('.table tbody');
                    const loadingHtml = `
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="spinner-border text-primary mb-3" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="text-muted">
                                        <i class="fa fa-search me-2"></i>Searching for customers...
                                    </div>
                                    <small class="text-muted mt-1">This may take a moment for large datasets</small>
                                </div>
                            </td>
                        </tr>
                    `;
                    tableBody.html(loadingHtml);

                    // Update summary cards with loading state
                    $('.fs-3.fw-bold').html('<i class="fa fa-spinner fa-spin"></i>');
                }

                Livewire.on('filterUpdated', () => {
                    showLoadingState();
                });

                // Search term highlighting
                function highlightSearchTerms() {
                    const searchTerm = @json($searchTerm ?? '');
                    if (searchTerm && searchTerm.length > 2) {
                        const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
                        $('.table tbody td').each(function() {
                            const $this = $(this);
                            if (!$this.find('a, input, select, button').length) {
                                const text = $this.text();
                                if (text.match(regex)) {
                                    const highlightedText = text.replace(regex, '<span class="search-highlight">$1</span>');
                                    $this.html(highlightedText);
                                }
                            }
                        });
                    }
                }

                // Check screen width on load and resize
                function checkScreenWidth() {
                    // Always show toggle button for all screen sizes
                    $('.toggle-view-btn').show();

                    // Retrieve user preference from localStorage
                    const savedViewMode = localStorage.getItem('customerCallback_viewMode');

                    // Apply saved preference if it exists
                    if (savedViewMode === 'card') {
                        $('.table-responsive').addClass('mobile-card-view').removeClass('mobile-table-view');
                        $('.view-text').text('Switch to Table View');
                        $('.toggle-view-btn i').removeClass('fa-th-list').addClass('fa-table');
                        $('.toggle-view-btn').css('background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)');
                    }
                }

                // Run on page load
                checkScreenWidth();

                // Run on window resize
                $(window).resize(function() {
                    checkScreenWidth();
                });

                // Escape regex special characters
                function escapeRegExp(string) {
                    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                }
            });

            // Global reset function with enhanced UX
            function resetAllFilters() {
                // Show confirmation for user experience
                if (confirm('Reset all filters and search criteria?')) {
                    // Reset select2 elements (these still need jQuery due to select2)
                    $('#reminder_product_id').val('').trigger('change');
                    $('#reminder_category_id').val('').trigger('change');

                    // Reset Livewire properties
                    @this.set('searchTerm', '');
                    @this.set('priorityFilter', 'all');
                    @this.set('perPage', 20);
                    @this.set('reminder_cutoff_date', '{{ date('Y-m-d', strtotime('-30 days')) }}');

                    // Show feedback toast on mobile
                    if ($(window).width() < 768) {
                        const toastHtml = `
                        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
                            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="toast-header bg-success text-white">
                                    <i class="fa fa-check-circle me-2"></i>
                                    <strong class="me-auto">Success</strong>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                                <div class="toast-body">
                                    All filters have been reset!
                                </div>
                            </div>
                        </div>
                    `;

                        $('body').append(toastHtml);
                        setTimeout(() => {
                            $('.toast').fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 2000);
                    }
                }
            }
        </script>
    @endpush
</div>
make th
