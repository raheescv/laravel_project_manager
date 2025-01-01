<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Component;

class Table extends Component
{
    public $search = '';

    public $limit = 10;

    public $filter = 'date-created';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public function mount() {}

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
            ->paginate($this->limit);

        return view('livewire.user.table', compact('data'));
    }
}
