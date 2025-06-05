<?php

namespace App\Livewire\System;

use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

class HealthMonitor extends Component
{
    public $refreshInterval = 30; // seconds

    public $autoRefresh = true;

    protected $listeners = [
        'runHealthCheck' => 'runHealthCheck',
        'toggleAutoRefresh' => 'toggleAutoRefresh',
    ];

    public function mount()
    {
        // Run health check on component mount
        $this->runHealthCheck();
    }

    public function runHealthCheck()
    {
        try {
            // Run the health checks using Artisan command
            \Illuminate\Support\Facades\Artisan::call('health:check');
            
            // Clear cached results to get fresh data
            \Illuminate\Support\Facades\Cache::forget('health_results');

            $this->dispatch('success', ['message' => 'Health checks completed successfully']);
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => 'Error running health checks: '.$e->getMessage()]);
        }
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = ! $this->autoRefresh;
    }

    public function getHealthResultsProperty()
    {
        return Cache::remember('health_results', 60, function () {
            return HealthCheckResultHistoryItem::latest()
                ->take(50)
                ->get()
                ->groupBy('check_name');
        });
    }

    public function getOverallStatusProperty()
    {
        $latestResults = HealthCheckResultHistoryItem::select('check_name', 'status')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('health_check_result_history_items')
                    ->groupBy('check_name');
            })
            ->get();

        if ($latestResults->isEmpty()) {
            return 'unknown';
        }

        $hasFailures = $latestResults->contains('status', 'failed');
        $hasWarnings = $latestResults->contains('status', 'warning');

        if ($hasFailures) {
            return 'failed';
        } elseif ($hasWarnings) {
            return 'warning';
        } else {
            return 'ok';
        }
    }

    public function getStatusCountsProperty()
    {
        $latestResults = HealthCheckResultHistoryItem::select('check_name', 'status')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('health_check_result_history_items')
                    ->groupBy('check_name');
            })
            ->get();

        return [
            'ok' => $latestResults->where('status', 'ok')->count(),
            'warning' => $latestResults->where('status', 'warning')->count(),
            'failed' => $latestResults->where('status', 'failed')->count(),
            'total' => $latestResults->count(),
        ];
    }

    public function render()
    {
        return view('livewire.system.health-monitor');
    }
}
