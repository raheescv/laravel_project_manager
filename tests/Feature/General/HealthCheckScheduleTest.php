<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Production health checks include ScheduleCheck and QueueCheck, which only
 * pass when their heartbeat commands keep running; without them the nightly
 * health:check always exits 1.
 */
it('schedules the heartbeats the production health checks read', function (): void {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn (Event $event): string => (string) $event->command);

    expect($commands->contains(fn (string $command): bool => str_contains($command, 'health:schedule-check-heartbeat')))->toBeTrue()
        ->and($commands->contains(fn (string $command): bool => str_contains($command, 'health:queue-check-heartbeat')))->toBeTrue()
        ->and($commands->contains(fn (string $command): bool => str_contains($command, 'health:check')))->toBeTrue();
});
