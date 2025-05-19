<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Appointment Overview</h5>
        <div class="btn-group">
            <button class="btn {{ $period === 'week' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="changePeriod('week')">Week</button>
            <button class="btn {{ $period === 'month' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="changePeriod('month')">Month</button>
        </div>
    </div>
    <div class="card-body">
        <canvas id="appointmentChart" wire:ignore></canvas>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('assets/vendors/chart.js/chartjs-plugin-datalabels@2.min.js') }}"></script>
    <script>
        let appointmentChart;

        document.addEventListener('livewire:initialized', () => {
            initChart();
        });
        window.addEventListener('reLoadChartData', event => {
            updateChart(event.detail[0]);
        });

        function initChart() {
            Chart.register(ChartDataLabels); // Register the plugin
            const ctx = document.getElementById('appointmentChart').getContext('2d');
            appointmentChart = new Chart(ctx, {
                type: 'bar',
                data: @js($chartData),
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: function(value) {
                                return value > 0 ? value : '';
                            },
                            color: '#666',
                            font: {
                                weight: 'bold',
                                size: 11
                            },
                            offset: -8,
                        }
                    },
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }
            });
        }

        function updateChart(data) {
            if (appointmentChart) {
                appointmentChart.data = data;
                appointmentChart.update('active');
            }
        }
    </script>
@endpush
