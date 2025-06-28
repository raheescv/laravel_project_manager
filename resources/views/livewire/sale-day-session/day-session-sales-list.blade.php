<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header" style="background: linear-gradient(135deg, #4a6fa5 0%, #357abd 100%); color: white; border-bottom: none;">
            <div class="d-flex align-items-center">
                <div class="rounded-circle p-2 me-3" style="background-color: rgba(255,255,255,0.2);">
                    <i class="fa fa-shopping-cart" style="font-size: 20px;"></i>
                </div>
                <div>
                    <h5 class="mb-0 text-white">Session Sales</h5>
                    <small class="text-light opacity-75">All sales recorded during this day session</small>
                </div>
            </div>
        </div>
        <div class="card-body" style="background-color: #fafafa;">
            <!-- Search and Filter Controls -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text" style="background-color: #e9ecef; border-color: #ced4da;">
                            <i class="fa fa-search" style="color: #6c757d;"></i>
                        </span>
                        <input type="text" wire:model.live="search" class="form-control" placeholder="Search by invoice, customer name, or mobile..." style="border-color: #ced4da;">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="perPage" class="form-select" style="border-color: #ced4da;">
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center justify-content-end">
                        <small class="text-muted me-2">Total Sales:</small>
                        <span class="badge" style="background-color: #4a6fa5; font-size: 14px; padding: 8px 12px;">
                            {{ $sales->total() }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Payment Method Summary Cards -->
            @if (count($paymentSummary) > 0)
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <h6 class="mb-3 text-muted d-flex align-items-center">
                            <i class="fa fa-credit-card me-2"></i>
                            Payment Methods Summary
                            <span class="badge bg-light text-dark ms-2">{{ count($paymentSummary) }} Methods</span>
                        </h6>
                    </div>
                    @foreach ($paymentSummary as $payment)
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div
                                class="card h-100 shadow-sm border-0
                                @if (strtolower($payment['payment_method_name']) === 'cash') bg-success
                                @elseif(strtolower($payment['payment_method_name']) === 'card') bg-primary
                                @else bg-info @endif
                                text-white">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-white bg-opacity-25 rounded-3 p-3 me-3 d-flex align-items-center justify-content-center">
                                                @if (strtolower($payment['payment_method_name']) === 'cash')
                                                    <i class="fa fa-money fs-4"></i>
                                                @elseif(strtolower($payment['payment_method_name']) === 'card')
                                                    <i class="fa fa-credit-card fs-4"></i>
                                                @else
                                                    <i class="fa fa-wallet fs-4"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-uppercase small">
                                                    {{ $payment['payment_method_name'] }}
                                                </h6>
                                                <small class="opacity-75">Payment Method</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-white bg-opacity-25 rounded-pill px-3 py-2">
                                            {{ number_format(($payment['total_paid'] / $totals['paid']) * 100, 1) }}%
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <h3 class="mb-1 fw-bold">{{ currency($payment['total_paid']) }}</h3>
                                        <small class="opacity-75">Total Collected</small>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-white border-opacity-25">
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-chart-line me-2 opacity-75"></i>
                                            <span class="small">{{ $payment['count'] }} Transactions</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-light text-success rounded-circle me-2" style="width: 8px; height: 8px;"></span>
                                            <span class="small opacity-75">Active</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Enhanced Data Table -->
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="background-color: white;">
                    <thead style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                        <tr>
                            <th wire:click="sortBy('id')" style="cursor: pointer; color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-tag me-2" style="color: #6c757d; font-size: 14px;"></i>
                                    ID
                                    @if ($sortField === 'id')
                                        @if ($sortDirection === 'asc')
                                            <i class="fa fa-caret-up ms-2" style="color: #4a6fa5;"></i>
                                        @else
                                            <i class="fa fa-caret-down ms-2" style="color: #4a6fa5;"></i>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th style="cursor: pointer; color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-file-text-o me-2" style="color: #6c757d; font-size: 14px;"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="invoice_no" label="Invoice No" />
                                </div>
                            </th>
                            <th style="color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-user me-2" style="color: #6c757d; font-size: 14px;"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_id" label="Customer" />
                                </div>
                            </th>
                            <th style="cursor: pointer; color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-calendar me-2" style="color: #6c757d; font-size: 14px;"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" />
                                </div>
                            </th>
                            <th class="text-end" style="cursor: pointer; color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-money me-2" style="color: #b8860b; font-size: 14px;"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total" label="Total" />
                                </div>
                            </th>
                            <th class="text-end" style="color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-tag me-2" style="color: #dc3545; font-size: 14px;"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="item_discount" label="Discount" />
                                </div>
                            </th>
                            <th class="text-end" style="color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-calculator me-2" style="color: #5a9fd4; font-size: 14px;"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tax_amount" label="Tax" />
                                </div>
                            </th>
                            <th class="text-end" style="color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-calculator me-2" style="color: #5a9fd4; font-size: 14px;"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="payment_method_name" label="Payment Method" />
                                </div>
                            </th>
                            <th class="text-end" style="cursor: pointer; color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-check-circle me-2" style="color: #28a745; font-size: 14px;"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="Paid" />
                                </div>
                            </th>
                            <th class="text-end" style="cursor: pointer; color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-check-circle me-2" style="color: #28a745; font-size: 14px;"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="balance" />
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr style="border-bottom: 1px solid #f8f9fa;">
                                <td style=" vertical-align: middle;">
                                    <span class="badge" style="background-color: #e9ecef; color: #495057; font-weight: 500;">
                                        #{{ $sale->id }}
                                    </span>
                                </td>
                                <td style=" vertical-align: middle;">
                                    <div class="fw-bold" style="color: #4a6fa5;">
                                        <a href="{{ route('sale::view', $sale->id) }}">
                                            <i class="fa fa-eye me-2" style="font-size: 12px;"></i>
                                            {{ $sale->invoice_no }}
                                        </a>
                                    </div>
                                </td>
                                <td style=" vertical-align: middle;">
                                    <div>
                                        <div class="fw-medium" style="color: #495057;">
                                            @php
                                                $customer_name = $sale->customer_name;
                                                $customer_mobile = $sale->customer_mobile;
                                                if ($sale->account && $sale->account->name) {
                                                    $customer_name = $sale->account->name;
                                                    $customer_mobile = $sale->account->mobile;
                                                }
                                            @endphp
                                            {{ $customer_name }}
                                        </div>
                                        @if ($customer_mobile)
                                            <small class="text-muted d-flex align-items-center mt-1">
                                                <i class="fa fa-phone me-1" style="font-size: 10px;"></i>
                                                {{ $customer_mobile }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td style=" vertical-align: middle;">
                                    <div class="d-flex align-items-center">
                                        <i class="fa fa-calendar-o me-2" style="color: #6c757d; font-size: 12px;"></i>
                                        <span style="color: #495057;">{{ systemDate($sale->date) }}</span>
                                    </div>
                                </td>
                                <td class="text-end" style=" vertical-align: middle;">
                                    <span class="fw-bold" style="color: #b8860b; font-size: 15px;">{{ currency($sale->total) }}</span>
                                </td>
                                <td class="text-end" style=" vertical-align: middle;">
                                    <span style="color: #dc3545;">{{ $sale->item_discount != 0 ? currency($sale->item_discount) : '-' }}</span>
                                </td>
                                <td class="text-end" style=" vertical-align: middle;">
                                    <span style="color: #5a9fd4;">{{ $sale->tax_amount != 0 ? currency($sale->tax_amount) : '-' }}</span>
                                </td>
                                <td class="text-end" style=" vertical-align: middle;">
                                    <span class="fw-bold" style="color: #28a745; font-size: 15px;">{{ $sale->payment_method_name }}</span>
                                </td>
                                <td class="text-end" style=" vertical-align: middle;">
                                    <span class="fw-bold" style="color: #28a745; font-size: 15px;">{{ currency($sale->paid) }}</span>
                                </td>
                                <td class="text-end" style=" vertical-align: middle;">
                                    <span class="fw-bold" style="color: red; font-size: 15px;">{{ $sale->balance != 0 ? currency($sale->balance) : '-' }}</span>
                                </td>
                            </tr>
                        @endforeach

                        @if ($sales->count() === 0)
                            <tr>
                                <td colspan="9" class="text-center" style="padding: 40px 20px;">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background-color: #f8f9fa;">
                                            <i class="fa fa-shopping-cart" style="color: #6c757d; font-size: 24px;"></i>
                                        </div>
                                        <h6 style="color: #6c757d; margin-bottom: 8px;">No Sales Found</h6>
                                        <p class="text-muted mb-0">No sales have been recorded for this day session yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-top: 2px solid #dee2e6;">
                        <tr>
                            <td colspan="4" class="text-end fw-bold" style="color: #495057; padding: 20px 12px; font-size: 16px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-calculator me-2" style="color: #6c757d;"></i>
                                    Session Totals:
                                </div>
                            </td>
                            <td class="text-end fw-bold" style="color: #b8860b; padding: 20px 12px; font-size: 16px;">
                                {{ currency($totals['total']) }}
                            </td>
                            <td class="text-end fw-bold" style="color: #dc3545; padding: 20px 12px; font-size: 16px;">
                                {{ currency($totals['item_discount']) }}
                            </td>
                            <td class="text-end fw-bold" style="color: #5a9fd4; padding: 20px 12px; font-size: 16px;">
                                {{ currency($totals['tax_amount']) }}
                            </td>
                            <td class="text-end fw-bold" style="color: #5a9fd4; padding: 20px 12px; font-size: 16px;">
                            </td>
                            <td class="text-end fw-bold" style="color: #28a745; padding: 20px 12px; font-size: 16px;">
                                {{ currency($totals['paid']) }}
                            </td>
                            <td class="text-end fw-bold" style="color: #28a745; padding: 20px 12px; font-size: 16px;">
                                {{ currency($totals['balance']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Enhanced Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted">
                    <small>
                        Showing {{ $sales->firstItem() ?? 0 }} to {{ $sales->lastItem() ?? 0 }}
                        of {{ $sales->total() }} results
                    </small>
                </div>
                <div>
                    {{ $sales->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
