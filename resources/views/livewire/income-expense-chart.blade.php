<div>
    <div class="card">
        <div class="card-header">
            Income vs Expense
        </div>
        <div class="card-body">
            <canvas id="incomeExpenseChart" wire:ignore></canvas>
        </div>
    </div>
    @push('scripts')
        <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script>
            const ctx = document.getElementById('incomeExpenseChart').getContext('2d');
            const chartData = @json($chartData);

            console.log(chartData);
            new Chart(ctx, {
                type: 'pie',
                data: chartData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        </script>
    @endpush
</div>
