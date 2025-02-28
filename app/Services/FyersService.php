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
        $this->headers = [
            'Authorization' => "$this->clientId:$this->accessToken",
            'Accept' => 'application/json',
        ];
        $this->http = Http::withHeaders($this->headers);

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
        $i = 0;
        buy:
        $orderData = [
            'symbol' => "{$symbol}",
            'qty' => intval($qty),
            'type' => 2, // 1 => Limit Order 2 => Market Order 3 => Stop Order (SL-M) 4 => Stop limit Order (SL-L)
            'side' => $type === 'BUY' ? 1 : -1, // 1 for Buy, -1 for Sell
            'productType' => 'INTRADAY',
            'limitPrice' => 0, // 0 for Market orders
            'stopPrice' => 0,
            'disclosedQty' => 0,
            'validity' => 'DAY',
            'offlineOrder' => false,
            'stopLoss' => 0,
            'takeProfit' => 0,
        ];
        $response = $this->http->post($this->apiUrl.'/api/v3/orders/sync', $orderData);
        $response = $response->json();
        if ($response['s'] != 'ok') {
            if ($response['message']) {
                if ($i == 0) {
                    if (str_contains($response['message'], ' not a multiple of minimum lot size ')) {
                        $explode = explode(' not a multiple of minimum lot size ', $response['message']);
                        $qty = $explode[1] ?? 10;
                        $i++;
                        goto buy;
                    }
                }
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
