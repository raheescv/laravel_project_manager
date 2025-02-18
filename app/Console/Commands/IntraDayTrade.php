<?php

namespace App\Console\Commands;

use App\Services\FyersService;
use App\Services\TradingStrategyService;
use Exception;
use Illuminate\Console\Command;

class IntraDayTrade extends Command
{
    protected $signature = 'trade:intra-day {symbol}';

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
        $symbol = $this->argument('symbol');
        try {
            $data = $this->fyersService->fetchHistoricalData($symbol, 5, 5);
            $prices = array_map(fn ($candle) => $candle[4], $data['candles'] ?? []);
            $signal = $this->tradingStrategyService->generateTradeSignal($prices);
            if ($signal === 'BUY') {
                $order = $this->fyersService->placeOrder($symbol, 'BUY', 10);
                $this->info('BUY Order Placed: '.json_encode($order));
            } elseif ($signal === 'SELL') {
                $order = $this->fyersService->placeOrder($symbol, 'SELL', 10);
                $this->info('SELL Order Placed: '.json_encode($order));
            } else {
                $this->info("No Trade Signal for $symbol");
            }

            info("Trade Signal for $symbol: $signal");

        } catch (Exception $e) {
            $this->error('Error: '.$e->getMessage());
        }
    }
}
