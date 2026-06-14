<?php

namespace App\Livewire\Trading;

use App\Models\TradingStrategy;
use App\Trading\Strategies\StrategyRegistry;
use Livewire\Component;

class StrategyManager extends Component
{
    public array $registered = [];

    public array $rows = [];

    public function mount(StrategyRegistry $registry): void
    {
        $this->registered = $registry->all()->map(fn ($s) => [
            'code' => $s->code(),
            'name' => $s->name(),
            'defaults' => $s->defaultParameters(),
        ])->all();

        $this->rows = TradingStrategy::query()->orderBy('code')->get()->toArray();
    }

    public function toggle(int $id): void
    {
        // TODO(C7): unmapped (candidate: 'flat_trade.trade') — no trading permission in config/permissions.php.
        $row = TradingStrategy::find($id);
        if ($row) {
            $row->is_active = ! $row->is_active;
            $row->save();
        }
        $this->rows = TradingStrategy::query()->orderBy('code')->get()->toArray();
    }

    public function togglePaper(int $id): void
    {
        // TODO(C7): unmapped (candidate: 'flat_trade.trade') — no trading permission in config/permissions.php.
        $row = TradingStrategy::find($id);
        if ($row) {
            $row->paper_mode = ! $row->paper_mode;
            $row->save();
        }
        $this->rows = TradingStrategy::query()->orderBy('code')->get()->toArray();
    }

    public function bootstrap(): void
    {
        // TODO(C7): unmapped (candidate: 'flat_trade.trade') — no trading permission in config/permissions.php.
        foreach ($this->registered as $r) {
            TradingStrategy::firstOrCreate(
                ['code' => $r['code']],
                ['name' => $r['name'], 'parameters' => $r['defaults'], 'is_active' => false, 'paper_mode' => true]
            );
        }
        $this->rows = TradingStrategy::query()->orderBy('code')->get()->toArray();
    }

    public function render()
    {
        return view('livewire.trading.strategy-manager');
    }
}
