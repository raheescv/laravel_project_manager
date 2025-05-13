<div>
    <div class="card shadow-sm h-100">
        <div class="card-header border-0 bg-transparent pt-4 px-4">
            <h4 class="mb-0">Income vs Expense</h4>
        </div>
        <div class="card-body">
            <canvas id="incomeExpenseChart" wire:ignore></canvas>
        </div>
    </div>
    @push('scripts')
        <script>
            // Register the ChartDataLabels plugin
            Chart.register(ChartDataLabels);

            const ctx = document.getElementById('incomeExpenseChart').getContext('2d');
            const chartData = @json($chartData);
            new Chart(ctx, {
                type: 'pie',
                data: {
                    ...chartData,
                    datasets: [{
                        ...chartData.datasets[0],
                        backgroundColor: [
                            'rgba(108, 198, 177, 0.8)', // Soft mint green for income
                            'rgba(255, 171, 145, 0.8)' // Soft coral for expense
                        ],
                        borderColor: [
                            'rgba(108, 198, 177, 1)',
                            'rgba(255, 171, 145, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        datalabels: {
                            color: '#333',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            formatter: function(value) {
                                return value.toLocaleString('en-US', {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                });
                            }
                        },
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        </script>
    @endpush
</div>
