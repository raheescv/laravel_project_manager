<?php

namespace App\Trading\Contracts;

use App\Trading\DataObjects\Bar;
use App\Trading\DataObjects\Signal;

/**
 * A Strategy decides whether to enter, exit, or hold a symbol.
 *
 * Strategies are pure — they read bars + context and return a Signal.
 * They MUST NOT place orders, write to the DB, or call brokers directly.
 * That separation is what lets the same class run live and in backtest.
 */
interface Strategy
{
    public function code(): string;

    public function name(): string;

    /** @return array<string, mixed> Default tunable parameters */
    public function defaultParameters(): array;

    /**
     * Score a symbol and return a Signal.
     *
     * @param  Bar[]  $bars  Most recent bars first? No — chronological asc.
     * @param  array<string, mixed>  $context  Risk state, portfolio snapshot, params
     */
    public function score(string $symbol, array $bars, array $context = []): Signal;
}
