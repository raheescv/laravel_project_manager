<?php

namespace App\Livewire\ApiLog;

use App\Helpers\Facades\MoqSolutionsHelper;
use Livewire\Component;

class MOQSettings extends Component
{
    public $config;

    // Test API form fields
    public $test_amount = 100.0;

    public $test_date;

    public $test_outlet;

    // Connection status
    public $connectionStatus = '';

    public $connectionMessage = '';

    public $connectionSuccess = false;

    // Sync result
    public $syncResult = '';

    public $syncSuccess = false;

    public $syncMessage = '';

    protected $rules = [
        'test_amount' => 'required|numeric|min:0',
        'test_date' => 'required|date',
        'test_outlet' => 'nullable|integer',
    ];

    public function mount()
    {
        $this->config = MoqSolutionsHelper::getConfig();

        $this->config['endpoint'] = $this->config['endpoint'] ?? '';
        $this->config['endpoint_sandbox'] = $this->config['endpoint_sandbox'] ?? '';
        $this->config['username'] = $this->config['username'] ?? '';
        $this->config['token'] = $this->config['token'] ?? '';
        $this->config['date'] = date('Y-m-d');
        $this->test_date = date('Y-m-d');
        $this->test_outlet = $this->config['outlet_name'] ?? '';
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function testConnection()
    {
        try {
            $result = MoqSolutionsHelper::testConnection();

            if ($result['success']) {
                $this->connectionSuccess = true;
                $this->connectionStatus = 'success';
                $this->connectionMessage = "Connection successful (Status Code: {$result['status_code']})";
            } else {
                $this->connectionSuccess = false;
                $this->connectionStatus = 'failed';
                $this->connectionMessage = $result['message'];
            }
        } catch (\Exception $e) {
            $this->connectionSuccess = false;
            $this->connectionStatus = 'failed';
            $this->connectionMessage = 'Connection test failed: '.$e->getMessage();
        }
    }

    public function syncDayClose()
    {
        $this->validate([
            'test_amount' => 'required|numeric|min:0',
            'test_date' => 'required|date',
            'test_outlet' => 'required|string',
        ]);

        try {
            $syncData = [
                'Date' => $this->test_date,
                'Revenue' => $this->test_amount,
                'Outlet' => $this->test_outlet,
            ];

            $result = MoqSolutionsHelper::syncDayCloseAmount($syncData);

            if ($result['success']) {
                $this->syncSuccess = true;
                $this->syncResult = 'success';
                $this->syncMessage = $result['message'].' (API Log ID: '.$result['api_log_id'].')';
                $this->dispatch('success', ['message' => $result['message']]);
            } else {
                $this->syncSuccess = false;
                $this->syncResult = 'failed';
                $this->syncMessage = $result['message'];
                if (isset($result['error'])) {
                    $this->syncMessage .= ' - Error: '.$result['error'];
                }
                $this->dispatch('error', ['message' => $result['message']]);
            }
        } catch (\Exception $e) {
            $this->syncSuccess = false;
            $this->syncResult = 'failed';
            $this->syncMessage = 'Failed to sync dayClose amount: '.$e->getMessage();
            $this->dispatch('error', ['message' => 'Failed to sync dayClose amount: '.$e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.api-log.m-o-q-settings');
    }
}
