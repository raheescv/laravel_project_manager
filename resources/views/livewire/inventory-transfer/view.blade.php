<div>
    <div class="inventory-transfer-container">
        <!-- Hero Section -->
        <div class="transfer-hero mb-4">
            <div class="glass-card p-4">
                <div class="transfer-flow position-relative">
                    <div class="row g-4">
                        <div class="col-md-5">
                            <div class="branch-card from-branch">
                                <div class="branch-icon">
                                    <i class="fa fa-building text-primary"></i>
                                </div>
                                <h6 class="text-uppercase mb-2 text-muted tracking-wide">From Branch</h6>
                                <h3 class="branch-name">{{ $model->fromBranch?->name }}</h3>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-center justify-content-center">
                            <div class="transfer-arrow">
                                <div class="pulse-ring"></div>
                                <i class="fa fa-exchange"></i>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="branch-card to-branch">
                                <div class="branch-icon">
                                    <i class="fa fa-building text-success"></i>
                                </div>
                                <h6 class="text-uppercase mb-2 text-muted tracking-wide">To Branch</h6>
                                <h3 class="branch-name">{{ $model->toBranch?->name }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Cards Grid -->
        <div class="info-grid mb-5">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="neo-card">
                        <div class="card-icon">
                            <i class="fa fa-tachometer"></i>
                        </div>
                        <div class="card-content">
                            <h6 class="text-muted mb-1">Transfer No.</h6>
                            <h4 class="mb-0">#{{ $model->id }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="neo-card">
                        <div class="card-icon">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <div class="card-content">
                            <h6 class="text-muted mb-1">Date</h6>
                            <h4 class="mb-0">{{ systemDate($model->date) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="neo-card">
                        <div class="card-icon">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <div class="card-content">
                            <h6 class="text-muted mb-1">Status</h6>
                            <div class="status-badge">
                                <span class="status-dot"></span>
                                {{ ucFirst($model->status) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="neo-card">
                        <div class="card-icon">
                            <i class="fa fa-user"></i>
                        </div>
                        <div class="card-content">
                            <h6 class="text-muted mb-1">Created By</h6>
                            <h4 class="mb-0">{{ $model->createdBy?->name }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="neo-card">
                        <div class="card-icon">
                            <i class="fa fa-shield"></i>
                        </div>
                        <div class="card-content">
                            <h6 class="text-muted mb-1">Approved By</h6>
                            <h4 class="mb-0">{{ $model->approvedBy?->name }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table Section -->
        <div class="items-section mb-5">
            <div class="section-header d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="bi bi-box-seam me-2"></i>
                    Transfer Items
                </h4>
                <div class="actions d-flex gap-3">
                    @if ($model['status'] != 'cancelled')
                        <button class="btn btn-light btn-with-icon" onclick="window.open('{{ route('inventory::transfer::print', $model['id']) }}', '_blank')">
                            <i class="fa fa-print"></i>
                            <span>Print</span>
                        </button>
                        @can('inventory transfer.edit completed')
                            <a href="{{ route('inventory::transfer::edit', $model['id']) }}" class="btn btn-primary btn-with-icon">
                                <i class="fa fa-pencil"></i>
                                <span>Edit</span>
                            </a>
                        @endcan
                    @endif
                </div>
            </div>

            <div class="table-container">
                <table class="table modern-table">
                    <thead>
                        <tr>
                            <th>SL No</th>
                            <th width="30%">Product</th>
                            <th>Batch</th>
                            <th>Barcode</th>
                            <th class="text-end">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($model->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item['name'] }}</td>
                                <td>{{ $item->inventory->batch }}</td>
                                <td>{{ $item->inventory->barcode }}</td>
                                <td class="text-end">{{ $item['quantity'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold">{{ currency(collect($model->items)->sum('quantity')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Description Section -->
        @if ($model['description'])
            <div class="description-section mb-5">
                <div class="neo-card">
                    <div class="card-icon">
                        <i class="bi bi-text-paragraph"></i>
                    </div>
                    <div class="card-content">
                        <h6 class="text-muted mb-3">Description</h6>
                        <p class="mb-0">{{ $model['description'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Inventory Logs Section -->
        <div class="inventory-logs-section">
            <div class="section-header mb-4">
                <h4 class="d-flex align-items-center">
                    <i class="bi bi-clock-history me-2"></i>
                    Inventory Logs
                </h4>
            </div>

            <div class="table-container">
                <table class="table modern-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Department</th>
                            <th>Category</th>
                            <th>Product</th>
                            <th>Barcode</th>
                            <th>Batch</th>
                            <th class="text-end">In</th>
                            <th class="text-end">Out</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ systemDateTime($item->created_at) }}</td>
                                <td>{{ $item->branch?->name }}</td>
                                <td>{{ $item->product?->department?->name }}</td>
                                <td>{{ $item->product?->mainCategory?->name }}</td>
                                <td>
                                    <a href="{{ route('inventory::product::view', $item->product_id) }}" class="product-link">
                                        {{ $item->product?->name }}
                                    </a>
                                </td>
                                <td>{{ $item->barcode }}</td>
                                <td>{{ $item->batch }}</td>
                                <td class="text-end">{{ $item->quantity_in }}</td>
                                <td class="text-end">{{ $item->quantity_out }}</td>
                                <td class="text-end">{{ $item->balance }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @push('styles')
        <style>
            /* Base Styles */
            .inventory-transfer-container {
                --primary-color: #4f46e5;
                --success-color: #10b981;
                --card-bg: #ffffff;
                --border-radius: 1rem;
                --transition: all 0.3s ease;
            }

            /* Glass Card Effect */
            .glass-card {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(10px);
                border-radius: var(--border-radius);
                border: 1px solid rgba(255, 255, 255, 0.2);
                box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            }

            /* Branch Cards */
            .branch-card {
                background: var(--card-bg);
                padding: 0.1rem;
                border-radius: var(--border-radius);
                text-align: center;
                transition: var(--transition);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
                    0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            .branch-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
                    0 4px 6px -2px rgba(0, 0, 0, 0.05);
            }

            .branch-icon {
                font-size: 2.5rem;
                margin-bottom: 1rem;
            }

            .tracking-wide {
                letter-spacing: 0.05em;
            }

            /* Transfer Arrow Animation */
            .transfer-arrow {
                position: relative;
                width: 60px;
                height: 60px;
                background: var(--card-bg);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                color: var(--primary-color);
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            }

            .pulse-ring {
                position: absolute;
                width: 100%;
                height: 100%;
                border-radius: 50%;
                animation: pulse 2s infinite;
                border: 3px solid var(--primary-color);
            }

            @keyframes pulse {
                0% {
                    transform: scale(0.95);
                    opacity: 0.5;
                }

                70% {
                    transform: scale(1.1);
                    opacity: 0.2;
                }

                100% {
                    transform: scale(0.95);
                    opacity: 0.5;
                }
            }

            /* Neo Cards */
            .neo-card {
                background: var(--card-bg);
                padding: 1.5rem;
                border-radius: var(--border-radius);
                display: flex;
                align-items: center;
                gap: 1rem;
                transition: var(--transition);

            }

            .neo-card:hover {
                transform: translateY(-2px);
                box-shadow: 8px 8px 20px rgba(0, 0, 0, 0.08),
                    -8px -8px 20px rgba(255, 255, 255, 0.9);
            }

            .card-icon {
                width: 48px;
                height: 48px;
                border-radius: 12px;
                background: #f3f4f6;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                color: var(--primary-color);
            }

            /* Status Badge */
            .status-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem 1rem;
                border-radius: 2rem;
                background: #ecfdf5;
                color: var(--success-color);
                font-weight: 600;
            }

            .status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--success-color);
            }

            /* Modern Table */
            .table-container {
                background: var(--card-bg);
                border-radius: var(--border-radius);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }

            .modern-table {
                margin: 0;
            }

            .modern-table thead {
                background: #f8fafc;
            }

            .modern-table th {
                text-transform: uppercase;
                font-size: 0.875rem;
                font-weight: 600;
                letter-spacing: 0.05em;
                padding: 1rem;
                border-bottom: 2px solid #e2e8f0;
            }

            .modern-table td {
                padding: 1rem;
                vertical-align: middle;
                border-bottom: 1px solid #f1f5f9;
            }

            .modern-table tbody tr:hover {
                background: #f8fafc;
            }

            /* Buttons */
            .btn-with-icon {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.625rem 1.25rem;
                font-weight: 500;
                border-radius: 0.75rem;
                transition: var(--transition);
            }

            .btn-with-icon i {
                font-size: 1.25rem;
            }

            /* Product Links */
            .product-link {
                color: var(--primary-color);
                text-decoration: none;
                font-weight: 500;
                transition: var(--transition);
            }

            .product-link:hover {
                color: #4338ca;
                text-decoration: underline;
            }

            /* Section Headers */
            .section-header h4 {
                font-weight: 600;
                color: #1f2937;
            }
        </style>
    @endpush
</div>
