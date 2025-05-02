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
        <script>
            const ctx = document.getElementById('incomeExpenseChart').getContext('2d');
            const chartData = @json($chartData);
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
