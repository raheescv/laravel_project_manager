<?php

use App\Trading\DataObjects\Bar;
use App\Trading\DataObjects\Signal;
use App\Trading\Strategies\MomentumScoreStrategy;

function bars(array $closes, string $sym = 'TEST-EQ'): array
{
    $bars = [];
    foreach ($closes as $i => $c) {
        $bars[] = new Bar($sym, time() + $i * 60, $c, $c * 1.01, $c * 0.99, $c, 1000);
    }

    return $bars;
}

it('returns HOLD when not enough bars', function () {
    $s = new MomentumScoreStrategy();
    $sig = $s->score('TEST-EQ', bars(range(100, 110)));
    expect($sig->action)->toBe(Signal::ACTION_HOLD);
});

it('emits a BUY on a strong uptrend', function () {
    $s = new MomentumScoreStrategy();
    // Realistic uptrend — alternating ups and downs with up>down so net
    // trend is positive but RSI stays inside 50–75.
    $closes = [];
    $price = 100.0;
    for ($i = 0; $i < 60; $i++) {
        $price += ($i % 2 === 0) ? -1.0 : 1.5;
        $closes[] = max(50.0, $price);
    }
    $sig = $s->score('TEST-EQ', bars($closes));
    expect($sig->action)->toBe(Signal::ACTION_BUY)
        ->and($sig->stopLoss)->toBeLessThan($sig->suggestedPrice)
        ->and($sig->target)->toBeGreaterThan($sig->suggestedPrice);
});

it('emits HOLD on a downtrend', function () {
    $s = new MomentumScoreStrategy();
    $closes = range(150, 100, -1); // strictly down
    $sig = $s->score('TEST-EQ', bars($closes));
    expect($sig->action)->toBe(Signal::ACTION_HOLD);
});
