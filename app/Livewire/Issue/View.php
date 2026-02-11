<?php

namespace App\Livewire\Issue;

use App\Models\Issue;
use Livewire\Component;

class View extends Component
{
    public ?int $table_id = null;

    public ?Issue $model = null;

    public function mount(?int $table_id = null): void
    {
        $this->table_id = $table_id;
        $this->model = Issue::with([
            'account:id,name,mobile',
            'items' => fn ($q) => $q->with('product:id,name,code')->orderBy('date', 'asc'),
        ])->find($this->table_id);
        if (! $this->model) {
            $this->redirect(route('issue::index'));
        }
    }

    public function render()
    {
        return view('livewire.issue.view');
    }
}
