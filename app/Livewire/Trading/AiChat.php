<?php

namespace App\Livewire\Trading;

use App\Models\TradingAiAnalysis;
use App\Trading\Ai\TradeAnalyst;
use Livewire\Component;

class AiChat extends Component
{
    public string $question = '';

    public ?string $answer = null;

    public function ask(TradeAnalyst $analyst): void
    {
        if (trim($this->question) === '') {
            return;
        }

        $openPositions = [];
        try {
            $broker = app(\App\Trading\Brokers\BrokerManager::class)->broker();
            $openPositions = collect($broker->positions())->map->toArray()->all();
        } catch (\Throwable) {
            // Broker unavailable — chat still works, just without live positions.
        }

        $context = [
            'recent_runs' => \App\Models\TradingStrategyRun::query()
                ->latest('ran_at')->limit(20)->get()->toArray(),
            'open_positions' => $openPositions,
        ];

        $analysis = $analyst->chat($this->question, $context);
        $this->answer = $analysis->response;
    }

    public function render()
    {
        return view('livewire.trading.ai-chat', [
            'history' => TradingAiAnalysis::query()->where('kind', 'chat')->latest()->limit(10)->get(),
        ]);
    }
}
