<?php

namespace App\Livewire\Trading;

use App\Models\TradingAlert;
use App\Models\TradingCircuitState;
use App\Models\TradingCommandRun;
use App\Models\TradingPaperOrder;
use App\Models\TradingRiskEvent;
use App\Models\TradingStrategyRun;
use App\Trading\Brokers\BrokerManager;
use App\Trading\Risk\Rules\KillSwitchRule;
use Livewire\Component;

class Dashboard extends Component
{
    public bool $killSwitch = false;

    public array $positions = [];

    public array $today = [];

    public function mount(): void
    {
        $this->killSwitch = KillSwitchRule::isEngaged();
        $this->refresh();
    }

    public function toggleKillSwitch(): void
    {
        if ($this->killSwitch) {
            KillSwitchRule::disengage();
        } else {
            KillSwitchRule::engage('toggled from dashboard');
        }
        $this->killSwitch = KillSwitchRule::isEngaged();
    }

    public function refresh(): void
    {
        try {
            $broker = app(BrokerManager::class)->broker();
            $this->positions = collect($broker->positions())->map->toArray()->all();
        } catch (\Throwable) {
            $this->positions = [];
        }

        $today = now()->toDateString();
        // Store only scalars — Livewire serializes public arrays between
        // requests and Eloquent models inside arrays are fragile.
        $circuit = TradingCircuitState::query()->where('trading_day', $today)->first();
        $this->today = [
            'runs' => TradingCommandRun::query()->whereDate('started_at', $today)->count(),
            'placed' => TradingStrategyRun::query()->whereDate('ran_at', $today)->where('outcome', 'placed')->count(),
            'rejected' => TradingStrategyRun::query()->whereDate('ran_at', $today)->where('outcome', 'rejected')->count(),
            'risk_events' => TradingRiskEvent::query()->whereDate('occurred_at', $today)->count(),
            'paper_open' => TradingPaperOrder::query()->where('status', 'OPEN')->count(),
            'circuit_tripped' => (bool) ($circuit?->breaker_tripped),
            'circuit_reason' => $circuit?->trip_reason,
            'circuit_tripped_at' => $circuit?->tripped_at?->format('H:i'),
        ];
    }

    public function render()
    {
        $recentRuns = TradingCommandRun::query()->latest('started_at')->limit(15)->get();
        $recentAlerts = TradingAlert::query()->latest()->limit(10)->get();
        $recentRisk = TradingRiskEvent::query()->latest('occurred_at')->limit(10)->get();

        return view('livewire.trading.dashboard', [
            'recentRuns' => $recentRuns,
            'recentAlerts' => $recentAlerts,
            'recentRisk' => $recentRisk,
        ]);
    }
}
