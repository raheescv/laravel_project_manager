<div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h5 class="card-title">Income vs Expense</h5>
    </div>
    <div class="card-body">
        <div style="height: 300px;">
            <canvas id="incomeExpenseBarChart" wire:ignore></canvas>
        </div>
    </div>
    @push('scripts')
        <script>
            let barChart;
            var incomeExpenseBarChartBody = getComputedStyle(document.body);
            var incomeExpenseBarChartSuccessColor = incomeExpenseBarChartBody.getPropertyValue("--bs-success");
            var incomeExpenseBarChartWarningColor = incomeExpenseBarChartBody.getPropertyValue("--bs-warning");

            var incomeExpenseBarCtx = document.getElementById('incomeExpenseBarChart').getContext('2d');
            var data = @json($chartData);
            barChart = new Chart(incomeExpenseBarCtx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Income',
                        data: data.income,
                        backgroundColor: incomeExpenseBarChartSuccessColor,
                        borderWidth: 1
                    }, {
                        label: 'Expense',
                        data: data.expense,
                        backgroundColor: incomeExpenseBarChartWarningColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
    @endpush
</div>
