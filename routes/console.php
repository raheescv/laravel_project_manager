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

// Process any remaining visitor batches that haven't reached batch size
Schedule::command('visitors:process-batches')->everyFiveMinutes();

Schedule::command(RunHealthChecksCommand::class)->daily();
