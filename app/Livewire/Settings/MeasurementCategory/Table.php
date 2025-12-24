<?php

namespace App\Livewire\Settings\MeasurementCategory;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\MeasurementCategory;
use App\Actions\Settings\MeasurementCategory\DeleteAction;
use App\Exports\MeasurementCategoryExport;
use App\Jobs\Export\ExportMeasurementCategoryJob;

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
        'MeasurementCategory-Refresh-Component' => '$refresh',
    ];

    public function delete()
    {
        try {
            DB::beginTransaction();

            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.');
            }

            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute($id);

                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            DB::commit();

            $this->dispatch('success', [
                'message' => 'Successfully Deleted '.count($this->selected).' items'
            ]);

            if (count($this->selected) > 10) {
                $this->resetPage();
            }

            $this->selected = [];
            $this->selectAll = false;

            $this->dispatch('RefreshMeasurementCategoryTable');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($key)
    {
        if (! in_array($key, ['SelectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = MeasurementCategory::latest()
                ->limit(2000)
                ->pluck('id')
                ->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function export()
    {
        $count = MeasurementCategory::count();

        if ($count > 2000) {
            ExportMeasurementCategoryJob::dispatch(Auth::user());

            $this->dispatch('success', [
                'message' => 'You will get your file in your mailbox.'
            ]);
        } else {
            $fileName = 'measurement_category_'.now()->timestamp.'.xlsx';

            return Excel::download(
                new MeasurementCategoryExport(),
                $fileName
            );
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection =
                $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        $data = MeasurementCategory::orderBy(
                $this->sortField,
                $this->sortDirection
            )
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where('name', 'like', '%'.trim($value).'%');
            })
            ->latest()
            ->paginate($this->limit);

        return view('livewire.settings.measurement-category.table', [
            'data' => $data,
        ]);
    }
}
