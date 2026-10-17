<?php

namespace App\Livewire\Settings;

use App\Actions\Product\RenumberBarcodesAction;
use App\Models\UniqueNoCounter;
use App\Support\UniqueNoCounters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class UniqueNoCounterConfiguration extends Component
{
    public array $rows = [];

    /**
     * The barcode series shown in the renumber panel; null while the panel is closed.
     *
     * @var array{count: int, first: ?int, last: ?int}|null
     */
    public ?array $barcodeSeries = null;

    public ?int $barcodeStart = null;

    public function mount(): void
    {
        if (! Auth::user()?->is_super_admin) {
            abort(403, 'Unauthorized access. Only super admin users can access this page.');
        }

        $this->loadRows();
    }

    public function save(): void
    {
        if (! Auth::user()?->is_super_admin) {
            abort(403, 'Unauthorized access. Only super admin users can access this page.');
        }

        $this->validate([
            'rows.*.number' => ['required', 'integer', 'min:0'],
        ]);

        $allowedSegments = UniqueNoCounters::allowedSegments(Auth::user());

        DB::transaction(function () use ($allowedSegments): void {
            foreach ($this->rows as $row) {
                if (! in_array($row['segment'], $allowedSegments, true)) {
                    continue;
                }

                $key = ['year' => $row['year'], 'branch_code' => $row['branch_code'], 'segment' => $row['segment']];
                $updated = UniqueNoCounter::query()->where($key)->update(['number' => (int) $row['number']]);

                if (! $updated && ! UniqueNoCounter::query()->where($key)->exists()) {
                    UniqueNoCounter::query()->create($key + ['number' => (int) $row['number']]);
                }
            }
        });

        $this->dispatch('success', ['message' => 'Unique counter values updated successfully']);
        $this->loadRows();
    }

    public function openBarcodeRenumber(): void
    {
        $this->authorizeBarcodeRenumber();

        $this->barcodeSeries = UniqueNoCounters::barcodeSeries();
        $this->barcodeStart = $this->barcodeSeries['first'] ?? 1;
        $this->resetValidation('barcodeStart');
    }

    public function closeBarcodeRenumber(): void
    {
        $this->barcodeSeries = null;
        $this->barcodeStart = null;
        $this->resetValidation('barcodeStart');
    }

    public function renumberBarcodes(): void
    {
        $this->authorizeBarcodeRenumber();

        $this->validate(
            ['barcodeStart' => ['required', 'integer', 'min:1', 'max:999999999999']],
            ['barcodeStart.required' => 'Enter the number the series should start from.', 'barcodeStart.min' => 'The series must start at 1 or higher.'],
        );

        $response = (new RenumberBarcodesAction())->execute((int) $this->barcodeStart);

        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->dispatch('success', ['message' => $response['message']]);
        $this->closeBarcodeRenumber();
        $this->loadRows();
    }

    public function render()
    {
        return view('livewire.settings.unique-no-counter-configuration');
    }

    private function authorizeBarcodeRenumber(): void
    {
        if (! Auth::user()?->is_super_admin || ! in_array(UniqueNoCounters::BARCODE, UniqueNoCounters::allowedSegments(Auth::user()), true)) {
            abort(403, 'Unauthorized access. Only super admin users can access this page.');
        }
    }

    private function loadRows(): void
    {
        $this->rows = UniqueNoCounters::possibleRows(UniqueNoCounters::allowedSegments(Auth::user()));
    }
}
