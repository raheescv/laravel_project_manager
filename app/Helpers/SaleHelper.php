<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\Sale;
use App\Models\SaleDaySession;
use App\Models\SalePayment;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class SaleHelper
{
    public function saleInvoice($id, $method = 'pdf')
    {
        $sale = Sale::find($id);
        if (! $sale) {
            return redirect(route('sale::index'));
        }
        $grand_total = str_pad(intval($sale->grand_total).'00', 6, '0', STR_PAD_LEFT);
        $barcode_string = '229919'.$grand_total;
        $checkDigit = $this->checkDigitFunction($barcode_string);
        $barcode_string .= $checkDigit;

        $thermal_printer_style = Configuration::where('key', 'thermal_printer_style')->value('value') ?? 'with_arabic';
        $gst_no = Configuration::where('key', 'gst_no')->value('value') ?? null;
        $thermal_printer_footer_english = Configuration::where('key', 'thermal_printer_footer_english')->value('value');
        $thermal_printer_footer_arabic = Configuration::where('key', 'thermal_printer_footer_arabic')->value('value');
        $enable_discount_in_print = Configuration::where('key', 'enable_discount_in_print')->value('value');
        $enable_total_quantity_in_print = Configuration::where('key', 'enable_total_quantity_in_print')->value('value');
        $enable_logo_in_print = Configuration::where('key', 'enable_logo_in_print')->value('value');
        $enable_barcode_in_print = Configuration::where('key', 'enable_barcode_in_print')->value('value') ?? 'yes';
        $print_item_label = Configuration::where('key', 'print_item_label')->value('value') ?? 'product';
        $print_quantity_label = Configuration::where('key', 'print_quantity_label')->value('value') ?? 'quantity';
        $barcodeSettings = Configuration::where('key', 'barcode_configurations')->value('value');
        $barcodeSettings = $barcodeSettings ? json_decode($barcodeSettings, true) : [];
        $barcodeType = $barcodeSettings['barcode']['type'] ?? 'C128';
        $payments = $sale->payments()->with('paymentMethod:id,name,alias_name')->get(['amount', 'payment_method_id'])->toArray();
        $data = compact(
            'payments',
            'sale',
            'gst_no',
            'thermal_printer_style',
            'thermal_printer_footer_english',
            'thermal_printer_footer_arabic',
            'enable_discount_in_print',
            'enable_total_quantity_in_print',
            'enable_logo_in_print',
            'enable_barcode_in_print',
            'print_item_label',
            'print_quantity_label',
            'barcode_string',
            'barcodeType',
        );
        $htmlContent = View::make('sale.print', $data)->render();

        return $htmlContent;
    }

    public function convertHtmlToImage($htmlContent, $title)
    {
        $tempHtmlFile = storage_path('app/temp.html');
        File::put($tempHtmlFile, $htmlContent);

        $outputImagePath = public_path("invoices/$title.png");

        $this->generateImageWithHtmlContent($tempHtmlFile, $outputImagePath);

        return $outputImagePath;
    }

    private function generateImageWithHtmlContent($htmlFilePath, $outputImagePath)
    {
        $command = "wkhtmltoimage --format png --width 240 $htmlFilePath $outputImagePath";
        exec($command);
    }

    private function checkDigitFunction($barcode)
    {
        // make sure there is just numbers in $barcode
        $barcode = preg_replace('/[^0-9]/', '', $barcode);
        $vals = str_split($barcode);
        // multiply every other value by 3
        $multiply = false;
        foreach ($vals as $k => $v) {
            $vals[$k] = $multiply ? $v * 3 : $v;
            $vals[$k] = (string) ($vals[$k]);
            $multiply = ! $multiply;
        }
        $mp = array_map(function ($v) {
            return $v;
        }, $vals);
        // adds the values
        $sum = array_sum($mp);
        // gets the mod
        $md = $sum % 10;
        // checks how much for 10
        if ($md != 0) {
            $result = 10 - $md;
        } else {
            $result = $md;
        }

        return $result;
    }

    public function daySessionReport($id)
    {
        $session = SaleDaySession::with(['branch', 'opener', 'closer'])->findOrFail($id);

        $sales = Sale::completed()
            ->where('sale_day_session_id', $id)
            ->with(['branch', 'payments.paymentMethod'])
            ->orderBy('created_at', 'asc')
            ->get();

        $paymentMethods = Account::whereIn('id', cache('payment_methods', []))->get();
        $totals['credit'] = $sales->sum('balance');
        foreach ($paymentMethods as $method) {
            $totals[$method->name] = 0;
        }
        $payments = SalePayment::whereDate('date', date('Y-m-d', strtotime($session->opened_at)))->get();
        $pendingPayments = [];
        foreach ($payments as $payment) {
            if ($payment->sale->sale_day_session_id != $id) {
                $pendingPayments[] = [
                    'date' => $payment->date,
                    'invoice_no' => $payment->sale->invoice_no,
                    'payment_method' => $payment->name,
                    'amount' => $payment->amount,
                ];
            }
            $totals[$payment->name] += $payment->amount;
        }

        return view('sale.day-session-print', compact('session', 'pendingPayments', 'sales', 'totals'));
    }
}
