<?php

namespace App\Livewire\Trading;

use App\Models\TradingCircuitState;
use App\Models\TradingRiskEvent;
use App\Trading\Brokers\BrokerManager;
use App\Trading\Risk\Rules\KillSwitchRule;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Dashboard extends Component
{
    public bool $killSwitch = false;

    public ?array $killSwitchMeta = null;

    public array $positions = [];

    public array $circuit = [];

    public array $exposure = [];

    public array $thresholds = [];

    public array $severityCounts = [];

    public ?string $severityFilter = null;

    public function mount(): void
    {
        $this->refresh();
    }

    public function toggleKillSwitch(): void
    {
        // TODO(C7): unmapped (candidate: 'flat_trade.trade') — no trading permission in config/permissions.php; kill-switch is a privileged trading control.
        if ($this->killSwitch) {
            KillSwitchRule::disengage();
        } else {
            KillSwitchRule::engage('toggled from risk dashboard');
        }
        $this->refresh();
    }

    public function setSeverityFilter(?string $severity): void
    {
        $this->severityFilter = $severity ?: null;
    }

    public function refresh(): void
    {
        $this->killSwitch = KillSwitchRule::isEngaged();
        $this->killSwitchMeta = $this->killSwitch ? (Cache::get(KillSwitchRule::CACHE_KEY) ?: null) : null;

        try {
            $broker = app(BrokerManager::class)->broker();
            $this->positions = collect($broker->positions())->map->toArray()->all();
        } catch (\Throwable) {
            $this->positions = [];
        }

        $today = now()->toDateString();
        $state = TradingCircuitState::query()->where('trading_day', $today)->first();
        $this->circuit = [
            'tripped' => (bool) ($state?->breaker_tripped),
            'reason' => $state?->trip_reason,
            'tripped_at' => $state?->tripped_at?->format('H:i:s'),
            'realized_pnl' => (float) ($state?->realized_pnl ?? 0),
            'unrealized_pnl' => (float) ($state?->unrealized_pnl ?? 0),
            'trades_count' => (int) ($state?->trades_count ?? 0),
        ];

        $this->exposure = $this->computeExposure();
        $this->thresholds = $this->loadThresholds();
        $this->severityCounts = $this->countEventsBySeverity($today);
    }

    public function render()
    {
        $query = TradingRiskEvent::query()->latest('occurred_at');
        if ($this->severityFilter) {
            $query->where('severity', $this->severityFilter);
        }
        $riskEvents = $query->limit(25)->get();

        return view('livewire.trading.dashboard', [
            'riskEvents' => $riskEvents,
        ]);
    }

    private function computeExposure(): array
    {
        $totalNotional = 0.0;
        $maxNotional = 0.0;
        $unrealized = 0.0;
        foreach ($this->positions as $p) {
            $notional = (float) ($p['ltp'] ?? 0) * (int) ($p['quantity'] ?? 0);
            $totalNotional += $notional;
            $maxNotional = max($maxNotional, $notional);
            $unrealized += (float) ($p['pnl_absolute'] ?? 0);
        }

        return [
            'total_notional' => $totalNotional,
            'max_notional' => $maxNotional,
            'positions_count' => count($this->positions),
            'unrealized_pnl' => $unrealized,
        ];
    }

    private function loadThresholds(): array
    {
        return [
            'max_position_size' => (float) config('trading.risk.max_position_size', 50000),
            'max_concurrent_positions' => (int) config('trading.risk.max_concurrent_positions', 10),
            'max_daily_loss' => (float) config('trading.risk.max_daily_loss', 5000),
            'cooldown_minutes' => (int) config('trading.risk.cooldown_minutes', 15),
        ];
    }

    private function countEventsBySeverity(string $day): array
    {
        $rows = TradingRiskEvent::query()
            ->whereDate('occurred_at', $day)
            ->selectRaw('severity, COUNT(*) as c')
            ->groupBy('severity')
            ->pluck('c', 'severity')
            ->all();

        return [
            'total' => array_sum($rows),
            'breaker' => (int) ($rows['breaker'] ?? 0),
            'blocked' => (int) ($rows['blocked'] ?? 0),
            'warning' => (int) ($rows['warning'] ?? 0),
            'info' => (int) ($rows['info'] ?? 0),
        ];
    }
}
