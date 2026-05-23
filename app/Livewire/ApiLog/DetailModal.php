<?php

namespace App\Livewire\ApiLog;

use App\Helpers\Facades\MoqSolutionsHelper;
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

    public function retryApiCall($apiLogId)
    {
        try {
            $apiLog = ApiLog::findOrFail($apiLogId);

            $result = MoqSolutionsHelper::syncDayCloseAmount(json_decode($apiLog->request, true));

            if ($result['success']) {
                $this->dispatch('success', ['message' => 'API call retried successfully']);
            } else {
                $this->dispatch('error', ['message' => 'API call failed: '.$result['message']]);
            }

            $this->apiLog = ApiLog::find($apiLogId);
            $this->dispatch('ApiLog-Refresh-Component');
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => 'Failed to retry API call: '.$e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.api-log.detail-modal');
    }
}
