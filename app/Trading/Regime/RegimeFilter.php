<?php

namespace App\Trading\Regime;

use App\Trading\Brokers\BrokerManager;
use App\Trading\DataObjects\Bar;
use App\Trading\Support\Indicators;

/**
 * Gates fresh long entries on the broad-market regime.
 *
 * The single biggest predictor of an intraday system's drawdown is taking
 * fresh longs against a trending-down NIFTY. This filter doesn't predict
 * — it simply refuses to add risk when the tape is hostile.
 */
final class RegimeFilter
{
    public function __construct(
        private readonly BrokerManager $brokers,
        private readonly string $indexSymbol = 'NIFTY',
        private readonly int $emaPeriod = 20,
        private readonly string $interval = '1d',
    ) {}

    public function allowsLongEntries(): array
    {
        try {
            $broker = $this->brokers->broker();
            $bars = $broker->historicalBars($this->indexSymbol, $this->interval, $this->emaPeriod * 3);

            if (count($bars) < $this->emaPeriod + 2) {
                return ['ok' => true, 'reason' => 'insufficient_history'];
            }

            $closes = array_map(fn (Bar $b) => $b->close, $bars);
            $ema = Indicators::ema($closes, $this->emaPeriod);
            $latest = end($closes);

            if ($latest < $ema) {
                return [
                    'ok' => false,
                    'reason' => "NIFTY {$latest} below {$this->emaPeriod}-EMA {$ema}",
                ];
            }

            return ['ok' => true, 'reason' => "NIFTY above {$this->emaPeriod}-EMA"];
        } catch (\Throwable $e) {
            return ['ok' => true, 'reason' => 'regime_check_failed: '.$e->getMessage()];
        }
    }
}
