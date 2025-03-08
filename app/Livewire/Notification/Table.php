<?php

namespace App\Livewire\Notification;

use App\Models\Notification;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $type = '';

    public $unread_only = true;

    public $start_date = '';

    public $end_date = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Notification-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->start_date = date('Y-m-d');
        $this->end_date = date('Y-m-d');
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['SelectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $user = auth()->user();
        $data = $user->notifications()
            ->latest()
            ->when($this->type ?? '', function ($query, $value) {
                return $query->where('type', $value);
            })
            ->when($this->unread_only ?? '', function ($query, $value) {
                return $query->whereNull('read_at');
            })
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where('data', 'LIKE', '%'.$value.'%');
            })
            ->when($this->start_date ?? '', function ($query, $value) {
                return $query->where('created_at', '>=', $value);
            })
            ->when($this->end_date ?? '', function ($query, $value) {
                return $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($value)));
            })
            ->paginate($this->limit);
        $types = Notification::pluck('type', 'type')->toArray();

        return view('livewire.notification.table', [
            'types' => $types,
            'data' => $data,
        ]);
    }
}
