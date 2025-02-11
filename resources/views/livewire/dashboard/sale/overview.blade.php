<div class="card h-100">
    <div class="card-header d-flex align-items-center border-0">
        <div class="me-auto">
            <h3 class="h4 m-0">Sale</h3>
        </div>
        <div class="toolbar-end">
            <div class="dropdown">
                <button class="btn btn-icon btn-sm btn-hover btn-light" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Network dropdown">
                    <i class="demo-pli-dot-horizontal fs-4"></i>
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a href="{{ route('sale::index') }}" class="dropdown-item">
                            <i class="demo-pli-calendar-4 fs-5 me-2"></i>
                            View Details
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body py-0" style="height: 250px; max-height: 275px">
        <canvas id="sale-overview-chart"></canvas>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h4 class="h5">Today Sales</h4>
                <div class="row">
                    <div class="col-6">
                        <div class="h5 display-6 fw-normal">
                            {{ currency($todaySale) }}
                        </div>
                    </div>
                    <div class="col-6 text-sm">
                        <div class="d-flex justify-content-between align-items-start px-3 mb-3">
                            Lowest Sale
                            <span class="d-block badge bg-warning ms-auto">{{ currency($lowestSale) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-start px-3">
                            Highest Sale
                            <span class="d-block badge bg-success ms-auto">{{ currency($highestSale) }}</span>
                        </div>
                    </div>
                </div>
                <h4 class="h5">Payments</h4>
                <div class="h2 fw-normal"> {{ currency($todayPayment) }} </div>

            </div>
            <div class="col-md-4">
                @php
                    $colors = ['bg-success', 'bg-info', 'bg-warning'];
                @endphp
                @foreach ($paymentData as $item)
                    <div class="mt-4 mb-2 d-flex justify-content-between">
                        <span class>{{ $item['method'] }}</span>
                        <span class>{{ $item['amount'] }}</span>
                    </div>
                    <div class="progress progress-md">
                        <div class="progress-bar {{ $colors[rand(1, count($paymentData) - 1)] }}" role="progressbar" style="width: {{ $item['percentage'] }}%" aria-label="Incoming Progress"
                            aria-valuenow="{{ $item['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            updateColorVars();
            const saleData = @js($data);
            networkChart = new Chart(
                document.getElementById("sale-overview-chart"), {
                    type: "line",
                    data: {
                        datasets: [{
                            label: "Sale",
                            data: saleData,
                            borderColor: primaryColor,
                            backgroundColor: primaryColor,
                            fill: "start",
                            parsing: {
                                xAxisKey: "date",
                                yAxisKey: "amount"
                            }
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                align: "start",
                                labels: {
                                    boxWidth: 10,
                                    color: textColor
                                }
                            },
                        },
                        interaction: {
                            mode: "index",
                            intersect: false,
                        },
                        scales: {
                            y: {
                                display: false,
                            },
                            x: {
                                grid: {
                                    borderWidth: 0,
                                    drawOnChartArea: false
                                },
                                ticks: {
                                    font: {
                                        size: 11
                                    },
                                    color: textColor,
                                    autoSkip: true,
                                    maxRotation: 0,
                                    minRotation: 0,
                                    maxTicksLimit: 9
                                }
                            }
                        },
                        radius: 1,
                        elements: {
                            line: {
                                tension: 0.25
                            }
                        }
                    }
                });
        });
    </script>
</div>
