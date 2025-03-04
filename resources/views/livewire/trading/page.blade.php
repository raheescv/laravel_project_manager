<div>
    <div class="card">
        <div class="card-header -4 mb-3">
            <div class="row">
                <div class="col-md-3 d-flex gap-1 align-items-center mb-3">
                    <button class="btn btn-secondary hstack gap-2 align-self-center" wire:click="getAuthCode"> Get Auth Code </button>
                </div>
                <div class="col-md-3 d-flex gap-1 align-items-center mb-3">
                    <button class="btn btn-success hstack gap-2 align-self-center" wire:click="login"> Login </button>
                </div>
            </div>
        </div>
        <div class="card-header toolbar">
            <div class="toolbar-start">
                <h5 class="m-0">Fyers</h5>
            </div>
            <div class="toolbar-end">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if ($activeTab == 'Quotes') active @endif" data-bs-toggle="tab" data-bs-target="#tabsQuotes" wire:click="tabSelect('Quotes')" type="button"
                            tabindex="-1">
                            Quotes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if ($activeTab == 'MarketDepth') active @endif" data-bs-toggle="tab" data-bs-target="#tabsMarketDepth" wire:click="tabSelect('MarketDepth')"
                            type="button" tabindex="-1">
                            Market Depth
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if ($activeTab == 'MarketStatus') active @endif" data-bs-toggle="tab" data-bs-target="#tabsMarketStatus" wire:click="tabSelect('MarketStatus')"
                            type="button" tabindex="-1">
                            Market Status
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if ($activeTab == 'GetOrders') active @endif" data-bs-toggle="tab" data-bs-target="#tabsGetOrders" wire:click="tabSelect('GetOrders')" type="button"
                            tabindex="-1">
                            Orders
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if ($activeTab == 'History') active @endif" data-bs-toggle="tab" data-bs-target="#tabsHistory" wire:click="tabSelect('History')" type="button"
                            tabindex="-1">
                            History
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if ($activeTab == 'Profile') active @endif" data-bs-toggle="tab" data-bs-target="#tabsProfile" wire:click="tabSelect('Profile')" type="button">
                            Profile
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if ($activeTab == 'Funds') active @endif" data-bs-toggle="tab" data-bs-target="#tabsFunds" wire:click="tabSelect('Funds')" type="button"
                            tabindex="-1">
                            Funds
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if ($activeTab == 'Position') active @endif" data-bs-toggle="tab" data-bs-target="#tabsPosition" wire:click="tabSelect('Position')" type="button"
                            tabindex="-1">
                            Position
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        <div class="card-body tab-content">
            <div id="tabsProfile" class="tab-pane fade @if ($activeTab == 'Profile') active show @endif" role="tabpanel">
                <h5>Profile</h5>
                <table class="table table-striped table-sm table-bordered text-capitalize">
                    <tr>
                        <th>fy id</th>
                        <th>Name</th>
                        <th>email</th>
                        <th>mobile</th>
                    </tr>
                    @if ($user)
                        <tr>
                            <td>{{ $user['profile']['fy_id'] }}</td>
                            <td>{{ $user['profile']['name'] }}</td>
                            <td>{{ $user['profile']['email_id'] }}</td>
                            <td>{{ $user['profile']['mobile_number'] }}</td>
                        </tr>
                    @endif
                </table>
            </div>
            <div id="tabsFunds" class="tab-pane fade @if ($activeTab == 'Funds') active show @endif" role="tabpanel">
                <h5>Funds</h5>
                <table class="table table-striped table-sm table-bordered text-capitalize">
                    <thead>
                        <tr>
                            <th class="text-end">#</th>
                            <th>Title</th>
                            <th class="text-end">Equity Amount</th>
                            <th class="text-end">Commodity Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($user['funds'] ?? [] as $value)
                            <tr>
                                <td class="text-end">{{ $value['id'] }}</td>
                                <td>{{ $value['title'] }}</td>
                                <td class="text-end">{{ $value['equityAmount'] }}</td>
                                <td class="text-end">{{ $value['commodityAmount'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="tabsQuotes" class="tab-pane fade @if ($activeTab == 'Quotes') active show @endif" role="tabpanel">
                <h5>Quotes</h5>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" wire:model="symbol" placeholder="Enter Your Symbols : NSE:SBIN-EQ">
                    <button class="btn btn-info" type="button" wire:click="getQuotes" id="button-addon2">Get</button>
                </div>
                <table class="table table-striped table-sm table-bordered text-capitalize">
                    @foreach ($quoteData as $key => $value)
                        <tr>
                            <th>
                                @if (config("constants.quote.$key"))
                                    {{ config("constants.quote.$key") }}
                                @else
                                    {{ $key }}
                                @endif
                            </th>
                            <th>{{ $value }}</th>
                        </tr>
                    @endforeach
                </table>
            </div>
            <div id="tabsMarketDepth" class="tab-pane fade @if ($activeTab == 'MarketDepth') active show @endif" role="tabpanel">
                <h5>Market Depth</h5>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" wire:model="symbol" placeholder="Enter Your Symbols : NSE:SBIN-EQ">
                    <button class="btn btn-info" type="button" wire:click="getMarketDepth" id="button-addon2">Get</button>
                </div>
                <table class="table table-striped table-sm table-bordered text-capitalize">
                    @foreach ($depthData as $key => $value)
                        <tr>
                            <th>
                                @if (config("constants.data_depth.$key"))
                                    {{ config("constants.data_depth.$key") }}
                                @else
                                    {{ $key }}
                                @endif
                            </th>
                            <th>
                                @if (!is_array($value))
                                    {{ $value }}
                                @else
                                    <table class="table table-striped table-sm table-bordered text-capitalize">
                                        <thead>
                                            <tr>
                                                <th class="text-end">price</th>
                                                <th class="text-end">volume</th>
                                                <th class="text-end">ord</th>
                                            </tr>
                                        </thead>
                                        @foreach ($value as $valueKey => $item)
                                            <tbody>
                                                <tr>
                                                    <td class="text-end">{{ $item['price'] }}</td>
                                                    <td class="text-end">{{ $item['volume'] }}</td>
                                                    <td class="text-end">{{ $item['ord'] }}</td>
                                                </tr>
                                            </tbody>
                                        @endforeach
                                    </table>
                                @endif
                            </th>
                        </tr>
                    @endforeach
                </table>
            </div>
            <div id="tabsMarketStatus" class="tab-pane fade @if ($activeTab == 'MarketStatus') active show @endif" role="tabpanel">
                <h5>Market Depth</h5>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" wire:model="symbol" placeholder="Enter Your Symbols : NSE:SBIN-EQ">
                    <button class="btn btn-info" type="button" wire:click="getMarketStatus" id="button-addon2">Get</button>
                </div>
                <table class="table table-striped table-sm table-bordered text-capitalize">
                    <thead>
                        <tr>
                            <th> Market Type </th>
                            <th> status </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($marketStatusData as $key => $value)
                            <tr>
                                <td>{{ $value['market_type'] }}</td>
                                <td>{{ $value['status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="tabsGetOrders" class="tab-pane fade @if ($activeTab == 'GetOrders') active show @endif" role="tabpanel">
                <h5>Orders</h5>
                <div class="input-group mb-3">
                    <button class="btn btn-info" type="button" wire:click="getOrders" id="button-addon2">Get</button>
                </div>
                <table class="table table-striped table-sm table-bordered text-capitalize">
                    <thead>
                        <tr>
                            <th> Symbol </th>
                            <th> Product Type </th>
                            <th> Qty </th>
                            <th> Type </th>
                            <th> Segment </th>
                            <th> orderTag </th>
                            <th> Status </th>
                            <th> orderDateTime </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ordersData as $key => $value)
                            <tr>
                                <td>{{ $value['symbol'] }}</td>
                                <td>{{ $value['productType'] }}</td>
                                <td>
                                    <span>Qty :{{ $value['qty'] }}</span>
                                    <span>( Disclosed :{{ $value['disclosedQty'] }}</span>
                                    <span>Remaining :{{ $value['remainingQuantity'] }})</span>
                                </td>
                                <td>{{ orderTypes($value['type']) }}</td>
                                <td>{{ orderSegments($value['segment']) }}</td>
                                <td>{{ $value['orderTag'] }}</td>
                                <td>{{ orderStatus($value['status']) }}</td>
                                <td>{{ $value['orderDateTime'] }}</td>
                            </tr>
                            <tr>
                                <td colspan="8" class="text-end">{{ $value['message'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="tabsHistory" class="tab-pane fade @if ($activeTab == 'History') active show @endif" role="tabpanel">
                <h5>History</h5>
                <div class="row">
                    <div class="col-md-3">
                        <label for="_dm-inputCity" class="form-label">Symbol</label>
                        <input type="text" class="form-control" wire:model="symbol" placeholder="Enter Your Symbols : NSE:SBIN-EQ">
                    </div>
                    <div class="col-md-3">
                        <label for="resolution" class="form-label">Resolution</label>
                        {!! html()->select('resolution', resolutionOptions())->value('')->class('form-control')->attribute('wire:model', 'resolution') !!}
                    </div>
                    <div class="col-md-5">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="range_from" class="form-label">Range From</label>
                                {!! html()->date('range_from')->value('')->class('form-control')->attribute('wire:model', 'range_from') !!}
                            </div>
                            <div class="col-md-6">
                                <label for="range_to" class="form-label">Range To</label>
                                {!! html()->date('range_to')->value('')->class('form-control')->attribute('wire:model', 'range_to') !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1"> <br>
                        <button class="btn btn-info" type="button" wire:click="getHistory" id="button-addon2">Get</button>
                    </div>
                </div>
                <div class="row">
                    <div class="tradingview-widget-container">
                        <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js" async>
                            {
                                "autosize": true,
                                "symbol": "{{ $symbol }}",
                                "interval": "D",
                                "timezone": "Etc/UTC",
                                "theme": "light",
                                "style": "1",
                                "locale": "en",
                                "allow_symbol_change": true,
                                "calendar": false,
                            }
                        </script>
                    </div>
                </div>
            </div>
            <div id="tabsPosition" class="tab-pane fade @if ($activeTab == 'Position') active show @endif" role="tabpanel">
                <h5>Position</h5>
                <div class="row">
                    <div class="col-md-11">
                    </div>
                    <div class="col-md-1"> <br>
                        <button class="btn btn-info pull-right" type="button" wire:click="getPosition" id="button-addon2">Get</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <table class="table table-striped table-sm table-bordered text-capitalize">
                            <tr>
                                <th>Open</th>
                                <th>{{ $positionData['overall']['count_open'] ?? 0 }}</th>
                            </tr>
                            <tr>
                                <th> Total Count</th>
                                <th>{{ $positionData['overall']['count_total'] ?? 0 }}</th>
                            </tr>
                            <tr>
                                <th> PL Realized</th>
                                <th>{{ $positionData['overall']['pl_realized'] ?? 0 }}</th>
                            </tr>
                            <tr>
                                <th> PL Unrealized</th>
                                <th>{{ $positionData['overall']['pl_unrealized'] ?? 0 }}</th>
                            </tr>
                            <tr>
                                <th> PL Total</th>
                                <th>{{ $positionData['overall']['pl_total'] ?? 0 }}</th>
                            </tr>
                        </table>
                    </div>
                    <div class="col-8">
                        <table class="table table-striped table-sm table-bordered text-capitalize">
                            <thead>
                                <tr>
                                    <th width="10%">symbol</th>
                                    <th>buyAvg</th>
                                    <th>buyQty</th>
                                    <th>buyVal</th>
                                    <th>netAvg</th>
                                    <th width="10%">pl</th>
                                </tr>
                            </thead>
                            <tbody wire:poll.1000s="getPosition">
                                @foreach ($positionData['netPositions'] ?? [] as $item)
                                    <tr>
                                        <th>{{ $item['symbol'] }}</th>
                                        <th>{{ $item['buyAvg'] }}</th>
                                        <th>{{ $item['buyQty'] }}</th>
                                        <th>{{ $item['buyVal'] }}</th>
                                        <th>{{ $item['netAvg'] }}</th>
                                        <th>{{ $item['pl'] }}</th>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5">Total</th>
                                    <th> {{ collect($positionData['netPositions'] ?? [])->sum('pl') }} </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

        <script>
            window.addEventListener('DOMContentLoaded', (event) => {
                // renderChart(@js($seriesData), @js($seriesDataLinear));
            });
            window.addEventListener('renderChart', (event) => {
                // chart.destroy();
                // chartBar.destroy();
                // renderChart(event.detail[0].seriesData, event.detail[0].seriesDataLinear);
            }, false);
        </script>

        <script>
            function renderChart(seriesData, seriesDataLinear) {
                var options = {
                    series: [{
                        data: seriesData
                    }],
                    chart: {
                        type: 'candlestick',
                        height: 290,
                        id: 'candles',
                        toolbar: {
                            autoSelected: 'pan',
                            show: false
                        },
                        zoom: {
                            enabled: false
                        },
                    },
                    plotOptions: {
                        candlestick: {
                            colors: {
                                upward: '#3C90EB',
                                downward: '#DF7D46'
                            }
                        }
                    },
                    xaxis: {
                        type: 'datetime'
                    }
                };
                chart = new ApexCharts(document.querySelector("#chart-candlestick"), options);
                chart.render();

                var optionsBar = {
                    series: [{
                        name: 'volume',
                        data: seriesDataLinear,
                    }],
                    chart: {
                        height: 160,
                        type: 'bar',
                        brush: {
                            enabled: true,
                            target: 'candles'
                        },
                        selection: {
                            enabled: false,
                            fill: {
                                color: '#ccc',
                                opacity: 0.4
                            },
                            stroke: {
                                color: '#0D47A1',
                            }
                        },
                    },
                    dataLabels: {
                        enabled: false
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '80%',
                            colors: {
                                ranges: [{
                                    from: -1000,
                                    to: 0,
                                    color: '#F15B46'
                                }, {
                                    from: 1,
                                    to: 10000,
                                    color: '#FEB019'
                                }],

                            },
                        }
                    },
                    stroke: {
                        width: 0
                    },
                    xaxis: {
                        type: 'datetime',
                        axisBorder: {
                            offsetX: 13
                        }
                    },
                    yaxis: {
                        labels: {
                            show: false
                        }
                    }
                };

                chartBar = new ApexCharts(document.querySelector("#chart-bar"), optionsBar);
                chartBar.render();
            }
        </script>
    @endpush
</div>
