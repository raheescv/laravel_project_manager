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
                        <div class="dropdown">
                            <button class="btn btn-white border shadow-sm rounded-3 fw-bold small dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                                <i class="fa fa-print text-muted"></i>
                                Print
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2">
                                <li><a class="dropdown-item py-2 fw-medium" href="javascript:void(0)" onclick="printOrder()"><i class="fa fa-file-text-o me-2 text-muted"></i> Print Order Summary</a>
                                </li>
                                <li><a class="dropdown-item py-2 fw-medium" href="javascript:void(0)" onclick="printCuttingSlips()"><i class="fa fa-scissors me-2 text-muted"></i> Print Cutting
                                        Slips</a></li>
                            </ul>
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
                                    <span class="h4 fw-bolder text-dark mb-0">{{ currency($order->total_amount) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-2 px-3 bg-success bg-opacity-10 rounded-3 border border-success border-opacity-10">
                                    <span class="small fw-bold text-success">Amount Paid</span>
                                    <span class="fw-bolder text-success">{{ currency($order->paid_amount) }}</span>
                                </div>
                                <div
                                    class="d-flex justify-content-between align-items-center py-3 px-3 rounded-3 mt-1 {{ $order->balance_amount > 0 ? 'bg-light border' : 'bg-success bg-opacity-25 border border-success border-opacity-10' }}">
                                    <span class="text-uppercase fw-bold {{ $order->balance_amount > 0 ? 'text-muted' : 'text-success' }}" style="font-size: 0.65rem;">Remaining Balance</span>
                                    <span class="h5 fw-bolder mb-0 {{ $order->balance_amount > 0 ? 'text-dark' : 'text-success' }}">{{ currency($order->balance_amount) }}</span>
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
                                                <button wire:click="setActiveTab('{{ $category['id'] }}')"
                                                    class="btn py-2 px-4 rounded-4 small fw-bold border-0 {{ $activeCategoryTab == $category['id'] ? 'bg-white text-primary shadow-sm' : 'text-muted' }}"
                                                    style="font-size: 0.75rem;">
                                                    {{ $category['name'] }} <span class="ms-1 opacity-50">{{ $category['count'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="card-body p-5 pt-4">
                                <div class="d-flex flex-column gap-5">
                                    @foreach ($this->getItemsByCategory($activeCategoryTab) as $item)
                                        <div class="bg-white rounded-4">
                                            <div class="row g-4">
                                                <!-- Visual ID Column -->
                                                <div class="col-auto d-none d-md-flex flex-column align-items-center">
                                                    <div class="bg-white border-2 border-light rounded-4 shadow-sm d-flex align-items-center justify-content-center fw-bolder text-dark"
                                                        style="width: 56px; height: 56px; border-style: solid !important;">
                                                        {{ $item->item_no }}
                                                    </div>
                                                    <div class="flex-grow-1 bg-light mt-3 rounded-pill" style="width: 3px; min-height: 100px;"></div>
                                                </div>

                                                <div class="col flex-grow-1 min-width-0">
                                                    <div class="d-flex justify-content-between align-items-start mb-4">
                                                        <div>
                                                            <h3 class="h4 fw-bolder text-dark mb-2">{{ $item->product_name }}</h3>
                                                            <div class="d-flex flex-wrap gap-2">
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

                                                    <div class="bg-light bg-opacity-25 rounded-5 border p-3 border-opacity-50">
                                                        <x-tailoring.measurement-view :item="$item" />
                                                    </div>

                                                    <div class="mt-5 px-3">
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
                                                                    {{ $item->status ?: 'Pending' }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            @if (!$loop->last)
                                                <hr class="my-5 opacity-10">
                                            @endif
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cutting Slips Print Template (Uses simple plain elements for clean Bootstrap printing) -->
    <div id="cutting-slips-container" class="d-none d-print-block bg-white text-dark">
        @foreach ($this->groupedItems as $catId => $group)
            <div class="p-4 border border-dark rounded-0 @if (!$loop->first) page-break-before @endif" style="min-height: 100vh;">
                <!-- Slip Header -->
                <div class="d-flex justify-content-between border-bottom border-2 border-dark pb-3 mb-4">
                    <div>
                        <p class="small fw-bold mb-1">{{ $order->customer_mobile ?: 'No Phone' }}</p>
                        <h1 class="h3 fw-bolder text-uppercase mb-1">{{ $order->customer_name }}</h1>
                        <p class="small fw-bold mb-0">ID: {{ $order->order_no }}</p>
                    </div>
                    <div class="text-center">
                        <h2 class="h5 fw-bolder border-bottom border-dark px-3 mt-3 d-inline-block">{{ strtoupper($group['category']->name ?? 'Tailoring') }} CUTTING SLIP</h2>
                    </div>
                    <div class="text-end small fw-bold">
                        <div class="mb-2">
                            Order: {{ systemDate($order->order_date) }}<br>
                            Deliver: {{ systemDate($order->delivery_date) }}
                        </div>
                    </div>
                </div>

                <!-- Measure Grid -->
                <div class="row g-2 mb-3">
                    @php $measures1 = ['length' => 'Length', 'shoulder' => '(Shoulder)', 'sleeve' => '(Sleeve)', 'chest' => '(Chest)', 'stomach' => '(Stomach)', 'sl_chest' => '(S-L Chest)']; @endphp
                    @foreach ($measures1 as $key => $lbl)
                        <div class="col-2">
                            <div class="d-flex border border-dark rounded overflow-hidden" style="height: 38px;">
                                <div class="bg-light border-end border-dark d-flex align-items-center justify-content-center fw-bold" style="width: 40%;">{{ $group['measurements']->$key }}</div>
                                <div class="p-1 px-2 small fw-bold d-flex align-items-center" style="width: 60%; line-height: 1.1;">{{ $lbl }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="row g-2 mb-3">
                    @php $measures2 = ['mar_size' => 'Mar Size', 'regal_size' => '(Regal Size)', 'knee_loose' => '(Knee Loose)', 'fp_down' => '(FP Down)', 'bottom' => '(Bottom)', 'neck' => '(Neck)']; @endphp
                    @foreach ($measures2 as $key => $lbl)
                        <div class="col-2">
                            <div class="d-flex border border-dark rounded overflow-hidden" style="height: 38px;">
                                <div class="bg-light border-end border-dark d-flex align-items-center justify-content-center fw-bold" style="width: 40%;">{{ $group['measurements']->$key }}</div>
                                <div class="p-1 px-2 small fw-bold d-flex align-items-center" style="width: 60%; line-height: 1.1;">{{ $lbl }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-8">
                        <div class="d-flex align-items-center fw-bold border-bottom border-dark pb-1" style="height: 38px;">
                            <span class="me-2">Notes:</span>
                            <span class="flex-grow-1 border-bottom border-dark border-opacity-25">{{ $group['measurements']->tailoring_notes }}</span>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="d-flex border border-dark rounded overflow-hidden" style="height: 38px;">
                            <div class="bg-light border-end border-dark d-flex align-items-center justify-content-center fw-bold" style="width: 40%;">{{ $group['measurements']->fp_size }}</div>
                            <div class="p-1 px-2 small fw-bold d-flex align-items-center" style="width: 60%; line-height: 1.1;">(FP Size)</div>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="d-flex border border-dark rounded overflow-hidden" style="height: 38px;">
                            <div class="bg-light border-end border-dark d-flex align-items-center justify-content-center fw-bold" style="width: 40%;">{{ $group['measurements']->neck_d_button }}
                            </div>
                            <div class="p-1 px-2 small fw-bold d-flex align-items-center" style="width: 60%; line-height: 1.1;">(Button)</div>
                        </div>
                    </div>
                </div>

                <!-- Item Table -->
                <table class="table table-bordered border-dark small fw-bold mb-4">
                    <thead class="bg-light">
                        <tr class="text-center align-middle">
                            <th class="text-start">Description</th>
                            <th>Barcode</th>
                            <th>Qty</th>
                            <th>Type</th>
                            <th>Model</th>
                            <th>Color</th>
                            <th>Stitch Rate</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle text-center">
                        @foreach ($group['items'] as $item)
                            <tr>
                                <td class="text-start">{{ $item->product_name }}</td>
                                <td>{{ $item->product->barcode ?? '-' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->category->name ?? 'N/A' }}</td>
                                <td class="bg-warning bg-opacity-25">{{ $item->categoryModel->name ?? 'Std' }}</td>
                                <td>{{ $item->product_color ?: '-' }}</td>
                                <td>{{ currency($item->stitch_rate) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Info Bar -->
                <div class="row g-0 border border-dark border-bottom-0 bg-light small fw-bold text-center">
                    <div class="col p-2 border-end border-dark">MAR Model</div>
                    <div class="col p-2 border-end border-dark">FP Model</div>
                    <div class="col p-2 border-end border-dark">Pen</div>
                    <div class="col p-2 border-end border-dark">Mob Pkt</div>
                    <div class="col p-2 border-end border-dark">Btn No</div>
                    <div class="col p-2 border-end border-dark">Side PT</div>
                    <div class="col p-2 border-end border-dark">Size</div>
                    <div class="col p-2">Cuff</div>
                </div>
                <div class="row g-0 border border-dark text-center fw-bold mb-4" style="min-height: 34px;">
                    <div class="col py-2 border-end border-dark">{{ $group['measurements']->mar_model ?: '-' }}</div>
                    <div class="col py-2 border-end border-dark">{{ $group['measurements']->fp_model ?: '-' }}</div>
                    <div class="col py-2 border-end border-dark">{{ $group['measurements']->pen ?: '-' }}</div>
                    <div class="col py-2 border-end border-dark">{{ $group['measurements']->mobile_pocket ?: '-' }}</div>
                    <div class="col py-2 border-end border-dark">{{ $group['measurements']->button_no ?: '-' }}</div>
                    <div class="col py-2 border-end border-dark">{{ $group['measurements']->side_pt_model ?: '-' }}</div>
                    <div class="col py-2 border-end border-dark">{{ $group['measurements']->side_pt_size ?: '-' }}</div>
                    <div class="col py-2">{{ $group['measurements']->cuff ?: '-' }}</div>
                </div>

                <div class="row mt-5 pt-3">
                    <div class="col-8">
                        <div class="d-flex align-items-center gap-3 mb-3 fw-bold small">
                            <span>Cutting Master:</span>
                            <span class="flex-grow-1 border-bottom border-dark border-opacity-50 pb-1">{{ $order->cutter->name ?? '' }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-4 fw-bold small">
                            <span>Tailor Name:</span>
                            <span class="flex-grow-1 border-bottom border-dark border-opacity-50 pb-1">{{ $group['items']->first()->tailor->name ?? '' }}</span>
                        </div>
                        <div class="d-flex gap-4 fw-bold small">
                            <label class="d-flex align-items-center gap-2">
                                <div class="border border-dark" style="width: 14px; height: 14px; @if ($order->status === 'Confirmed') background: black; @endif"></div> Booking
                            </label>
                            <label class="d-flex align-items-center gap-2">
                                <div class="border border-dark" style="width: 14px; height: 14px; @if ($order->status === 'Completed') background: black; @endif"></div> Finished
                            </label>
                            <label class="d-flex align-items-center gap-2">
                                <div class="border border-dark" style="width: 14px; height: 14px; @if ($order->status === 'Delivered') background: black; @endif"></div> Delivered
                            </label>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <p class="small fw-bold mb-2">QR VERIFICATION</p>
                        <div class="d-inline-block p-1 border">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ $order->order_no }}-{{ $catId }}" alt="QR" width="80">
                        </div>
                    </div>
                </div>

                <div class="row mt-5 pt-5 border-top border-dark border-opacity-10 opacity-75 fw-bold small">
                    <div class="col-6">Prepared By: {{ $order->salesman->name ?? 'Sales' }}</div>
                    <div class="col-6 text-end">Approved: {{ $order->customer_name }}</div>
                </div>
            </div>
        @endforeach
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
