<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Health\Facades\Health;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Checks\Checks\BackupsCheck;

class HealthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $environment = app()->environment();
        
        $checks = [
            // Core Laravel Checks
            DatabaseCheck::new(),
            CacheCheck::new(),
            
            // Database Performance
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(50)
                ->failWhenMoreConnectionsThan(100),
            
            // System Resources
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),
        ];

        // Add production-specific checks
        if ($environment === 'production') {
            $checks[] = EnvironmentCheck::new()->expectEnvironment('production');
            $checks[] = DebugModeCheck::new();
            $checks[] = OptimizedAppCheck::new();
            $checks[] = QueueCheck::new();
            $checks[] = ScheduleCheck::new()->heartbeatMaxAgeInMinutes(2);
            
            // Only add Horizon check if Redis is configured
            if (config('database.redis.default')) {
                $checks[] = HorizonCheck::new();
            }
        } else {
            // Development environment checks
            $checks[] = EnvironmentCheck::new()->expectEnvironment($environment);
        }

        Health::checks($checks);
    }
}
