<?php

namespace App\Livewire\Trading;

use App\Models\TradingAlertRule;
use Livewire\Component;

class AlertRules extends Component
{
    public array $form = [
        'name' => '',
        'event' => 'OrderPlaced',
        'channels' => 'database',
        'severity' => 'info',
        'rate_limit_per_hour' => 60,
    ];

    public function save(): void
    {
        $data = $this->form;
        $data['channels'] = array_filter(array_map('trim', explode(',', $data['channels'])));
        TradingAlertRule::create($data + ['is_active' => true]);
        $this->reset('form');
    }

    public function toggle(int $id): void
    {
        $r = TradingAlertRule::find($id);
        if ($r) {
            $r->is_active = ! $r->is_active;
            $r->save();
        }
    }

    public function delete(int $id): void
    {
        TradingAlertRule::where('id', $id)->delete();
    }

    public function render()
    {
        return view('livewire.trading.alert-rules', [
            'rules' => TradingAlertRule::query()->orderBy('event')->get(),
        ]);
    }
}
