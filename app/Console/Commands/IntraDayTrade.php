<?php

namespace App\Console\Commands;

use App\Models\TradingOrder;
use App\Services\FyersService;
use App\Services\TradingStrategyService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IntraDayTrade extends Command
{
    protected $signature = 'trade:intra-day {symbol} {qty}';

    protected $description = 'Automate Intra Day Trading with Fyers API';

    protected $fyersService;

    protected $tradingStrategyService;

    public function __construct(FyersService $fyersService, TradingStrategyService $tradingStrategyService)
    {
        parent::__construct();
        $this->fyersService = $fyersService;
        $this->tradingStrategyService = $tradingStrategyService;
    }

    public function handle()
    {
        $no_data_symbols = cache('no_data_symbols', []);
        $symbol = $this->argument('symbol');
        $qty = $this->argument('qty', 10);
        try {
            $data = $this->fyersService->fetchHistoricalData($symbol, 5, 5);
            $prices = array_map(fn ($candle) => $candle[4], $data['candles'] ?? []);
            [$signal,$currentPrice] = $this->tradingStrategyService->generateTradeSignal($prices);
            if ($signal === 'BUY') {
                $order = $this->fyersService->placeOrder($symbol, 'BUY', $qty);
                $this->info('BUY Order Placed: '.json_encode($order));

                $tradingOrderData = [
                    'symbol' => $symbol,
                    'type' => 'BUY',
                    'price' => $currentPrice,
                    'quantity' => $qty,
                    'stop_loss' => $currentPrice * 0.98, // Example: 2% SL
                    'take_profit' => $currentPrice * 2,
                    'status' => 'OPEN',
                ];
                TradingOrder::create($tradingOrderData);

            } elseif ($signal === 'SELL') {
                $order = $this->fyersService->placeOrder($symbol, 'SELL', $qty);
                $this->info('SELL Order Placed: '.json_encode($order));
            } else {
                $this->info("No Trade Signal for $symbol");
            }
            if ($signal != 'HOLD') {
                info("Trade Signal for $symbol: $signal");
            }

        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
            if (str_contains($e->getMessage(), 'RED')) {
                $no_data_symbols[] = $symbol;
                Cache::put('no_data_symbols', $no_data_symbols, now()->addYear());
            }
            if ($e->getMessage() != 'no_data') {
                Log::error('Error: '.$symbol.' '.$e->getMessage());
            } else {
                $no_data_symbols[] = $symbol;
                Cache::put('no_data_symbols', $no_data_symbols, now()->addYear());
                info('no_data_symbols count : '.count($no_data_symbols));
            }
        }
    }
}
