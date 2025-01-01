<?php

namespace App\Livewire\Audit;

use Livewire\Component;
use OwenIt\Auditing\Models\Audit;

class Table extends Component
{
    public $model;

    public $table_id;

    public function modal($model, $table_id)
    {
        $this->model = $model;
        $this->table_id = $table_id;
    }

    public function render()
    {
        $audits = Audit::latest()
            ->where('auditable_type', 'App\\Models\\'.$this->model)
            ->where('auditable_id', $this->table_id)
            ->get();

        return view('livewire.audit.table', compact('audits'));
    }
}
