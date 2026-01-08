<?php

namespace App\Livewire\Settings\MeasurementSubCategory;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

use App\Models\MeasurementSubCategory;
use App\Actions\Settings\MeasurementSubCategory\DeleteAction;

class Table extends Component
{
    use WithPagination;

    public $search = '';
    public $limit = 10;
    public $selected = [];
    public $selectAll = false;
    public $sortField = 'id';
    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'MeasurementSubCategory-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        try {
            DB::beginTransaction();

            if (!count($this->selected)) {
                throw new \Exception('Please select any item to delete.');
            }

            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute($id);

                if (!$response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            DB::commit();

            $this->dispatch('success', [
                'message' => 'Successfully deleted '.count($this->selected).' items'
            ]);

            $this->selected = [];
            $this->selectAll = false;

            $this->dispatch('MeasurementSubCategory-Refresh-Component');

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = MeasurementSubCategory::pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function render()
    {
        $data = MeasurementSubCategory::with('category')
            ->orderBy($this->sortField, $this->sortDirection)
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%'.trim($this->search).'%')
                  ->orWhereHas('category', function ($q2) {
                      $q2->where('name', 'like', '%'.trim($this->search).'%');
                  });
            })
            ->paginate($this->limit);

        return view('livewire.settings.measurement-sub-category.table', [
            'data' => $data,
        ]);
    }
}
