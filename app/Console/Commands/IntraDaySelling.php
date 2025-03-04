<?php

namespace App\Console\Commands;

use App\Models\TradingOrder;
use App\Services\FyersService;
use Illuminate\Console\Command;

class IntraDaySelling extends Command
{
    protected $signature = 'app:intra-day-selling';

    protected $description = 'Sell Orders';

    public $fyersService;

    public function __construct(FyersService $fyersService)
    {
        parent::__construct();
        $this->fyersService = $fyersService;
    }

    public function handle()
    {
        $openOrders = TradingOrder::where('type', 'BUY')->where('status', 'OPEN')->get();

        foreach ($openOrders as $order) {
            $data = $this->fyersService->fetchHistoricalData($order->symbol, 5, 5);
            $prices = array_map(fn ($candle) => $candle[4], $data['candles'] ?? []);

            $currentPrice = end($prices);
            if ($currentPrice >= $order->take_profit || $currentPrice <= $order->stop_loss) {
                $symbol = $order->symbol;
                TradingOrder::create([
                    'symbol' => $symbol,
                    'type' => 'SELL',
                    'price' => $currentPrice,
                    'quantity' => $order->quantity,
                    'status' => 'OPEN',
                ]);
                $order->update(['status' => 'CLOSED']);
                $response = $this->fyersService->placeOrder($symbol, 'SELL', $order->quantity);
                info("Sell order placed for {$symbol} at price {$currentPrice}".json_encode($response));
            }
        }
    }
}
