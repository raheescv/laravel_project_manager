<?php

namespace App\Livewire\Sale;

use Livewire\Component;

class Feedback extends Component
{
    protected $listeners = [
        'Open-Sale-Feedback-Component' => 'open',
    ];

    public $sales = [];

    public function open($sales)
    {
        $this->mount($sales);
        $this->dispatch('ToggleSaleFeedbackModal');
    }

    public function mount($sales = [])
    {
        if (! $sales) {
            $this->sales = [
                'rating' => 0,
                'feedback_type' => 'compliment',
                'feedback' => '',
            ];
        } else {
            $this->sales = $sales;
        }
    }

    public function save()
    {
        $this->dispatch('Save-Sale-Feedback', $this->sales);
        $this->dispatch('ToggleSaleFeedbackModal');
    }

    public function render()
    {
        return view('livewire.sale.feedback');
    }
}
