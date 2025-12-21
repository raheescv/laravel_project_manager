<div>
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Filter Section -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="branch_id" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-building me-1"></i>Branch
                        </label>
                        <select wire:model.live="branch_id" class="form-select shadow-sm border-light" id="branch_id">
                            <option value="">All Branches</option>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="period" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-time-clock me-1"></i>Period
                        </label>
                        <select wire:model.live="period" class="form-select shadow-sm border-light" id="period">
                            <option value="monthly">Current Month</option>
                            <option value="quarterly">Current Quarter</option>
                            <option value="yearly">Current Year</option>
                            <option value="previous_month">Previous Month</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="start_date" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-calendar-4 me-1"></i>Start Date
                        </label>
                        <input type="date" wire:model.live="start_date" class="form-control shadow-sm border-light" id="start_date">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="end_date" class="form-label fw-bold text-secondary mb-2">
                            <i class="pli-calendar-4 me-1"></i>End Date
                        </label>
                        <input type="date" wire:model.live="end_date" class="form-control shadow-sm border-light" id="end_date">
                    </div>
                </div>
            </div>
            <!-- Profit & Loss Report - T-Account Format -->
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="profitLossTable" style="font-size: 0.9rem;">
                    <thead>
                        <tr>
                            <th class="text-left bg-light" style="width: 40%; border-right: 2px solid #dee2e6;">
                                <strong>PARTICULARS</strong>
                            </th>
                            <th class="text-center bg-light" style="width: 15%; border-right: 2px solid #dee2e6;">
                                <strong>AMOUNT</strong>
                            </th>
                            <th class="text-left bg-light" style="width: 40%;">
                                <strong>PARTICULARS</strong>
                            </th>
                            <th class="text-center bg-light" style="width: 15%;">
                                <strong>AMOUNT</strong>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Top Section: Gross Profit/Loss Calculation -->
                        <tr>
                            <!-- Left: Opening Stock -->
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>OPENING STOCK</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;">{{ currency($openingStock) }}</td>
                            <!-- Right: Net Sale -->
                            <td class="ps-3"><strong>NET SALE</strong></td>
                            <td class="text-end pe-3">{{ currency($netSale) }}</td>
                        </tr>
                        <tr>
                            <!-- Left: Net Purchase -->
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>NET PURCHASE</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;">{{ currency($netPurchase) }}</td>
                            <!-- Right: Closing Stock -->
                            <td class="ps-3">
                                <strong>CLOSING STOCK</strong>
                            </td>
                            <td class="text-end pe-3" style="background-color: #e3f2fd;">{{ currency($closingStock) }}</td>
                        </tr>
                        <tr>
                            <!-- Left: Direct Expense -->
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>DIRECT EXPENSE</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;">{{ currency($directExpense) }}</td>
                            <!-- Right: Direct Income -->
                            <td class="ps-3"><strong>DIRECT INCOME</strong></td>
                            <td class="text-end pe-3">{{ currency($directIncome) }}</td>
                        </tr>
                        <tr>
                            @if ($grossProfit > 0)
                                <td class="ps-3"><strong class="text-success">GROSS PROFIT C/D</strong></td>
                                <td class="text-end pe-3 text-success"><strong class="text-success">{{ currency($grossProfit) }}</strong></td>
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"></td>
                            @elseif($grossLoss > 0)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"></td>
                                <td class="ps-3"><strong class="text-danger">GROSS LOSS C/D</strong></td>
                                <td class="text-end pe-3 text-danger"><strong class="text-danger">{{ currency($grossLoss) }}</strong></td>
                            @endif
                        </tr>
                        <!-- Total Row for Top Section -->
                        <tr class="table-light" style="border-top: 2px solid #dee2e6;">
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>TOTAL</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"><strong>{{ currency($leftTotal1) }}</strong></td>
                            <td class="ps-3"><strong>TOTAL</strong></td>
                            <td class="text-end pe-3"><strong>{{ currency($rightTotal1) }}</strong></td>
                        </tr>

                        <!-- Bottom Section: Net Profit/Loss Calculation -->
                        <tr>
                            <!-- Left: Gross Loss B/D -->
                            @if ($grossLoss > 0)
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong class="text-danger">GROSS LOSS B/D</strong></td>
                                <td class="text-end pe-3 text-danger" style="border-right: 2px solid #dee2e6;"><strong class="text-danger">{{ currency($grossLoss) }}</strong></td>
                                <!-- Right: Empty -->
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                            @endif
                            @if ($grossProfit > 0)
                                <!-- Right: Empty -->
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                                <!-- Left: Gross Profit B/D -->
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong class="text-success">GROSS PROFIT B/D</strong></td>
                                <td class="text-end pe-3 text-success" style="border-right: 2px solid #dee2e6;"><strong class="text-success">{{ currency($grossProfit) }}</strong></td>
                            @endif
                        </tr>
                        <tr>
                            <!-- Left: Indirect Expense -->
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>INDIRECT EXPENSE</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;">{{ currency($indirectExpense) }}</td>
                            <!-- Right: Indirect Income -->
                            <td class="ps-3"><strong>INDIRECT INCOME</strong></td>
                            <td class="text-end pe-3">{{ currency($indirectIncome) }}</td>
                        </tr>
                        <tr>
                            @if ($netProfitAmount > 0)
                                <!-- Left: Net Profit C/D -->
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong class="text-success">NET PROFIT C/D</strong></td>
                                <td class="text-end pe-3 text-success" style="border-right: 2px solid #dee2e6;"><strong class="text-success">{{ currency($netProfitAmount) }}</strong></td>
                                <!-- Right: Empty -->
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                            @endif
                            @if ($netLossAmount > 0)
                                <!-- Right: Empty -->
                                <td class="ps-3"></td>
                                <td class="text-end pe-3"></td>
                                <!-- Left: Net Loss C/D -->
                                <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong class="text-danger">NET LOSS C/D</strong></td>
                                <td class="text-end pe-3 text-danger" style="border-right: 2px solid #dee2e6;"><strong class="text-danger">{{ currency($netLossAmount) }}</strong></td>
                            @endif
                        </tr>
                        <!-- Total Row for Bottom Section -->
                        <tr class="table-light" style="border-top: 2px solid #dee2e6;">
                            <td class="ps-3" style="border-right: 2px solid #dee2e6;"><strong>TOTAL</strong></td>
                            <td class="text-end pe-3" style="border-right: 2px solid #dee2e6;"><strong>{{ currency($leftTotal2) }}</strong></td>
                            <td class="ps-3"><strong>TOTAL</strong></td>
                            <td class="text-end pe-3"><strong>{{ currency($rightTotal2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function copyTable() {
                const table = document.getElementById('profitLossTable');
                const range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand('copy');
                window.getSelection().removeAllRanges();
                alert('Table copied to clipboard!');
            }

            function exportToExcel() {
                // Implement Excel export
                alert('Excel export functionality will be implemented');
            }

            function exportToCSV() {
                const table = document.getElementById('profitLossTable');
                let csv = [];
                const rows = table.querySelectorAll('tr');

                for (let i = 0; i < rows.length; i++) {
                    const row = [],
                        cols = rows[i].querySelectorAll('td, th');
                    for (let j = 0; j < cols.length; j++) {
                        row.push(cols[j].innerText.trim());
                    }
                    csv.push(row.join(','));
                }

                const csvContent = csv.join('\n');
                const blob = new Blob([csvContent], {
                    type: 'text/csv;charset=utf-8;'
                });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'profit_loss_report.csv';
                a.click();
                window.URL.revokeObjectURL(url);
            }

            function exportToPDF() {
                window.print();
            }
        </script>
    @endpush
</div>
