<?php

namespace App\Livewire\ApiLog;

use App\Models\ApiLog;
use Livewire\Component;

class DetailModal extends Component
{
    public $apiLogId = null;

    public $apiLog = null;

    protected $listeners = [
        'showApiLogDetail' => 'showDetail',
        'closeModal' => 'closeModal',
    ];

    public function showDetail($apiLogId)
    {
        $this->apiLogId = $apiLogId;
        $this->apiLog = ApiLog::find($apiLogId);
    }

    public function closeModal()
    {
        $this->apiLogId = null;
        $this->apiLog = null;
    }

    public function render()
    {
        return view('livewire.api-log.detail-modal');
    }
}
