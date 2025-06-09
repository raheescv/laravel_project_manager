<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header" style="background: linear-gradient(135deg, #4a6fa5 0%, #357abd 100%); color: white; border-bottom: none;">
            <div class="d-flex align-items-center">
                <div class="rounded-circle p-2 me-3" style="background-color: rgba(255,255,255,0.2);">
                    <i class="fa fa-shopping-cart" style="font-size: 20px;"></i>
                </div>
                <div>
                    <h5 class="mb-0">Session Sales</h5>
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
                        <input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Search by invoice, customer name, or mobile..." style="border-color: #ced4da;">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model="perPage" class="form-select" style="border-color: #ced4da;">
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

            <!-- Enhanced Data Table -->
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="background-color: white;">
                    <thead style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                        <tr>
                            <th wire:click="sortBy('id')" style="cursor: pointer; color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-hashtag me-2" style="color: #6c757d; font-size: 14px;"></i>
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
                            <th wire:click="sortBy('invoice_no')" style="cursor: pointer; color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-file-text-o me-2" style="color: #6c757d; font-size: 14px;"></i>
                                    Invoice No
                                    @if ($sortField === 'invoice_no')
                                        @if ($sortDirection === 'asc')
                                            <i class="fa fa-caret-up ms-2" style="color: #4a6fa5;"></i>
                                        @else
                                            <i class="fa fa-caret-down ms-2" style="color: #4a6fa5;"></i>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th style="color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-user me-2" style="color: #6c757d; font-size: 14px;"></i>
                                    Customer
                                </div>
                            </th>
                            <th wire:click="sortBy('date')" style="cursor: pointer; color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-calendar me-2" style="color: #6c757d; font-size: 14px;"></i>
                                    Date
                                    @if ($sortField === 'date')
                                        @if ($sortDirection === 'asc')
                                            <i class="fa fa-caret-up ms-2" style="color: #4a6fa5;"></i>
                                        @else
                                            <i class="fa fa-caret-down ms-2" style="color: #4a6fa5;"></i>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th class="text-end" wire:click="sortBy('gross_amount')" style="cursor: pointer; color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-money me-2" style="color: #b8860b; font-size: 14px;"></i>
                                    Gross Amount
                                    @if ($sortField === 'gross_amount')
                                        @if ($sortDirection === 'asc')
                                            <i class="fa fa-caret-up ms-2" style="color: #4a6fa5;"></i>
                                        @else
                                            <i class="fa fa-caret-down ms-2" style="color: #4a6fa5;"></i>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th class="text-end" style="color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-percent me-2" style="color: #dc3545; font-size: 14px;"></i>
                                    Discount
                                </div>
                            </th>
                            <th class="text-end" style="color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-calculator me-2" style="color: #5a9fd4; font-size: 14px;"></i>
                                    Tax
                                </div>
                            </th>
                            <th class="text-end" wire:click="sortBy('paid')" style="cursor: pointer; color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-check-circle me-2" style="color: #28a745; font-size: 14px;"></i>
                                    Paid
                                    @if ($sortField === 'paid')
                                        @if ($sortDirection === 'asc')
                                            <i class="fa fa-caret-up ms-2" style="color: #4a6fa5;"></i>
                                        @else
                                            <i class="fa fa-caret-down ms-2" style="color: #4a6fa5;"></i>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th style="color: #495057; font-weight: 600; border-bottom: 2px solid #dee2e6; padding: 15px 12px;">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-cogs me-2" style="color: #6c757d; font-size: 14px;"></i>
                                    Actions
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr style="border-bottom: 1px solid #f8f9fa;">
                                <td style="padding: 15px 12px; vertical-align: middle;">
                                    <span class="badge" style="background-color: #e9ecef; color: #495057; font-weight: 500;">
                                        #{{ $sale->id }}
                                    </span>
                                </td>
                                <td style="padding: 15px 12px; vertical-align: middle;">
                                    <div class="fw-bold" style="color: #4a6fa5;">{{ $sale->invoice_no }}</div>
                                </td>
                                <td style="padding: 15px 12px; vertical-align: middle;">
                                    <div>
                                        <div class="fw-medium" style="color: #495057;">{{ $sale->customer_name ?? ($sale->account->name ?? 'N/A') }}</div>
                                        @if ($sale->customer_mobile ?? ($sale->account->mobile ?? ''))
                                            <small class="text-muted d-flex align-items-center mt-1">
                                                <i class="fa fa-phone me-1" style="font-size: 10px;"></i>
                                                {{ $sale->customer_mobile ?? ($sale->account->mobile ?? '') }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td style="padding: 15px 12px; vertical-align: middle;">
                                    <div class="d-flex align-items-center">
                                        <i class="fa fa-calendar-o me-2" style="color: #6c757d; font-size: 12px;"></i>
                                        <span style="color: #495057;">{{ systemDate($sale->date) }}</span>
                                    </div>
                                </td>
                                <td class="text-end" style="padding: 15px 12px; vertical-align: middle;">
                                    <span class="fw-bold" style="color: #b8860b; font-size: 15px;">{{ currency($sale->gross_amount) }}</span>
                                </td>
                                <td class="text-end" style="padding: 15px 12px; vertical-align: middle;">
                                    <span style="color: #dc3545;">{{ currency($sale->item_discount) }}</span>
                                </td>
                                <td class="text-end" style="padding: 15px 12px; vertical-align: middle;">
                                    <span style="color: #5a9fd4;">{{ currency($sale->tax_amount) }}</span>
                                </td>
                                <td class="text-end" style="padding: 15px 12px; vertical-align: middle;">
                                    <span class="fw-bold" style="color: #28a745; font-size: 15px;">{{ currency($sale->paid) }}</span>
                                </td>
                                <td style="padding: 15px 12px; vertical-align: middle;">
                                    <a href="{{ route('sale::view', $sale->id) }}" class="btn btn-sm d-flex align-items-center"
                                        style="background-color: #4a6fa5; color: white; border: none; padding: 8px 12px;">
                                        <i class="fa fa-eye me-2" style="font-size: 12px;"></i>
                                        View
                                    </a>
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
                                {{ currency($totals['gross_amount']) }}
                            </td>
                            <td class="text-end fw-bold" style="color: #dc3545; padding: 20px 12px; font-size: 16px;">
                                {{ currency($totals['item_discount']) }}
                            </td>
                            <td class="text-end fw-bold" style="color: #5a9fd4; padding: 20px 12px; font-size: 16px;">
                                {{ currency($totals['tax_amount']) }}
                            </td>
                            <td class="text-end fw-bold" style="color: #28a745; padding: 20px 12px; font-size: 16px;">
                                {{ currency($totals['paid']) }}
                            </td>
                            <td style="padding: 20px 12px;"></td>
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
