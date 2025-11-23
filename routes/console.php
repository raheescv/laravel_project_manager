<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\RunHealthChecksCommand;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('backup:run --only-db')->daily();
Schedule::command('backup:clean')->daily();

Schedule::command('git:auto-pull main --force --build')->everyMinute();

// Process any remaining visitor batches that haven't reached batch size
Schedule::command('visitors:process-batches')->everyFiveMinutes();

Schedule::command(RunHealthChecksCommand::class)->daily();

// Optimized unified trading commands
// Schedule::command('trade:unified --action=buy')->everyFiveMinutes()->between('05:10', '09:55');
// Schedule::command('trade:unified --action=sell')->everyFiveMinutes()->between('05:20', '09:55');
// Schedule::command('trade:unified --action=sell --sell-all')->dailyAt('09:55');

// Quick trading: Buy best stock and sell losing positions every 5 minutes
Schedule::command('trade:quick')->everyTwoMinutes()->between('04:30', '09:30')->weekdays();

// Force sell all stocks after 09:50 (Monday to Friday only)
Schedule::command('trade:quick --sell-all')->dailyAt('09:31')->weekdays();
