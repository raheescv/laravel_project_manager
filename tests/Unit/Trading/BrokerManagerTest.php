<?php

use App\Trading\Brokers\BrokerManager;
use App\Trading\Brokers\KiteBrokerAdapter;

it('registers and resolves brokers', function () {
    $m = new BrokerManager();
    $m->register(new KiteBrokerAdapter(), default: true);
    expect($m->broker()->code())->toBe('kite')
        ->and($m->has('kite'))->toBeTrue();
});

it('throws when asking for an unknown broker', function () {
    $m = new BrokerManager();
    $m->register(new KiteBrokerAdapter());
    expect(fn () => $m->broker('flat_trade'))->toThrow(\RuntimeException::class);
});
