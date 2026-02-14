<div>
    <div class="min-h-screen bg-light bg-opacity-50 pb-5">
        <!-- Top Navigation Bar -->
        <div class="bg-white border-bottom sticky-top z-index-1000 shadow-sm">
            <div class="container-xl px-4">
                <div class="d-flex justify-content-between align-items-center" style="height: 64px;">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h1 class="h6 fw-bold text-dark mb-0">Order #{{ $order->order_no }}</h1>
                                @php
                                    $statusColors = [
                                        'Pending' => 'warning',
                                        'Confirmed' => 'primary',
                                        'Completed' => 'success',
                                        'Cancelled' => 'danger',
                                        'Delivered' => 'dark',
                                    ];
                                    $color = $statusColors[$order->status] ?? 'secondary';
                                @endphp
                                <span
                                    class="badge rounded-pill fw-bold text-uppercase px-2 py-1 bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }} border-opacity-25"
                                    style="font-size: 0.65rem;">
                                    {{ $order->status }}
                                </span>
                            </div>
                            <p class="small text-muted mb-0 fw-medium" style="font-size: 0.75rem;">Created: {{ systemDate($order->order_date) }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-none d-md-flex gap-2 me-3">
                            <a href="{{ route('tailoring::job-completion::index') }}?order_no={{ $order->order_no }}"
                                class="btn btn-white border shadow-sm d-flex align-items-center gap-2 py-2 px-3 rounded-3 text-success fw-bold small">
                                <i class="fa fa-check-circle"></i>
                                <span>Goto Job Completion</span>
                            </a>
                        </div>
                        <a href="{{ route('tailoring::order::edit', $order->id) }}" class="btn btn-primary rounded-3 py-2 px-3 fw-bold small shadow-sm d-flex align-items-center gap-2">
                            <i class="fa fa-edit"></i>
                            Edit
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-xl mt-4 px-4">
            <div class="row g-4">
                <!-- Sidebar (4 cols) -->
                <div class="col-lg-4">
                    <div class="d-flex flex-column gap-4">
                        <!-- Customer Information -->
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-body p-4 bg-light bg-opacity-50">
                                <p class="text-uppercase fw-bold text-muted small mb-3" style="letter-spacing: 0.1em;">Customer Details</p>
                                <div class="d-flex align-items-start gap-3">
                                    <div class="bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold rounded-4 shadow-sm border border-primary border-opacity-10"
                                        style="width: 56px; height: 56px; font-size: 1.5rem;">
                                        {{ substr($order->customer_name, 0, 1) }}
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <h3 class="h6 fw-bold text-dark mb-1 text-truncate">{{ $order->customer_name }}</h3>
                                        <p class="small text-muted d-flex align-items-center gap-2 mb-0">
                                            <i class="fa fa-phone opacity-50"></i>
                                            {{ $order->customer_mobile ?: 'No mobile' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4 border-top">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 0.65rem;">Branch</p>
                                        <p class="small fw-bold text-dark mb-0 text-truncate">{{ $order->branch->name ?? 'Main' }}</p>
                                    </div>
                                    <div class="col-6 text-end">
                                        <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 0.65rem;">Salesman</p>
                                        <p class="small fw-bold text-dark mb-0 text-truncate">{{ $order->salesman->name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <div class="d-flex align-items-center gap-2 mb-4">
                                <div class="bg-primary rounded-pill" style="width: 4px; height: 16px;"></div>
                                <h3 class="text-uppercase fw-bold text-muted mb-0" style="font-size: 0.7rem; letter-spacing: 0.1em;">Timeline</h3>
                            </div>
                            <div class="d-flex flex-column gap-4">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center text-muted shrink-0" style="width: 36px; height: 36px;">
                                        <i class="fa fa-calendar-o"></i>
                                    </div>
                                    <div class="flex-grow-1 border-bottom pb-2">
                                        <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 0.65rem;">Order Date</p>
                                        <p class="small fw-bold text-dark mb-0">{{ systemDate($order->order_date) }}</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3">
                                    <div class="bg-primary bg-opacity-10 border border-primary border-opacity-10 rounded-3 d-flex align-items-center justify-content-center text-primary shrink-0"
                                        style="width: 36px; height: 36px;">
                                        <i class="fa fa-clock-o"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 0.65rem;">Target Delivery</p>
                                        <p class="small fw-bold text-primary mb-0">{{ systemDate($order->delivery_date) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Overview -->
                        <div class="card border-0 shadow-sm rounded-4 p-4 position-relative overflow-hidden">
                            <div class="position-absolute top-0 end-0 bg-primary bg-opacity-10 rounded-circle" style="width: 120px; height: 120px; margin-top: -60px; margin-right: -60px;"></div>
                            <h3 class="text-uppercase fw-bold text-muted mb-4 position-relative z-index-1" style="font-size: 0.7rem; letter-spacing: 0.1em;">Financial Overview</h3>
                            <div class="d-flex flex-column gap-3 position-relative z-index-1">
                                <div class="d-flex justify-content-between align-items-center small">
                                    <span class="text-secondary fw-semibold">Subtotal</span>
                                    <span class="text-dark fw-bold">{{ currency($order->gross_amount) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center small">
                                    <span class="text-secondary fw-semibold">Stitch Rate</span>
                                    <span class="text-dark fw-bold">{{ currency($order->stitch_amount) }}</span>
                                </div>
                                @if ($order->discount)
                                    <div class="d-flex justify-content-between align-items-center small text-danger">
                                        <span class="fw-semibold">Discount Applied</span>
                                        <span class="fw-bold">-{{ currency($order->discount) }}</span>
                                    </div>
                                @endif
                                <div class="pt-3 mt-1 border-top d-flex justify-content-between align-items-center">
                                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.65rem;">Total</span>
                                    <span class="h4 fw-bolder text-dark mb-0">{{ currency($order->total) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-2 px-3 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-10">
                                    <span class="small fw-bold text-success">Amount Paid</span>
                                    <span class="fw-bolder text-success">{{ currency($order->paid) }}</span>
                                </div>
                                <div
                                    class="d-flex justify-content-between align-items-center py-3 px-3 rounded-3 mt-1 {{ $order->balance > 0 ? 'bg-light border' : 'bg-success bg-opacity-25 border border-success border-opacity-10' }}">
                                    <span class="text-uppercase fw-bold {{ $order->balance > 0 ? 'text-muted' : 'text-success' }}" style="font-size: 0.65rem;">Remaining Balance</span>
                                    <span class="h5 fw-bolder mb-0 {{ $order->balance > 0 ? 'text-dark' : 'text-success' }}">{{ currency($order->balance) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content (8 cols) -->
                <div class="col-lg-8">
                    <div class="d-flex flex-column gap-4">
                        <!-- Items Card -->
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-white border-bottom-0 p-4 pt-5 px-5">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4">
                                    <div>
                                        <h2 class="h3 fw-bolder text-dark mb-1">Order Composition</h2>
                                        <p class="text-uppercase fw-bold text-muted small mb-0" style="font-size: 0.7rem; letter-spacing: 0.1em;">{{ count($order->items) }} Total Items</p>
                                    </div>

                                    <!-- Category Tabs (Pills) -->
                                    @if (count($this->categoryTabs) > 0)
                                        <div class="bg-light p-1 rounded-4 d-flex gap-1 shadow-sm border border-light">
                                            @foreach ($this->categoryTabs as $category)
                                                @php $catId = (string) ($category['id'] ?? 'other'); @endphp
                                                <button type="button" wire:click="setActiveTab('{{ $catId }}')"
                                                    class="btn py-2 px-4 rounded-4 small fw-bold border-0 {{ $activeCategoryTab === $catId ? 'bg-white text-primary shadow-sm' : 'text-muted' }}"
                                                    style="font-size: 0.75rem;">
                                                    {{ $category['name'] }} <span class="ms-1 opacity-50">{{ $category['count'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="card-body p-5 pt-4" wire:key="category-content-{{ $activeCategoryTab }}">
                                @php
                                    $measurementData = $this->getMeasurementsCommonAndSeparate($activeCategoryTab);
                                    $sectionLabels = [
                                        'basic_body' => ['label' => 'DIMENSIONS', 'icon' => 'fa-ruler-combined'],
                                        'collar_cuff' => ['label' => 'COMPONENTS', 'icon' => 'fa-puzzle-piece'],
                                        'specifications' => ['label' => 'STYLES & MODELS', 'icon' => 'fa-cut'],
                                    ];
                                @endphp

                                <div class="d-flex flex-column gap-5">
                                    {{-- Single common measurements block (common + separate values) --}}
                                    @if ($measurementData['referenceItem'])
                                        <div class="bg-light bg-opacity-25 rounded-5 border p-4 border-opacity-50">
                                            <div class="d-flex align-items-center gap-2 mb-4 px-1">
                                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                                    <div class="bg-primary bg-opacity-10 rounded-pill px-3 py-1">
                                                        <span class="text-uppercase fw-bold text-primary small" style="font-size: 0.65rem; letter-spacing: 0.05em;">
                                                            <i class="fa fa-ruler me-2"></i>Measurements (same for all items)
                                                        </span>
                                                    </div>
                                                    <a href="{{ route('tailoring::order::print-cutting-slip', ['id' => $order->id, 'category_id' => $activeCategoryTab, 'model_id' => 'all']) }}"
                                                        target="_blank" class="btn btn-sm btn-light border rounded-4 shadow-sm text-muted d-flex align-items-center gap-2"
                                                        title="Print Cutting Slip for this category">
                                                        <i class="fa fa-print"></i>
                                                        <span class="small fw-bold">Print Cutting Slip</span>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="row g-4">
                                                @foreach (['basic_body', 'collar_cuff', 'specifications'] as $sectionId)
                                                    @php
                                                        $sLabel = $sectionLabels[$sectionId] ?? ['label' => $sectionId, 'icon' => 'fa-list'];
                                                        $commonInSection = collect($measurementData['common'])->where('section', $sectionId);
                                                        $separateInSection = collect($measurementData['separate'])->where('section', $sectionId);
                                                    @endphp
                                                    @if ($commonInSection->isNotEmpty())
                                                        <div class="col-xl-4 col-md-6">
                                                            <div class="text-uppercase fw-bold text-muted small mb-2 ps-1">
                                                                <i class="fa {{ $sLabel['icon'] }} me-2"></i>{{ $sLabel['label'] }}
                                                            </div>
                                                            <div class="card shadow-sm rounded-3 overflow-hidden border">
                                                                @foreach ($commonInSection as $key => $entry)
                                                                    <div class="row g-0 border-bottom @if ($loop->last) border-bottom-0 @endif">
                                                                        <div class="col-7 bg-light p-2 fw-semibold text-muted small border-end d-flex align-items-center">{{ $entry['label'] }}</div>
                                                                        <div
                                                                            class="col-5 p-2 fw-bold text-dark small d-flex align-items-center {{ !($entry['value'] ?? null) ? 'text-muted opacity-50' : '' }}">
                                                                            {{ $entry['value'] ?? '-' }}
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                            {{-- Per-item measurements: show in Measurements area with Product No for reference --}}
                                            @if (count($measurementData['separate']) > 0)
                                                <div class="mt-4">
                                                    <div class="text-uppercase fw-bold text-muted small mb-2 ps-1">
                                                        <i class="fa fa-list-ol me-2"></i>Per-item reference
                                                    </div>
                                                    <div class="card shadow-sm rounded-3 overflow-hidden border">
                                                        <div class="table-responsive mb-0">
                                                            <table class="table table-sm table-hover align-middle mb-0 small">
                                                                <thead class="bg-light">
                                                                    <tr>
                                                                        <th class="ps-3 py-2 fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Product No</th>
                                                                        @foreach ($measurementData['separate'] as $entry)
                                                                            <th class="py-2 fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">{{ $entry['label'] }}</th>
                                                                        @endforeach
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($measurementData['items'] as $item)
                                                                        <tr>
                                                                            <td class="ps-3 py-2 fw-bolder text-dark">#{{ $item->item_no }}</td>
                                                                            @foreach ($measurementData['separate'] as $fieldKey => $entry)
                                                                                @php $val = $item->$fieldKey ?? null; @endphp
                                                                                <td class="py-2 fw-bold {{ $val !== null && $val !== '' ? 'text-dark' : 'text-muted' }}">{{ $val ?? '-' }}</td>
                                                                            @endforeach
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @php
                                                $itemsWithNotes = $measurementData['items']->filter(fn($item) => !empty(trim($item->tailoring_notes ?? '')));
                                            @endphp
                                            @if ($itemsWithNotes->isNotEmpty())
                                                <div class="mt-4 p-3 bg-warning bg-opacity-10 border border-warning rounded-3 border-opacity-25" style="border-style: dashed !important;">
                                                    <div class="small fw-bold text-warning-emphasis text-uppercase mb-2" style="letter-spacing: 0.05em;">
                                                        <i class="fa fa-info-circle me-1"></i>SPECIAL INSTRUCTIONS
                                                    </div>
                                                    <div class="d-flex flex-column gap-2">
                                                        @foreach ($itemsWithNotes as $item)
                                                            <div class="text-dark-emphasis fw-medium small">
                                                                <span class="badge bg-warning bg-opacity-25 text-dark me-2">#{{ $item->item_no }}</span>{{ $item->tailoring_notes }}
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    {{-- Products list only (no measurement block per product) --}}
                                    @foreach ($measurementData['items'] as $item)
                                        <div class="bg-white rounded-4">
                                            <div class="row g-4">
                                                <div class="col-auto d-none d-md-flex flex-column align-items-center">
                                                    <div class="bg-white border-2 border-light rounded-4 shadow-sm d-flex align-items-center justify-content-center fw-bolder text-dark"
                                                        style="width: 56px; height: 56px; border-style: solid !important;">
                                                        {{ $item->item_no }}
                                                    </div>
                                                    <div class="flex-grow-1 bg-light mt-3 rounded-pill" style="width: 3px; min-height: 80px;"></div>
                                                </div>

                                                <div class="col flex-grow-1 min-width-0">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div>
                                                            <h3 class="h4 fw-bolder text-dark mb-2">{{ $item->product_name }}</h3>
                                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                                <span class="badge bg-light text-muted fw-bold border text-uppercase px-2 py-1"
                                                                    style="font-size: 0.6rem;">{{ $item->category->name ?? 'Cat' }}</span>
                                                                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold border border-primary border-opacity-10 text-uppercase px-2 py-1"
                                                                    style="font-size: 0.6rem;">{{ $item->categoryModel->name ?? 'Standard' }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-3">
                                                            <a href="{{ route('tailoring::order::print-cutting-slip', ['id' => $order->id, 'category_id' => $item->tailoring_category_id, 'model_id' => $item->tailoring_category_model_id ?: 'all']) }}"
                                                                target="_blank" class="btn btn-light border rounded-4 p-2 shadow-sm text-muted group" title="Cutting Slip">
                                                                <i class="fa fa-print fs-5"></i>
                                                            </a>
                                                            <div class="text-end">
                                                                <p class="text-uppercase fw-bold text-muted mb-0" style="font-size: 0.6rem;">Value</p>
                                                                <p class="h5 fw-bolder text-dark mb-0">{{ currency($item->total) }}</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="px-3">
                                                        <div class="row g-3">
                                                            <div class="col-6 col-md-3 text-center">
                                                                <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 0.6rem;">Volume</p>
                                                                <div class="bg-white border rounded-3 py-2 small fw-bold">
                                                                    {{ $item->quantity }} <span class="text-muted">{{ $item->unit->name ?? 'Nos' }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-6 col-md-3 text-center">
                                                                <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 0.6rem;">Shade</p>
                                                                <div class="bg-white border rounded-3 py-2 small fw-bold">
                                                                    {{ $item->product_color ?: 'N/A' }}
                                                                </div>
                                                            </div>
                                                            <div class="col-6 col-md-3 text-center">
                                                                <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 0.6rem;">Rate</p>
                                                                <div class="bg-white border rounded-3 py-2 small fw-bold">
                                                                    {{ currency($item->stitch_rate) }}
                                                                </div>
                                                            </div>
                                                            @php
                                                                $phaseColors = [
                                                                    'Pending' => 'warning',
                                                                    'In Progress' => 'primary',
                                                                    'Partially Completed' => 'indigo',
                                                                    'Completed' => 'success',
                                                                ];
                                                                $phaseColor = $phaseColors[$item->status] ?? 'secondary';
                                                            @endphp
                                                            <div class="col-6 col-md-3 text-center">
                                                                <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 0.6rem;">Phase</p>
                                                                <div
                                                                    class="rounded-3 py-2 small fw-bold bg-{{ $phaseColor }} bg-opacity-10 text-{{ $phaseColor }} border border-{{ $phaseColor }} border-opacity-10">
                                                                    {{ $item->is_selected_for_completion > 0 ? 'Completed' : 'Pending' }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Payment History Table -->
                        @if (count($order->payments) > 0)
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="card-header bg-white p-4 border-bottom-0 d-flex justify-content-between align-items-center">
                                    <h2 class="h6 fw-bolder text-dark mb-0">Financial History</h2>
                                    <span class="badge bg-light text-muted border px-2 py-1 small">{{ count($order->payments) }} Transactions</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light bg-opacity-50">
                                            <tr>
                                                <th class="ps-4 py-3 text-uppercase fw-bold text-muted" style="font-size: 0.65rem;">Date</th>
                                                <th class="py-3 text-uppercase fw-bold text-muted" style="font-size: 0.65rem;">Method</th>
                                                <th class="pe-4 py-3 text-end text-uppercase fw-bold text-muted" style="font-size: 0.65rem;">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="border-top-0">
                                            @foreach ($order->payments as $payment)
                                                <tr>
                                                    <td class="ps-4 py-3 small fw-medium text-secondary">{{ systemDate($payment->date) }}</td>
                                                    <td class="py-3">
                                                        <div class="d-flex align-items-center gap-2 small fw-bold text-dark">
                                                            <div class="rounded-circle bg-primary" style="width: 6px; height: 6px;"></div>
                                                            {{ $payment->paymentMethod->name ?? 'Cash' }}
                                                        </div>
                                                    </td>
                                                    <td class="pe-4 py-3 text-end fw-bold text-success">{{ currency($payment->amount) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <!-- Journal Entries -->
                        @can('tailoring.order.view journal entries')
                            @if (count($order->journals ?? []) > 0)
                                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                    <div class="card-header bg-white p-4 border-bottom-0 d-flex justify-content-between align-items-center">
                                        <h2 class="h6 fw-bolder text-dark mb-0">Journal Entries</h2>
                                        <span class="badge bg-light text-muted border px-2 py-1 small">{{ count($order->journals) }} Journal(s)</span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped align-middle table-sm mb-0">
                                            <thead>
                                                <tr class="bg-primary text-white">
                                                    <th class="text-white text-end">SL No</th>
                                                    <th class="text-white">Date</th>
                                                    <th class="text-white">Account Name</th>
                                                    <th class="text-white">Description</th>
                                                    <th class="text-white text-end">Debit</th>
                                                    <th class="text-white text-end">Credit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($order->journals as $journal)
                                                    @foreach ($journal->entries()->where('account_id','!=',$order->account_id)->get() as $entry)
                                                        <tr>
                                                            <td class="text-end">{{ $entry->id }}</td>
                                                            <td>{{ systemDate($entry->date) }}</td>
                                                            <td>
                                                                <a href="{{ route('account::view', $entry->account_id) }}" class="text-primary text-decoration-none">
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
                                </div>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printOrder() {
            document.body.classList.remove('print-slips');
            window.print();
        }

        function printCuttingSlips() {
            document.body.classList.add('print-slips');
            window.print();
        }
    </script>

    <style>
        @media print {
            body.print-slips .min-h-screen {
                display: none !important;
            }

            body:not(.print-slips) #cutting-slips-container {
                display: none !important;
            }

            .no-print,
            nav,
            .sticky-top,
            footer {
                display: none !important;
            }

            .page-break-before {
                page-break-before: always;
            }

            .card {
                border: none !important;
                shadow: none !important;
            }

            .p-4 {
                padding: 0 !important;
            }

            .container-xl {
                max-width: 100% !important;
                border: none !important;
            }
        }
    </style>
</div>
