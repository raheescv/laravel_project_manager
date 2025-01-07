<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 10;

    public $filter = 'date-created';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    protected $listeners = [
        'User-Refresh-Component' => '$refresh',
    ];

    protected $paginationTheme = 'bootstrap';

    public function updated($key, $value)
    {
        $this->resetPage();
    }

    public function updatedFilter()
    {
        switch ($this->filter) {
            case 'date-created':
                $this->sortField = 'created_at';
                $this->sortDirection = 'asc';
                break;
            case 'date-modified':
                $this->sortField = 'updated_at';
                $this->sortDirection = 'asc';
                break;
            case 'alphabetically':
                $this->sortField = 'name';
                $this->sortDirection = 'asc';
                break;
            case 'alphabetically-reversed':
                $this->sortField = 'name';
                $this->sortDirection = 'desc';
                break;
            default:
                $this->sortField = 'id';
                $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $data = User::orderBy($this->sortField, $this->sortDirection)
            ->when($this->search ?? '', function ($query, $value) {
                $query->where('name', 'like', "%{$value}%");
            })
            ->latest()
            ->where('type', 'user')
            ->paginate($this->limit);

        return view('livewire.user.table', compact('data'));
    }
}
