<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trading platform — core tables for the 7-module extension.
 *
 * Keeps existing `trading_orders` table untouched. Everything new lives
 * in its own table so the existing commands keep working during rollout.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('trading_brokers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // flat_trade, kite, paper
            $table->string('label');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('config')->nullable();        // non-secret options
            $table->text('credentials')->nullable();   // Crypt::encryptString json
            $table->unsignedInteger('rate_limit_per_min')->default(60);
            $table->timestamp('last_healthy_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trading_strategies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('parameters')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('paper_mode')->default(true);
            $table->unsignedInteger('capital_weight')->default(0); // 0–100
            $table->timestamps();
        });

        Schema::create('trading_strategy_runs', function (Blueprint $table) {
            $table->id();
            $table->string('strategy_code')->index();
            $table->string('command')->nullable(); // trade:quick / trade:unified
            $table->string('symbol')->nullable();
            $table->string('action')->nullable(); // BUY/SELL/HOLD/FLATTEN/SKIP
            $table->decimal('score', 12, 4)->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('outcome')->nullable(); // placed, rejected, skipped, paper
            $table->text('reason')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamp('ran_at')->index();
            $table->timestamps();
        });

        Schema::create('trading_risk_events', function (Blueprint $table) {
            $table->id();
            $table->string('rule_code')->index();
            $table->string('severity')->default('warning'); // info, warning, blocked, breaker
            $table->string('symbol')->nullable();
            $table->string('strategy_code')->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('trading_circuit_states', function (Blueprint $table) {
            $table->id();
            $table->date('trading_day')->unique();
            $table->boolean('breaker_tripped')->default(false);
            $table->string('trip_reason')->nullable();
            $table->decimal('realized_pnl', 14, 2)->default(0);
            $table->decimal('unrealized_pnl', 14, 2)->default(0);
            $table->unsignedInteger('trades_count')->default(0);
            $table->timestamp('tripped_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trading_paper_orders', function (Blueprint $table) {
            $table->id();
            $table->string('strategy_code')->nullable()->index();
            $table->string('symbol');
            $table->string('side');     // BUY/SELL
            $table->string('type')->default('MARKET');
            $table->integer('quantity');
            $table->decimal('price', 12, 4)->nullable();
            $table->decimal('filled_price', 12, 4)->nullable();
            $table->integer('filled_qty')->default(0);
            $table->decimal('stop_loss', 12, 4)->nullable();
            $table->decimal('target', 12, 4)->nullable();
            $table->string('status')->default('OPEN'); // OPEN/CLOSED/CANCELED
            $table->decimal('exit_price', 12, 4)->nullable();
            $table->decimal('pnl', 14, 2)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trading_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event'); // OrderPlaced, StopHit, etc, or 'price', 'pnl'
            $table->json('conditions')->nullable();
            $table->json('channels')->nullable(); // ["database","telegram"]
            $table->string('severity')->default('info');
            $table->boolean('is_active')->default(true);
            $table->time('quiet_from')->nullable();
            $table->time('quiet_until')->nullable();
            $table->unsignedInteger('rate_limit_per_hour')->default(60);
            $table->timestamps();
        });

        Schema::create('trading_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->nullable()->constrained('trading_alert_rules')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('severity')->default('info');
            $table->json('payload')->nullable();
            $table->json('delivered_to')->nullable(); // array of channel codes that accepted
            $table->json('failed_channels')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trading_bars', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->index();
            $table->string('exchange')->default('NSE');
            $table->string('interval')->default('1m');
            $table->timestamp('ts');
            $table->decimal('open', 12, 4);
            $table->decimal('high', 12, 4);
            $table->decimal('low', 12, 4);
            $table->decimal('close', 12, 4);
            $table->unsignedBigInteger('volume')->default(0);
            $table->unique(['symbol', 'interval', 'ts']);
        });

        Schema::create('trading_backtest_runs', function (Blueprint $table) {
            $table->id();
            $table->string('strategy_code');
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('initial_capital', 14, 2);
            $table->decimal('final_equity', 14, 2)->nullable();
            $table->decimal('total_return_percent', 8, 4)->nullable();
            $table->decimal('max_drawdown_percent', 8, 4)->nullable();
            $table->decimal('sharpe', 8, 4)->nullable();
            $table->decimal('sortino', 8, 4)->nullable();
            $table->decimal('win_rate', 6, 4)->nullable();
            $table->unsignedInteger('trades_count')->default(0);
            $table->json('parameters')->nullable();
            $table->json('equity_curve')->nullable();
            $table->json('trades')->nullable();
            $table->string('status')->default('queued'); // queued, running, done, failed
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('trading_sentiment_scores', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->index();
            $table->string('source'); // news, twitter, reddit
            $table->decimal('score', 6, 4); // -1 .. 1
            $table->decimal('confidence', 5, 4)->default(0);
            $table->text('snippet')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('observed_at');
            $table->timestamps();
        });

        Schema::create('trading_ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('kind'); // daily_postmortem, trade_critique, chat
            $table->date('for_date')->nullable();
            $table->string('symbol')->nullable();
            $table->text('prompt')->nullable();
            $table->longText('response')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->json('context')->nullable();
            $table->timestamps();
        });

        Schema::create('trading_command_runs', function (Blueprint $table) {
            $table->id();
            $table->string('command');           // trade:unified / trade:quick
            $table->string('action')->nullable(); // buy/sell/sell-all
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('outcome')->default('running'); // running/success/error/skipped
            $table->text('error')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
            $table->index(['command', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_command_runs');
        Schema::dropIfExists('trading_ai_analyses');
        Schema::dropIfExists('trading_sentiment_scores');
        Schema::dropIfExists('trading_backtest_runs');
        Schema::dropIfExists('trading_bars');
        Schema::dropIfExists('trading_alerts');
        Schema::dropIfExists('trading_alert_rules');
        Schema::dropIfExists('trading_paper_orders');
        Schema::dropIfExists('trading_circuit_states');
        Schema::dropIfExists('trading_risk_events');
        Schema::dropIfExists('trading_strategy_runs');
        Schema::dropIfExists('trading_strategies');
        Schema::dropIfExists('trading_brokers');
    }
};
