<?php

namespace App\Livewire\ApiLog;

use App\Helpers\Facades\MoqSolutionsHelper;
use App\Models\ApiLog;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public $endpoint = '';

    public $from_date = '';

    public $to_date = '';

    public $limit = 10;

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'ApiLog-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
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

            $this->dispatch('ApiLog-Refresh-Component');
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => 'Failed to retry API call: '.$e->getMessage()]);
        }
    }

    public function render()
    {
        $data = ApiLog::orderBy($this->sortField, $this->sortDirection)
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('endpoint', 'like', "%{$value}%")
                        ->orWhere('method', 'like', "%{$value}%")
                        ->orWhere('status', 'like', "%{$value}%")
                        ->orWhere('username', 'like', "%{$value}%")
                        ->orWhere('user_name', 'like', "%{$value}%")
                        ->orWhere('description', 'like', "%{$value}%");
                });
            })
            ->when($this->status ?? '', function ($query, $value) {
                return $query->where('status', $value);
            })
            ->when($this->endpoint ?? '', function ($query, $value) {
                return $query->where('endpoint', 'like', "%{$value}%");
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                return $query->where('created_at', '>=', $value.' 00:00:00');
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                return $query->where('created_at', '<=', $value.' 23:59:59');
            })
            ->latest()
            ->paginate($this->limit);

        return view('livewire.api-log.table', [
            'data' => $data,
        ]);
    }
}
