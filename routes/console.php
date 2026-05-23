<?php

use App\Models\Configuration;
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\RunHealthChecksCommand;

Schedule::command('backup:run --only-db')->daily();
Schedule::command('backup:clean')->daily();

if (config('constants.auto_pull_enabled')) {
    Schedule::command('git:auto-pull '.config('constants.auto_pull_branch').' --force --build')
        ->everyMinute()
        ->withoutOverlapping();
}

// Process any remaining visitor batches that haven't reached batch size
Schedule::command('visitors:process-batches')->everyFiveMinutes();

Schedule::command(RunHealthChecksCommand::class)->daily();

// Close all open sale day sessions daily at start of day (if enabled)
Schedule::command('sale-day-sessions:close-daily')
    ->dailyAt('00:00')
    ->when(function () {
        return Configuration::where('key', 'auto_close_day_sessions_enabled')->value('value') === 'yes';
    });

Schedule::command('send:daily-sale-summary')->dailyAt('00:10');
Schedule::command('assets:post-depreciation')->dailyAt('00:15')->withoutOverlapping();

// ============================================================================
// Trading platform schedule
// ----------------------------------------------------------------------------
// Toggle TRADING_SCHEDULE_ENABLED=true in .env to flip these on. All entries
// run sequentially with withoutOverlapping() so a slow tick can never collide
// with the next one. Times follow the server clock — config('trading.timezone')
// is informational only here.
// ----------------------------------------------------------------------------
if (config('trading.schedule.enabled')) {
    [$buyFrom, $buyTo] = config('trading.schedule.buy_between');
    [$sellFrom, $sellTo] = config('trading.schedule.sell_between');
    [$quickFrom, $quickTo] = config('trading.schedule.quick_between');

    // Optimized unified trading commands
    Schedule::command('trade:unified --action=buy')
        ->everyFiveMinutes()->between($buyFrom, $buyTo)->withoutOverlapping();
    Schedule::command('trade:unified --action=sell')
        ->everyFiveMinutes()->between($sellFrom, $sellTo)->withoutOverlapping();
    Schedule::command('trade:unified --action=sell --sell-all')
        ->dailyAt(config('trading.schedule.forced_flatten_at'))->withoutOverlapping();

    // Quick trading: Buy best stock and sell losing positions every 2 minutes
    Schedule::command('trade:quick')
        ->everyTwoMinutes()->between($quickFrom, $quickTo)->weekdays()->withoutOverlapping();

    // Force sell all stocks after the quick-trading window (Mon–Fri)
    Schedule::command('trade:quick --sell-all')
        ->dailyAt(config('trading.schedule.quick_flatten_at'))->weekdays()->withoutOverlapping();

    // Daily AI post-mortem after the trading day ends
    Schedule::command('trade:analyse')
        ->dailyAt(config('trading.schedule.analyse_at'))->weekdays();
}

// Legacy reference — keep commented as documentation of the original cadence.
// Schedule::command('trade:unified --action=buy')->everyFiveMinutes()->between('05:10', '09:55');
// Schedule::command('trade:unified --action=sell')->everyFiveMinutes()->between('05:20', '09:55');
// Schedule::command('trade:unified --action=sell --sell-all')->dailyAt('09:55');
// Schedule::command('trade:quick')->everyTwoMinutes()->between('04:30', '09:30')->weekdays();
// Schedule::command('trade:quick --sell-all')->dailyAt('09:31')->weekdays();
