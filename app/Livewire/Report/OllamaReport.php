<?php

namespace App\Livewire\Report;

use App\Exports\OllamaReportExport;
use App\Helpers\Facades\OllamaHelper;
use App\Models\SaleItem;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class OllamaReport extends Component
{
    use WithPagination;

    public $search = '';

    public $from_date;

    public $to_date;

    public $limit = 10;

    public $prompt = '';

    public $report_data = [];

    public $analysis = '';

    public $sortField = 'date';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
    }

    public function generateReport()
    {
        try {
            // Get base data from models
            $query = SaleItem::with(['sale:id,account_id,date,invoice_no', 'product:id,name'])
                ->select(
                    'sale_items.sale_id',
                    'sales.account_id',
                    'sales.date',
                    'sales.invoice_no',
                    'sale_items.product_id',
                    'sale_items.unit_price',
                    'sale_items.quantity',
                    'sale_items.discount',
                    'sale_items.tax',
                    'sale_items.total'
                )
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->when($this->from_date, fn ($q) => $q->whereDate('sales.date', '>=', $this->from_date))
                ->when($this->to_date, fn ($q) => $q->whereDate('sales.date', '<=', $this->to_date));

            $data = $query->limit(2);
            $data = $query->get();

            // Format data for Ollama prompt
            $formatted_data = $data->map(function ($item) {
                return [
                    'customer' => $item->sale?->account?->name,
                    'mobile' => $item->sale?->account?->mobile,
                    'date' => $item->sale?->date,
                    'invoice' => $item->sale?->invoice_no,
                    'product' => $item->product->name,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'discount' => $item->discount,
                    'tax' => $item->tax,
                    'total' => $item->total,
                ];
            });
            $this->report_data = $formatted_data;
            // Generate analysis using Ollama
            $this->analysis = OllamaHelper::generateReport($formatted_data, $this->prompt);

            session()->flash('message', 'Report generated successfully!');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to generate report: '.$e->getMessage());
        }
    }

    public function export()
    {
        $exportFileName = 'OllamaReport_'.now()->timestamp.'.xlsx';

        return Excel::download(new OllamaReportExport($this->report_data), $exportFileName);
    }

    public function render()
    {
        return view('livewire.report.ollama-report', [
            'data' => $this->report_data,
            'analysis' => $this->analysis,
        ]);
    }
}
