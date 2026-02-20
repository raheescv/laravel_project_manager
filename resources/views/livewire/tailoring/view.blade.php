<div>
    @php
        use Illuminate\Support\Str;
    @endphp
    <div class="min-vh-100 bg-body-tertiary py-4 tailoring-view-page">
        <div class="container-fluid px-3 px-lg-4">
            @php
                $statusColors = [
                    'pending' => 'primary',
                    'confirmed' => 'primary',
                    'completed' => 'primary',
                    'cancelled' => 'primary',
                    'delivered' => 'primary',
                    'in_progress' => 'primary',
                ];
                $orderStatusKey = strtolower((string) $order->status);
                $orderStatusColor = $statusColors[$orderStatusKey] ?? 'secondary';
            @endphp

            <div class="card shadow border-0 mb-3 bg-primary-subtle order-hero-card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h1 class="h4 mb-0 fw-bold"><i class="fa fa-file-text-o me-2"></i>Work Order #{{ $order->order_no }}</h1>
                                <span class="badge rounded-pill text-bg-{{ $orderStatusColor }}">
                                    {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </div>
                            <p class="text-muted mb-0 mt-1 small">
                                <i class="fa fa-calendar me-1"></i>Order Date: {{ systemDate($order->order_date) }}
                                @if ($order->delivery_date)
                                    | <i class="fa fa-truck me-1"></i>Delivery Date: {{ systemDate($order->delivery_date) }}
                                @endif
                            </p>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('tailoring::order::print-receipt', $order->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fa fa-file-pdf-o me-1"></i> Receipt PDF
                            </a>
                            <a href="{{ route('tailoring::order::print-receipt-thermal', $order->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fa fa-print me-1"></i> Thermal Print
                            </a>
                            <a href="{{ route('tailoring::job-completion::index') }}?order_no={{ $order->order_no }}" class="btn btn-outline-primary btn-sm">
                                <i class="fa fa-check-circle me-1"></i> Job Completion
                            </a>
                            <a href="{{ route('tailoring::order::edit', $order->id) }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-edit me-1"></i> Edit Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary metric-card">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase"><i class="fa fa-calculator me-1"></i>Grand Total</div>
                            <div class="h5 mb-0 fw-bold">{{ currency($order->grand_total) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary metric-card">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase"><i class="fa fa-check-circle me-1"></i>Paid</div>
                            <div class="h5 mb-0 fw-bold text-primary">{{ currency($order->paid) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary metric-card">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase"><i class="fa fa-exchange me-1"></i>Balance</div>
                            <div class="h5 mb-0 fw-bold text-primary">{{ currency($order->balance) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary metric-card">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase"><i class="fa fa-cubes me-1"></i>Total Items</div>
                            <div class="h5 mb-0 fw-bold text-primary">{{ count($order->items) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-lg-4">
                    <div class="d-flex flex-column gap-3">
                        <div class="card border-0 shadow-sm info-card">
                            <div class="card-header bg-primary-subtle text-primary-emphasis fw-semibold"><i class="fa fa-user me-1"></i>Customer Details</div>
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold p-3 shadow-sm">
                                        {{ strtoupper(substr($order->customer_name ?? 'C', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $order->customer_name ?: 'Walk-in Customer' }}</div>
                                        <div class="text-muted small"><i class="fa fa-phone me-1"></i>{{ $order->customer_mobile ?: 'No mobile' }}</div>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="text-muted small"><i class="fa fa-building me-1"></i>Branch</div>
                                        <div class="fw-semibold">{{ $order->branch->name ?? 'Main' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted small"><i class="fa fa-user-plus me-1"></i>Salesman</div>
                                        <div class="fw-semibold">{{ $order->salesman->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm info-card">
                            <div class="card-header bg-primary-subtle text-primary-emphasis fw-semibold"><i class="fa fa-clock-o me-1"></i>Timeline</div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <span class="text-muted"><i class="fa fa-calendar me-1"></i>Order Date</span>
                                    <span class="fw-semibold">{{ systemDate($order->order_date) }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-2">
                                    <span class="text-muted"><i class="fa fa-truck me-1"></i>Delivery Date</span>
                                    <span class="fw-semibold">{{ systemDate($order->delivery_date) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm info-card">
                            <div class="card-header bg-primary-subtle text-primary-emphasis fw-semibold"><i class="fa fa-line-chart me-1"></i>Financial Overview</div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between py-1">
                                    <span class="text-muted"><i class="fa fa-list-alt me-1"></i>Subtotal</span>
                                    <span class="fw-semibold">{{ currency($order->gross_amount) }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1">
                                    <span class="text-muted"><i class="fa fa-scissors me-1"></i>Stitch Amount</span>
                                    <span class="fw-semibold">{{ currency($order->stitch_amount) }}</span>
                                </div>
                                @if ($order->discount)
                                    <div class="d-flex justify-content-between py-1 text-primary">
                                        <span><i class="fa fa-tag me-1"></i>Discount</span>
                                        <span class="fw-semibold">-{{ currency($order->discount) }}</span>
                                    </div>
                                @endif
                                <hr class="my-2">
                                <div class="d-flex justify-content-between py-1">
                                    <span class="fw-semibold"><i class="fa fa-calculator me-1"></i>Total</span>
                                    <span class="fw-bold">{{ currency($order->total) }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1 text-primary">
                                    <span class="fw-semibold"><i class="fa fa-check-circle me-1"></i>Paid</span>
                                    <span class="fw-bold">{{ currency($order->paid) }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1 text-primary">
                                    <span class="fw-semibold"><i class="fa fa-exchange me-1"></i>Balance</span>
                                    <span class="fw-bold">{{ currency($order->balance) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="d-flex flex-column gap-3">
                        <div class="card border-0 shadow-sm composition-card">
                            <div class="card-header bg-white border-bottom">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                    <div>
                                        <h2 class="h5 mb-0"><i class="fa fa-list-ul me-1"></i>Order Composition</h2>
                                        <div class="text-muted small"><i class="fa fa-cubes me-1"></i>{{ count($order->items) }} items</div>
                                    </div>
                                    @if (count($this->categoryTabs) > 0)
                                        <ul class="nav nav-pills gap-1 bg-light p-1 rounded-3">
                                            @foreach ($this->categoryTabs as $category)
                                                @php $catId = (string) ($category['id'] ?? 'other'); @endphp
                                                <li class="nav-item">
                                                    <button type="button" wire:click="setActiveTab('{{ $catId }}')"
                                                        class="nav-link px-3 py-1 rounded-2 {{ $activeCategoryTab === $catId ? 'active' : 'text-primary' }}">
                                                        {{ $category['name'] }} ({{ $category['count'] }})
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>

                            <div class="card-body" wire:key="category-content-{{ $activeCategoryTab }}">
                                @php
                                    $measurementData = $this->getMeasurementsCommonAndSeparate($activeCategoryTab);
                                    $sectionLabels = [
                                        'basic_body' => ['label' => 'Dimensions', 'icon' => 'fa-arrows-h'],
                                        'collar_cuff' => ['label' => 'Components', 'icon' => 'fa-puzzle-piece'],
                                        'specifications' => ['label' => 'Styles & Models', 'icon' => 'fa-cut'],
                                    ];
                                @endphp

                                <div class="d-flex flex-column gap-3">
                                    @if ($measurementData['referenceItem'])
                                        @php
                                            $measurementsCollapseId = 'measurements-common-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($activeCategoryTab ?? 'cat'));
                                        @endphp
                                        <div class="card bg-primary-subtle border-0 measurements-card">
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                                    <h3 class="h6 mb-0"><i class="fa fa-arrows-h me-1"></i>Measurements (Common Values)</h3>
                                                    <a href="{{ route('tailoring::order::print-cutting-slip', ['id' => $order->id, 'category_id' => $activeCategoryTab, 'model_id' => 'all']) }}"
                                                        target="_blank" class="btn btn-outline-primary btn-sm">
                                                        <i class="fa fa-print me-1"></i> Print Cutting Slip
                                                    </a>
                                                </div>
                                                <div class="mb-3 d-flex justify-content-center">
                                                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $measurementsCollapseId }}"
                                                        aria-expanded="false" aria-controls="{{ $measurementsCollapseId }}">
                                                        <i class="fa fa-columns me-1"></i> Show Measurement Details
                                                    </button>
                                                </div>
                                                <div id="{{ $measurementsCollapseId }}" class="collapse">
                                                    <div class="row g-2">
                                                        @foreach (['basic_body', 'collar_cuff', 'specifications'] as $sectionId)
                                                            @php
                                                                $sLabel = $sectionLabels[$sectionId] ?? ['label' => $sectionId, 'icon' => 'fa-list'];
                                                                $commonInSection = collect($measurementData['common'])->where('section', $sectionId);
                                                            @endphp
                                                            @if ($commonInSection->isNotEmpty())
                                                                <div class="col-md-6">
                                                                    <div class="card h-100 shadow-sm border-0">
                                                                        <div class="card-header bg-white small fw-semibold border-bottom">
                                                                            <i class="fa {{ $sLabel['icon'] }} me-1"></i>{{ $sLabel['label'] }}
                                                                        </div>
                                                                        <ul class="list-group list-group-flush">
                                                                            @foreach ($commonInSection as $entry)
                                                                                <li class="list-group-item d-flex justify-content-between small">
                                                                                    <span class="text-muted">{{ $entry['label'] }}</span>
                                                                                    <span class="fw-semibold">{{ $entry['value'] ?? '-' }}</span>
                                                                                </li>
                                                                            @endforeach
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>

                                                    @if (count($measurementData['separate']) > 0)
                                                        <div class="mt-3">
                                                            <h6 class="small text-muted text-uppercase mb-2"><i class="fa fa-list-ol me-1"></i>Per-item Reference</h6>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-bordered align-middle mb-0">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th>#</th>
                                                                            @foreach ($measurementData['separate'] as $entry)
                                                                                <th>{{ $entry['label'] }}</th>
                                                                            @endforeach
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach ($measurementData['items'] as $item)
                                                                            <tr>
                                                                                <td class="fw-semibold">{{ $item->item_no }}</td>
                                                                                @foreach ($measurementData['separate'] as $fieldKey => $entry)
                                                                                    @php $val = $item->$fieldKey ?? null; @endphp
                                                                                    <td>{{ $val ?? '-' }}</td>
                                                                                @endforeach
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @php
                                                        $itemsWithNotes = $measurementData['items']->filter(fn($item) => !empty(trim($item->tailoring_notes ?? '')));
                                                    @endphp
                                                    @if ($itemsWithNotes->isNotEmpty())
                                                        <div class="alert alert-primary mt-3 mb-0">
                                                            <div class="fw-semibold mb-1"><i class="fa fa-exclamation-triangle me-1"></i>Special Instructions</div>
                                                            <ul class="mb-0 ps-3">
                                                                @foreach ($itemsWithNotes as $item)
                                                                    <li><span class="fw-semibold">#{{ $item->item_no }}:</span> {{ $item->tailoring_notes }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @foreach ($measurementData['items'] as $item)
                                        @php
                                            $phaseColors = [
                                                'pending' => 'primary',
                                                'partially completed' => 'primary',
                                                'completed' => 'primary',
                                            ];
                                            $phaseKey = strtolower((string) $item->status);
                                            $phaseColor = $phaseColors[$phaseKey] ?? 'secondary';
                                        @endphp
                                        @php
                                            $itemCollapseId = 'item-summary-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($item->id ?? ($item->item_no ?? $loop->index)));
                                        @endphp
                                        <div class="card border-0 shadow-sm border-start border-4 border-primary-subtle item-summary-card">
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                                                    <div>
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <span class="badge rounded-pill text-bg-primary"><i class="fa fa-hashtag me-1"></i>Item {{ $item->item_no }}</span>
                                                            <h3 class="h6 mb-0">{{ $item->product_name }}</h3>
                                                        </div>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <span class="badge rounded-pill text-bg-light border">{{ $item->category->name ?? 'Category' }}</span>
                                                            <span class="badge rounded-pill text-bg-light border">{{ $item->categoryModel->name ?? 'Standard' }}</span>
                                                            @if ($item->categoryModelType)
                                                                <span class="badge rounded-pill text-bg-light border">{{ $item->categoryModelType->name }}</span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="d-flex align-items-center gap-2">
                                                        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $itemCollapseId }}"
                                                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="{{ $itemCollapseId }}">
                                                            <i class="fa fa-chevron-down me-1"></i> Show Tailors
                                                        </button>
                                                        <a href="{{ route('tailoring::order::print-cutting-slip', ['id' => $order->id, 'category_id' => $item->tailoring_category_id, 'model_id' => $item->tailoring_category_model_id ?: 'all']) }}"
                                                            target="_blank" class="btn btn-outline-primary btn-sm">
                                                            <i class="fa fa-print"></i>
                                                        </a>
                                                        <span class="badge rounded-pill text-bg-{{ $phaseColor }}">{{ ucwords($item->status) }}</span>
                                                        <span class="fw-bold">{{ currency($item->total) }}</span>
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered mb-0 align-middle">
                                                            <tbody>
                                                                <tr>
                                                                    <th class="table-light"><i class="fa fa-cube me-1"></i>Quantity</th>
                                                                    <td>{{ $item->quantity }} {{ $item->unit?->name ?? 'Nos' }}</td>
                                                                    <th class="table-light"><i class="fa fa-check-square-o me-1"></i>Completed Qty</th>
                                                                    <td>{{ number_format($item->completed_quantity, 3) }} {{ $item->unit?->name ?? 'Nos' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th class="table-light"><i class="fa fa-tint me-1"></i>Color</th>
                                                                    <td>{{ $item->product_color ?: 'N/A' }}</td>
                                                                    <th class="table-light"><i class="fa fa-money me-1"></i>Stitch Rate</th>
                                                                    <td>{{ currency($item->stitch_rate) }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    @if ($item->tailorAssignments && $item->tailorAssignments->count() > 0)
                                                        <div id="{{ $itemCollapseId }}" class="mt-3 collapse {{ $loop->first ? 'show' : '' }}">
                                                            <div class="card border-0 shadow-sm tailor-assignment-card">
                                                                <div class="card-header bg-primary-subtle text-primary-emphasis fw-semibold d-flex justify-content-between align-items-center">
                                                                    <span><i class="fa fa-users me-1"></i>Tailor Assignment (Unit-wise)</span>
                                                                    <span class="badge text-bg-primary">{{ $item->tailorAssignments->count() }} units</span>
                                                                </div>
                                                                <div class="card-body p-0">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm table-bordered align-middle mb-0">
                                                                            <thead class="table-light">
                                                                                <tr>
                                                                                    <th><i class="fa fa-hashtag me-1"></i>Piece</th>
                                                                                    <th><i class="fa fa-user me-1"></i>Tailor</th>
                                                                                    <th class="text-end"><i class="fa fa-money me-1"></i>Commission</th>
                                                                                    <th><i class="fa fa-calendar me-1"></i>Completion Date</th>
                                                                                    <th><i class="fa fa-star me-1"></i>Rating</th>
                                                                                    <th><i class="fa fa-flag me-1"></i>Status</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($item->tailorAssignments as $assignmentIndex => $assignment)
                                                                                    @php
                                                                                        $assignmentStatus = $assignment->status;
                                                                                        $assignmentStatusClass = match ($assignmentStatus) {
                                                                                            'delivered' => 'success',
                                                                                            'completed' => 'primary',
                                                                                            default => 'warning',
                                                                                        };
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td class="fw-semibold">#{{ $assignmentIndex + 1 }}</td>
                                                                                        <td>{{ $assignment->tailor?->name ?? '-' }}</td>
                                                                                        <td class="text-end">{{ currency($assignment->tailor_commission ?? 0) }}</td>
                                                                                        <td>{{ $assignment->completion_date ? systemDate($assignment->completion_date) : '-' }}</td>
                                                                                        <td>{{ $assignment->rating ? $assignment->rating . '/5' : '-' }}</td>
                                                                                        <td>
                                                                                            <span class="badge rounded-pill text-bg-{{ $assignmentStatusClass }}">
                                                                                                {{ ucfirst($assignmentStatus) }}
                                                                                            </span>
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    @php
                        $hasPayments = $order->payments->count() > 0;
                        $hasJournals = count($order->journals ?? []) > 0;
                        $canViewJournals = auth()->user()?->can('tailoring order.view journal entries');
                        $paymentTabActive = $hasPayments;
                        $journalTabActive = !$hasPayments && $hasJournals && $canViewJournals;
                        $auditTabActive = !$hasPayments && (!$canViewJournals || !$hasJournals);
                    @endphp

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white pb-0 border-bottom">
                            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $paymentTabActive ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tailoring-tab-payment-details" type="button"
                                        role="tab">
                                        <i class="fa fa-credit-card me-1"></i>Payment Details @if ($hasPayments)
                                            <span class="badge text-bg-primary ms-1">{{ $order->payments->count() }}</span>
                                        @endif
                                    </button>
                                </li>
                                @can('tailoring order.view journal entries')
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $journalTabActive ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tailoring-tab-journal-entries" type="button"
                                            role="tab">
                                            <i class="fa fa-book me-1"></i>Journal Entries @if ($hasJournals)
                                                <span class="badge text-bg-primary ms-1">{{ count($order->journals) }}</span>
                                            @endif
                                        </button>
                                    </li>
                                @endcan
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $auditTabActive ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tailoring-tab-audit-report" type="button"
                                        role="tab">
                                        <i class="fa fa-history me-1"></i>Audit Report
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body">
                            <div class="tab-content">
                                <div id="tailoring-tab-payment-details" class="tab-pane fade {{ $paymentTabActive ? 'show active' : '' }}" role="tabpanel">
                                    @if ($order->payments->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><i class="fa fa-calendar me-1"></i>Date</th>
                                                        <th><i class="fa fa-credit-card me-1"></i>Method</th>
                                                        <th class="text-end"><i class="fa fa-money me-1"></i>Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($order->payments as $payment)
                                                        <tr>
                                                            <td>{{ systemDate($payment->date) }}</td>
                                                            <td>{{ $payment->paymentMethod->name ?? 'Cash' }}</td>
                                                            <td class="text-end fw-semibold">{{ currency($payment->amount) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary mb-0">No payment transactions for this order.</div>
                                    @endif
                                </div>

                                @can('tailoring order.view journal entries')
                                    <div id="tailoring-tab-journal-entries" class="tab-pane fade {{ $journalTabActive ? 'show active' : '' }}" role="tabpanel">
                                        @if (count($order->journals ?? []) > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="text-end"><i class="fa fa-hashtag me-1"></i>SL No</th>
                                                            <th><i class="fa fa-calendar me-1"></i>Date</th>
                                                            <th><i class="fa fa-user me-1"></i>Account</th>
                                                            <th><i class="fa fa-file-text-o me-1"></i>Description</th>
                                                            <th class="text-end"><i class="fa fa-arrow-down me-1"></i>Debit</th>
                                                            <th class="text-end"><i class="fa fa-arrow-up me-1"></i>Credit</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($order->journals as $journal)
                                                            @foreach ($journal->entries()->where('account_id', '!=', $order->account_id)->get() as $entry)
                                                                <tr>
                                                                    <td class="text-end">{{ $entry->id }}</td>
                                                                    <td>{{ systemDate($entry->date) }}</td>
                                                                    <td>
                                                                        <a href="{{ route('account::view', $entry->account_id) }}" class="text-decoration-none">
                                                                            {{ $entry->account?->name }}
                                                                        </a>
                                                                    </td>
                                                                    <td>{{ $entry->remarks }}</td>
                                                                    <td class="text-end">{{ currency($entry->debit) }}</td>
                                                                    <td class="text-end">{{ currency($entry->credit) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="alert alert-secondary mb-0">No journal entries for this order.</div>
                                        @endif
                                    </div>
                                @endcan

                                <div id="tailoring-tab-audit-report" class="tab-pane fade {{ $auditTabActive ? 'show active' : '' }}" role="tabpanel">
                                    <ul class="nav nav-pills mb-3 gap-2" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#audit-order" type="button"><i class="fa fa-file-text-o me-1"></i>Order</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#audit-order-items" type="button"><i class="fa fa-list me-1"></i>Order Items</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#audit-tailor-assignments" type="button"><i class="fa fa-users me-1"></i>Tailor Assignments</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#audit-order-measurement" type="button"><i class="fa fa-arrows-h me-1"></i>Measurements</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#audit-payments" type="button"><i class="fa fa-credit-card me-1"></i>Payments</button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <div id="audit-order" class="tab-pane fade show active" role="tabpanel">
                                            @php $orderAudits = $order->audits ?? collect(); @endphp
                                            @if ($orderAudits->isNotEmpty())
                                                @php
                                                    $orderColumns = $orderAudits
                                                        ->pluck('new_values')
                                                        ->filter()
                                                        ->map(fn($item) => is_array($item) ? array_keys($item) : [])
                                                        ->flatten()
                                                        ->unique()
                                                        ->values()
                                                        ->all();
                                                @endphp
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th><i class="fa fa-clock-o me-1"></i>Date Time</th>
                                                                <th><i class="fa fa-user me-1"></i>User</th>
                                                                <th><i class="fa fa-bolt me-1"></i>Event</th>
                                                                @foreach ($orderColumns as $key)
                                                                    <th class="text-end">{{ Str::title(str_replace('_', ' ', $key)) }}</th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($orderAudits as $audit)
                                                                <tr>
                                                                    <td class="text-nowrap">{{ $audit->created_at }}</td>
                                                                    <td>{{ $audit->user?->name }}</td>
                                                                    <td>{{ $audit->event }}</td>
                                                                    @foreach ($orderColumns as $key)
                                                                        <td class="text-end text-nowrap">{{ $audit->new_values[$key] ?? '' }}</td>
                                                                    @endforeach
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="alert alert-secondary mb-0">No audit history for this order.</div>
                                            @endif
                                        </div>

                                        <div id="audit-order-items" class="tab-pane fade" role="tabpanel">
                                            @php $itemAudits = collect($order->items ?? [])->flatMap->audits; @endphp
                                            @if ($itemAudits->isNotEmpty())
                                                @php
                                                    $itemColumns = $itemAudits
                                                        ->pluck('new_values')
                                                        ->filter()
                                                        ->map(fn($item) => is_array($item) ? array_keys($item) : [])
                                                        ->flatten()
                                                        ->unique()
                                                        ->values()
                                                        ->all();
                                                @endphp
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th><i class="fa fa-clock-o me-1"></i>Date Time</th>
                                                                <th><i class="fa fa-user me-1"></i>User</th>
                                                                <th><i class="fa fa-bolt me-1"></i>Event</th>
                                                                @foreach ($itemColumns as $key)
                                                                    <th class="text-end">{{ Str::title(str_replace('_', ' ', $key)) }}</th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($itemAudits as $audit)
                                                                <tr>
                                                                    <td class="text-nowrap">{{ $audit->created_at }}</td>
                                                                    <td>{{ $audit->user?->name }}</td>
                                                                    <td>{{ $audit->event }}</td>
                                                                    @foreach ($itemColumns as $key)
                                                                        <td class="text-end text-nowrap">{{ $audit->new_values[$key] ?? '' }}</td>
                                                                    @endforeach
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="alert alert-secondary mb-0">No audit history for order items.</div>
                                            @endif
                                        </div>
                                        <div id="audit-tailor-assignments" class="tab-pane fade" role="tabpanel">
                                            @php
                                                $assignmentAudits = collect($order->items ?? [])->flatMap->tailorAssignments->flatMap->audits;
                                            @endphp
                                            @if ($assignmentAudits->isNotEmpty())
                                                @php
                                                    $assignmentColumns = $assignmentAudits
                                                        ->pluck('new_values')
                                                        ->filter()
                                                        ->map(fn($item) => is_array($item) ? array_keys($item) : [])
                                                        ->flatten()
                                                        ->unique()
                                                        ->values()
                                                        ->all();
                                                @endphp
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th><i class="fa fa-clock-o me-1"></i>Date Time</th>
                                                                <th><i class="fa fa-user me-1"></i>User</th>
                                                                <th><i class="fa fa-bolt me-1"></i>Event</th>
                                                                @foreach ($assignmentColumns as $key)
                                                                    <th class="text-end">{{ Str::title(str_replace('_', ' ', $key)) }}</th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($assignmentAudits as $audit)
                                                                <tr>
                                                                    <td class="text-nowrap">{{ $audit->created_at }}</td>
                                                                    <td>{{ $audit->user?->name }}</td>
                                                                    <td>{{ $audit->event }}</td>
                                                                    @foreach ($assignmentColumns as $key)
                                                                        <td class="text-end text-nowrap">{{ $audit->new_values[$key] ?? '' }}</td>
                                                                    @endforeach
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="alert alert-secondary mb-0">No audit history for tailor assignments.</div>
                                            @endif
                                        </div>

                                        <div id="audit-order-measurement" class="tab-pane fade" role="tabpanel">
                                            @php $measurementAudits = collect($order->measurements ?? [])->flatMap->audits; @endphp
                                            @if ($measurementAudits->isNotEmpty())
                                                @php
                                                    $measurementColumns = $measurementAudits
                                                        ->pluck('new_values')
                                                        ->filter()
                                                        ->map(fn($item) => is_array($item) ? array_keys($item) : [])
                                                        ->flatten()
                                                        ->unique()
                                                        ->values()
                                                        ->all();
                                                @endphp
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th><i class="fa fa-clock-o me-1"></i>Date Time</th>
                                                                <th><i class="fa fa-user me-1"></i>User</th>
                                                                <th><i class="fa fa-bolt me-1"></i>Event</th>
                                                                @foreach ($measurementColumns as $key)
                                                                    <th class="text-end">{{ Str::title(str_replace('_', ' ', $key)) }}</th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($measurementAudits as $audit)
                                                                <tr>
                                                                    <td class="text-nowrap">{{ $audit->created_at }}</td>
                                                                    <td>{{ $audit->user?->name }}</td>
                                                                    <td>{{ $audit->event }}</td>
                                                                    @foreach ($measurementColumns as $key)
                                                                        <td class="text-end text-nowrap">{{ $audit->new_values[$key] ?? '' }}</td>
                                                                    @endforeach
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="alert alert-secondary mb-0">No audit history for order measurements.</div>
                                            @endif
                                        </div>

                                        <div id="audit-payments" class="tab-pane fade" role="tabpanel">
                                            @php $paymentAudits = collect($order->payments ?? [])->flatMap->audits; @endphp
                                            @if ($paymentAudits->isNotEmpty())
                                                @php
                                                    $paymentColumns = $paymentAudits
                                                        ->pluck('new_values')
                                                        ->filter()
                                                        ->map(fn($item) => is_array($item) ? array_keys($item) : [])
                                                        ->flatten()
                                                        ->unique()
                                                        ->values()
                                                        ->all();
                                                @endphp
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th><i class="fa fa-clock-o me-1"></i>Date Time</th>
                                                                <th><i class="fa fa-user me-1"></i>User</th>
                                                                <th><i class="fa fa-bolt me-1"></i>Event</th>
                                                                @foreach ($paymentColumns as $key)
                                                                    <th class="text-end">{{ Str::title(str_replace('_', ' ', $key)) }}</th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($paymentAudits as $audit)
                                                                <tr>
                                                                    <td class="text-nowrap">{{ $audit->created_at }}</td>
                                                                    <td>{{ $audit->user?->name }}</td>
                                                                    <td>{{ $audit->event }}</td>
                                                                    @foreach ($paymentColumns as $key)
                                                                        <td class="text-end text-nowrap">{{ $audit->new_values[$key] ?? '' }}</td>
                                                                    @endforeach
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="alert alert-secondary mb-0">No audit history for payments.</div>
                                            @endif
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
    <style>
        .tailoring-view-page {
            background: linear-gradient(180deg, #f3f6fb 0%, #f8fafc 42%, #ffffff 100%);
        }

        .tailoring-view-page .card {
            border-radius: 14px;
            box-shadow: 0 6px 22px rgba(15, 23, 42, 0.06) !important;
        }

        .tailoring-view-page .order-hero-card {
            border: 1px solid #dbe8ff !important;
            background: linear-gradient(120deg, #eff6ff 0%, #eef2ff 100%) !important;
        }

        .tailoring-view-page .metric-card .card-body {
            padding: 1rem 1.1rem;
        }

        .tailoring-view-page .metric-card .h5 {
            font-size: 1.15rem;
        }

        .tailoring-view-page .info-card .card-header,
        .tailoring-view-page .composition-card .card-header,
        .tailoring-view-page .tailor-assignment-card .card-header,
        .tailoring-view-page .measurements-card .card-header {
            font-size: 0.9rem;
            font-weight: 700;
        }

        .tailoring-view-page .composition-card .card-body {
            padding: 1rem;
        }

        .tailoring-view-page .item-summary-card {
            border-left-color: #93c5fd !important;
        }

        .tailoring-view-page .item-summary-card .card-body {
            padding: 1rem;
        }

        .tailoring-view-page .table th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #475569;
            white-space: nowrap;
        }

        .tailoring-view-page .table td {
            font-size: 0.88rem;
            color: #0f172a;
        }

        .tailoring-view-page .table th,
        .tailoring-view-page .table td {
            padding: 0.6rem 0.7rem;
        }

        .tailoring-view-page .badge {
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        @media (max-width: 992px) {
            .tailoring-view-page .card-body {
                padding: 0.85rem;
            }

            .tailoring-view-page .metric-card .h5 {
                font-size: 1.02rem;
            }
        }
    </style>
</div>
