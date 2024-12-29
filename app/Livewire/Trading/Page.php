<?php

namespace App\Livewire\Trading;

use Illuminate\Support\Facades\Http;
use Livewire\Component;

class Page extends Component
{
    public $fyers_link;

    public $secret_id;

    public $client_id;

    public $access_token;

    public $refresh_token;

    public $headers;

    public $user;

    public $symbol;

    public $resolution = 30;

    public $range_from;

    public $range_to;

    public $rsi_value;

    public $quoteData = [];

    public $depthData = [];

    public $marketStatusData = [];

    public $ordersData = [];

    public $positionData = [];

    public $historyData = [];

    public $seriesData = [];

    public $seriesDataLinear = [];

    public function mount()
    {
        $this->fyers_link = config('constants.fyers.link');
        $this->client_id = config('constants.fyers.client_id');
        $this->secret_id = config('constants.fyers.secret_id');
        $this->access_token = config('constants.fyers.access_token');
        $this->refresh_token = config('constants.fyers.refresh_token');
        $this->range_from = date('Y-m-d', strtotime('-14 day'));
        $this->range_to = date('Y-m-d');
        $this->refresh_token = config('constants.fyers.refresh_token');
        $this->headers = [
            'Authorization' => "$this->client_id:$this->access_token",
            'Accept' => 'application/json',
        ];
        $this->symbol = 'NSE:NIFTY50-INDEX';
        // $this->symbol = 'NSE:SBIN-EQ';
        $this->profile();
        $this->funds();
        $this->getHistory();
        $this->getOrders();
    }

    public function profile()
    {
        $link = "$this->fyers_link/api/v3/profile";
        $response = Http::withHeaders($this->headers)->get($link);
        $response = $response->json();
        if ($response['code'] != 200) {
            $this->dispatch('error', ['message' => $response['message']]);

            return false;
        }
        $this->user['profile'] = $response['data'];
    }

    public function funds()
    {
        $link = "$this->fyers_link/api/v3/funds";
        $response = Http::withHeaders($this->headers)->get($link);
        $response = $response->json();
        if ($response['code'] != 200) {
            $this->dispatch('error', ['message' => $response['message']]);

            return false;
        }
        $this->user['funds'] = $response['fund_limit'];
    }

    public function getQuotes()
    {
        $this->quoteData = [];
        $link = "$this->fyers_link/data/quotes/?symbols=$this->symbol";
        $response = Http::withHeaders($this->headers)->get($link);
        $response = $response->json();
        if ($response['code'] != 200) {
            $this->dispatch('error', ['message' => $response['message']]);

            return false;
        }
        $response = $response['d'][0];
        if (isset($response['v']['s'])) {
            $this->dispatch('error', ['message' => $response['v']['errmsg']]);

            return false;
        }
        $this->quoteData = $response['v'];
    }

    public function getPosition()
    {
        $this->positionData = [];
        $link = "$this->fyers_link/api/v3/positions?side=1";
        $response = Http::withHeaders($this->headers)->get($link);
        $response = $response->json();
        if ($response['code'] != 200) {
            $this->dispatch('error', ['message' => $response['message']]);

            return false;
        }
        $this->positionData = $response;
    }

    public function getMarketStatus()
    {
        $this->marketStatusData = [];
        $link = "$this->fyers_link/data/marketStatus";
        $response = Http::withHeaders($this->headers)->get($link);
        $response = $response->json();
        if ($response['code'] != 200) {
            $this->dispatch('error', ['message' => $response['message']]);

            return false;
        }
        $response = $response['marketStatus'];
        $this->marketStatusData = $response;
    }

    public function getOrders()
    {
        $this->ordersData = [];
        $link = "$this->fyers_link/api/v3/orders";
        $response = Http::withHeaders($this->headers)->get($link);
        $response = $response->json();
        if ($response['code'] != 200) {
            $this->dispatch('error', ['message' => $response['message']]);

            return false;
        }
        $response = $response['orderBook'];
        $this->ordersData = $response;
        info($this->ordersData);
    }

    public function getMarketDepth()
    {
        $this->depthData = [];
        $link = "$this->fyers_link/data/depth?symbol=$this->symbol&ohlcv_flag=1";
        $response = Http::withHeaders($this->headers)->get($link);
        $response = $response->json();
        if ($response['s'] != 'ok') {
            $this->dispatch('error', ['message' => $response['message']]);

            return false;
        }
        $response = $response['d'][$this->symbol];
        $this->depthData = $response;
    }

    public function getHistory()
    {
        $this->historyData = [];
        $link = "$this->fyers_link/data/history?";
        $link .= "symbol=$this->symbol&";
        $link .= "resolution=$this->resolution&";
        $link .= 'date_format=1&';
        $link .= "range_from=$this->range_from&";
        $link .= "range_to=$this->range_to&";
        $link .= 'cont_flag=1';
        $response = Http::withHeaders($this->headers)->get($link);
        $response = $response->json();
        if ($response['s'] != 'ok') {
            if ($response['message']) {
                $this->dispatch('error', ['message' => $response['message']]);
            } else {
                $this->dispatch('error', ['message' => $response['s']]);
            }

            return false;
        }
        $historyData = $response;
        $this->seriesData = [];
        $this->seriesDataLinear = [];
        foreach ($historyData['candles'] as $value) {
            // seriesData
            // {
            //     x: new Date(2016, 01, 01),
            //     y: [51.98, 56.29, 51.59, 53.85]
            // }
            $singleSeriesData = [
                'x' => $value[0],
                'y' => [$value[1], $value[2], $value[3], $value[4]],
            ];
            $this->seriesData[] = $singleSeriesData;
            // seriesDataLinear
            // {
            //     x: new Date(2016, 01, 01),
            //     y: 3.85
            // }
            $singleSeriesDataLinear = [
                'x' => $value[0],
                'y' => $value[5],
            ];
            $this->seriesDataLinear[] = $singleSeriesDataLinear;
        }
        $this->dispatch('renderChart', [
            'seriesData' => $this->seriesData,
            'seriesDataLinear' => $this->seriesDataLinear,
        ]);
        $this->calculateRSI($this->seriesData);
    }

    public function getAuthCode()
    {
        // OAuth2 - Auth Flow
        $redirect_uri = route('webhook::fyers');
        $response_type = 'code';
        $state = 'sample_state';
        $link = "$this->fyers_link/api/v3/generate-authcode?client_id=$this->client_id&redirect_uri=$redirect_uri&response_type=$response_type&state=$state";

        return $this->redirect($link);
    }

    public function login()
    {
        $payload = [
            'grant_type' => 'authorization_code',
            'appIdHash' => hash('sha256', $this->client_id.':'.$this->secret_id),
            'code' => config('constants.fyers.auth_code'),
        ];
        $response = Http::post("$this->fyers_link/api/v3/validate-authcode", $payload);
        $response = $response->json();
        if ($response['code'] == '200') {
            writeToEnv('FYERS_ACCESS_TOKEN', $response['access_token']);
            writeToEnv('FYERS_REFRESH_TOKEN', $response['refresh_token']);
            $this->dispatch('success', ['message' => 'Access token has been generated.']);
        } else {
            $this->dispatch('error', ['message' => $response['message']]);
        }
    }

    public function calculateRSI($data, $period = 14)
    {
        info($data);
        $closingPrices = array_map(fn ($entry) => end($entry['y']), $data);

        // Step 2: Calculate price changes
        $priceChanges = [];
        for ($i = 1; $i < count($closingPrices); $i++) {
            $priceChanges[] = $closingPrices[$i] - $closingPrices[$i - 1];
        }

        // Step 3: Separate gains and losses
        $gains = array_map(fn ($change) => $change > 0 ? $change : 0, $priceChanges);
        $losses = array_map(fn ($change) => $change < 0 ? abs($change) : 0, $priceChanges);

        // Step 4: Calculate initial average gain and loss
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        $rsiValues = [];
        for ($i = $period; $i < count($gains); $i++) {
            // Update the average gain and loss using smoothing
            $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;

            // Step 5: Calculate RS and RSI
            $rs = $avgLoss == 0 ? 0 : $avgGain / $avgLoss;
            $rsi = $avgLoss == 0 ? 100 : 100 - (100 / (1 + $rs));
            $rsiValues[] = $rsi;
        }
        info($rsiValues);
        $this->rsi_value = $rsi;
    }

    public function render()
    {
        return view('livewire.trading.page');
    }
}
