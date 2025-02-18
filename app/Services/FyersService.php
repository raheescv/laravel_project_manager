<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class FyersService
{
    protected $clientId;

    protected $accessToken;

    public $headers;

    protected $apiUrl;

    public $http;

    public function __construct()
    {
        $this->clientId = config('services.fyers.client_id');
        $this->accessToken = config('services.fyers.access_token');
        $this->apiUrl = config('services.fyers.url');
        $this->headers = ['Authorization' => "Bearer {$this->accessToken}"];
        $this->http = Http::withHeaders([
            'Authorization' => "$this->clientId:$this->accessToken",
            'Accept' => 'application/json',
        ]);
    }

    public function fetchStockData($symbol)
    {
        $link = "$this->apiUrl/data/quotes/?symbols=$symbol";
        $response = $this->http->get($link);
        $response = $response->json();
        if ($response['code'] != 200) {
            throw new Exception($response['message']);
        }

        return $response;
    }

    public function placeOrder($symbol, $type, $qty)
    {
        $orderData = [
            'symbol' => "{$symbol}",
            'qty' => $qty,
            'type' => $type, // 1=Market, 2=Limit, 3=SL, 4=SLM
            'side' => $type === 'BUY' ? 1 : -1, // 1 for Buy, -1 for Sell
            'productType' => 'INTRADAY',
            'limitPrice' => 0, // 0 for Market orders
            'stopPrice' => 0,
            'disclosedQty' => 0,
            'validity' => 'DAY',
            'offlineOrder' => false,
            // 'limitPrice' => 7.9,
            'stopLoss' => 0,
            'takeProfit' => 0,
        ];
        $response = $this->http->post($this->apiUrl.'/api/v3/orders/sync', $orderData);
        if ($response['s'] != 'ok') {
            if ($response['message']) {
                throw new Exception($response['message'], 1);
            } else {
                throw new Exception($response['s'], 1);
            }
        }

        return $response;
    }

    public function fetchHistoricalData($symbol, $resolution = '5', $days = 5)
    {
        $to = time();
        $from = strtotime("-{$days} days", $to);

        $query = [
            'symbol' => "{$symbol}",
            'resolution' => $resolution,
            'date_format' => 1,
            'range_from' => date('Y-m-d', $from),
            'range_to' => date('Y-m-d', $to),
            'cont_flag' => '1',
        ];

        $response = $this->http->get("{$this->apiUrl}/data/history", $query);
        $response = $response->json();
        if ($response['s'] != 'ok') {
            if ($response['message']) {
                throw new Exception($response['message'], 1);
            } else {
                throw new Exception($response['s'], 1);
            }
        }

        return $response;
    }
}
