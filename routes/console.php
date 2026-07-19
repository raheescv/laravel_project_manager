<?php

use App\Models\Configuration;
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\RunHealthChecksCommand;

Schedule::command('backup:run --only-db')->daily();
Schedule::command('property:status-check')->daily();
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
// with the next one. Window times use config('app.timezone') (see trading.schedule).
// ----------------------------------------------------------------------------
if (config('trading.schedule.enabled')) {
    [$buyFrom, $buyTo] = config('trading.schedule.buy_between');
    [$sellFrom, $sellTo] = config('trading.schedule.sell_between');
    [$quickFrom, $quickTo] = config('trading.schedule.quick_between');

    // Unified pipeline — entry / exit / hard squareoff
    Schedule::command('trade:unified --action=buy --mode=live')
        ->everyFiveMinutes()->between($buyFrom, $buyTo)->withoutOverlapping();
    Schedule::command('trade:unified --action=sell --mode=live')
        ->everyFiveMinutes()->between($sellFrom, $sellTo)->withoutOverlapping();
    Schedule::command('trade:unified --action=squareoff --mode=live')
        ->dailyAt(config('trading.schedule.forced_flatten_at'))->withoutOverlapping();

    // Quick cycle — exit-first then single best entry, every 2 minutes
    Schedule::command('trade:quick --mode=live')
        ->everyTwoMinutes()->between($quickFrom, $quickTo)->weekdays()->withoutOverlapping();

    // Force flatten after the quick-trading window (Mon–Fri)
    Schedule::command('trade:quick --mode=live --sell-all')
        ->dailyAt(config('trading.schedule.quick_flatten_at'))->weekdays()->withoutOverlapping();

    // Daily AI post-mortem after the trading day ends
    Schedule::command('trade:analyse')
        ->dailyAt(config('trading.schedule.analyse_at'))->weekdays();
}
