<?php

namespace App\Console\Commands\SingleUse;

use App\Models\JournalEntry;
use Illuminate\Console\Command;
use App\Models\Sale;

class MigrateInvoiceToCustomNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-invoice-to-custom-number';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To Update the Invoice No with the Custom Number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $branchStartingNo = [
            2 => 5000,
            3 => 1000,
        ];
        foreach ($branchStartingNo as $branchId => $startingNo) {
            echo "Branch ID: $branchId, Starting No: $startingNo\n";
            $list = Sale::where('branch_id', $branchId)->where('status', 'completed')->get();
            foreach ($list as $sale) {
                $oldInvoice = $sale->invoice_no;
                $invoiceNo = explode('-', $sale->invoice_no);
                $invoiceNo[3] += $startingNo;
                $newInvoiceNo = implode('-', $invoiceNo);

                $sale->update(['invoice_no' => $newInvoiceNo]);

                $sale->journals()->update(['description' => 'Sale:' . $newInvoiceNo]);

                JournalEntry::where('description', 'Sale:' . $oldInvoice)->update(['description' => 'Sale:' . $newInvoiceNo]);

                echo $newInvoiceNo . "\n";
            }
        }
    }
}
