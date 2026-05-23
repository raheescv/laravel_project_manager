<?php

use App\Trading\DataObjects\OrderRequest;
use App\Trading\Risk\RiskGate;
use App\Trading\Risk\Rules\KillSwitchRule;
use App\Trading\Risk\Rules\MaxPositionSizeRule;
use App\Trading\Risk\Rules\SymbolCooldownRule;

beforeEach(function () {
    KillSwitchRule::disengage();
});

it('approves a small buy when nothing is tripped', function () {
    $gate = new RiskGate([new MaxPositionSizeRule(100_000)]);
    $req = new OrderRequest(symbol: 'SBIN-EQ', side: OrderRequest::SIDE_BUY, quantity: 10, price: 500.0);
    $decision = $gate->evaluate($req);
    expect($decision->approved)->toBeTrue();
});

it('blocks a buy that exceeds max position size', function () {
    $gate = new RiskGate([new MaxPositionSizeRule(1_000)]);
    $req = new OrderRequest(symbol: 'SBIN-EQ', side: OrderRequest::SIDE_BUY, quantity: 10, price: 500.0);
    $decision = $gate->evaluate($req);
    expect($decision->approved)->toBeFalse()
        ->and($decision->ruleCode)->toBe('max_position_size');
});

it('lets sells through even when the kill switch is engaged', function () {
    KillSwitchRule::engage('test');
    $gate = new RiskGate([new KillSwitchRule()]);
    $sell = new OrderRequest(symbol: 'SBIN-EQ', side: OrderRequest::SIDE_SELL, quantity: 10);
    expect($gate->evaluate($sell)->approved)->toBeTrue();
});

it('blocks buys when the kill switch is engaged', function () {
    KillSwitchRule::engage('test');
    $gate = new RiskGate([new KillSwitchRule()]);
    $buy = new OrderRequest(symbol: 'SBIN-EQ', side: OrderRequest::SIDE_BUY, quantity: 10);
    expect($gate->evaluate($buy)->approved)->toBeFalse();
});

it('cools down a symbol after a trip', function () {
    SymbolCooldownRule::trip('TCS-EQ', minutes: 15);
    $gate = new RiskGate([new SymbolCooldownRule(15)]);
    $buy = new OrderRequest(symbol: 'TCS-EQ', side: OrderRequest::SIDE_BUY, quantity: 1);
    expect($gate->evaluate($buy)->approved)->toBeFalse();
});
