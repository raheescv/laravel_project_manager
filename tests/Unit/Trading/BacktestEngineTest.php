<?php

use App\Trading\Backtest\BacktestEngine;
use App\Trading\DataObjects\Bar;
use App\Trading\Strategies\MomentumScoreStrategy;

it('runs end-to-end without errors and produces equity curve', function () {
    $closes = array_merge(range(100, 150, 1), range(150, 120, -1), range(120, 170, 1));
    $bars = [];
    foreach ($closes as $i => $c) {
        $bars[] = new Bar('TEST-EQ', strtotime('2025-01-01 09:15') + $i * 300, $c, $c * 1.005, $c * 0.995, $c, 1000);
    }

    $engine = new BacktestEngine(initialCapital: 100_000, capitalPerTrade: 20_000);
    $result = $engine->run(new MomentumScoreStrategy(), ['TEST-EQ' => $bars]);

    expect($result->equityCurve)->not->toBeEmpty()
        ->and($result->finalEquity)->toBeGreaterThan(0)
        ->and($result->trades)->toBeArray();
});
