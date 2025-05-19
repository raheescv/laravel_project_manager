<div class="card shadow-sm h-100">
    <div class="card-header border-0 bg-transparent pt-4 px-4">
        <h4 class="mb-0">Income vs Expense</h4>
    </div>
    <div class="card-body">
        <div style="height: 300px;">
            <canvas id="incomeExpenseBarChart" wire:ignore></canvas>
        </div>
    </div>
    @push('scripts')
        <script>
            // Register the plugin
            Chart.register(ChartDataLabels);

            document.addEventListener("DOMContentLoaded", () => {
                var data = @json($chartData);
                const ctx = document.getElementById("incomeExpenseBarChart").getContext('2d');

                new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: data.labels,
                        datasets: [{
                                label: "Income",
                                data: data.income,
                                backgroundColor: 'rgba(108, 198, 177, 0.8)', // Soft mint green
                                borderColor: 'rgba(108, 198, 177, 1)',
                                borderWidth: 1,
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.8
                            },
                            {
                                label: "Expense",
                                data: data.expense,
                                backgroundColor: 'rgba(255, 171, 145, 0.8)', // Soft coral
                                borderColor: 'rgba(255, 171, 145, 1)',
                                borderWidth: 1,
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                offset: 6,
                                color: '#666',
                                font: {
                                    weight: 'bold',
                                    size: 11
                                },
                                formatter: function(value) {
                                    return value.toLocaleString('en-US', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    });
                                },
                                offset: -2,
                            },
                            legend: {
                                display: true,
                                position: 'top',
                                align: 'end',
                                labels: {
                                    boxWidth: 12,
                                    usePointStyle: true,
                                    pointStyle: 'rectRounded'
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    drawBorder: false,
                                    color: 'rgba(0,0,0,0.05)'
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    },
                                    color: '#666'
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    },
                                    color: '#666'
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
</div>
