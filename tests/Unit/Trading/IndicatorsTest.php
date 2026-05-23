<?php

use App\Trading\Support\Indicators;

it('computes SMA correctly', function () {
    expect(Indicators::sma([1, 2, 3, 4, 5], 5))->toBe(3.0)
        ->and(Indicators::sma([1, 2, 3, 4, 5], 3))->toBe(4.0);
});

it('returns ~100 RSI when there are only gains', function () {
    $rising = range(1, 30);
    expect(Indicators::rsi($rising, 14))->toBe(100.0);
});

it('returns 50 RSI when not enough data', function () {
    expect(Indicators::rsi([1, 2, 3], 14))->toBe(50.0);
});

it('computes max drawdown for a falling curve', function () {
    $equity = [100, 80, 60, 90, 70];
    $dd = Indicators::maxDrawdown($equity);
    expect($dd)->toBeLessThanOrEqual(0.0)
        ->and(round($dd, 2))->toBe(-40.0);
});

it('computes pctChange', function () {
    $closes = [100, 105, 110, 115, 120, 125];
    expect(Indicators::pctChange($closes, 5))->toBe(25.0);
});
