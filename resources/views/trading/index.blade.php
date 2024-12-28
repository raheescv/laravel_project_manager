<x-app-layout>
    @push('styles')
        <link href="{{ asset('assets/vendors/apexcharts/style.css') }}" rel="stylesheet" />
        <style>
            .chart-box {
                /* max-width: 650px; */
                margin: 35px auto;
            }

            #chart-candlestick,
            #chart-bar {
                /* max-width: 640px; */
            }

            #chart-bar {
                margin-top: -30px;
            }

            .tradingview-widget-container {
                height: 501px !important;
            }
        </style>
    @endpush
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Trading</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Trading</h1>
            <p class="lead">
                A table is an arrangement of Trading
            </p>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            @livewire('trading.page')
        </div>
    </div>
    @push('scripts')
    @endpush
</x-app-layout>
