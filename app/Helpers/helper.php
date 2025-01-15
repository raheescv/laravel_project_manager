<?php

use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

if (! function_exists('writeToEnv')) {
    function writeToEnv($key, $value)
    {
        $path = base_path('.env');
        if (File::exists($path)) {
            $envContents = File::get($path);
            $keyPattern = '/^'.preg_quote($key).'=.*/m';
            if (preg_match($keyPattern, $envContents)) {
                $envContents = preg_replace($keyPattern, $key.'='.$value, $envContents);
            } else {
                $envContents .= PHP_EOL.$key.'='.$value;
            }
            File::put($path, $envContents);

            return true;
        }

        return false;
    }
}

if (! function_exists('resolutionOptions')) {
    function resolutionOptions()
    {
        return [
            '5S' => '5 seconds',
            '10S' => '10 seconds',
            '15S' => '15 seconds',
            '30S' => '30 seconds',
            '45S' => '45 seconds',
            '1' => '1 minute',
            '2' => '2 minute',
            '3' => '3 minute',
            '5' => '5 minute',
            '10' => '10 minute',
            '15' => '15 minute',
            '20' => '20 minute',
            '30' => '30 minute',
            '60' => '60 minute',
            '120' => '120 minute',
            '240' => '240 minute',
        ];
    }
}

if (! function_exists('orderStatus')) {
    function orderStatus($key)
    {
        $statues = [
            1 => 'Canceled',
            2 => 'Traded / Filled',
            3 => '(Not used currently)',
            4 => 'Transit',
            5 => 'Rejected',
            6 => 'Pending',
            7 => 'Expired',
        ];

        return $statues[$key];
    }
}

if (! function_exists('orderSegments')) {
    function orderSegments($key)
    {
        $statues = [
            10 => 'E (Equity)',
            11 => 'D (F&O)',
            12 => 'C (Currency)',
            20 => 'M (Commodity)',
        ];

        return $statues[$key];
    }
}

if (! function_exists('systemDate')) {
    function systemDate($value)
    {
        if ($value) {
            return date('d-m-Y', strtotime($value));
        } else {
            return $value;
        }
    }
}

if (! function_exists('systemDateTime')) {
    function systemDateTime($value)
    {
        if ($value) {
            return date('d-m-Y h:i:s A', strtotime($value));
        } else {
            return $value;
        }
    }
}
if (! function_exists('currency')) {
    function currency($value, $decimal_count = 2)
    {
        if (is_numeric($value)) {
            return number_format($value, $decimal_count);
        } else {
            return number_format(0, $decimal_count);
        }
    }
}
if (! function_exists('orderTypes')) {
    function orderTypes($key)
    {
        $statues = [
            1 => 'Limit Order',
            2 => 'Market Order',
            3 => 'Stop Order (SL-M)',
            4 => 'Stoplimit Order (SL-L)',
        ];

        return $statues[$key];
    }
}

if (! function_exists('validationHelper')) {
    function validationHelper($rules, $data)
    {
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }
}

if (! function_exists('getUserRoles')) {
    function getUserRoles($user)
    {
        return implode(',', $user->getRoleNames()->toArray());
    }
}
if (! function_exists('fileUpload')) {
    function fileUpload($file, $path)
    {
        $fileName = time().'.'.$file->extension();
        $disk = Storage::disk('public');
        if (! File::exists($path)) {
            File::makeDirectory($path, $mode = 777, true, true);
        }
        $disk->putFileAs($path, $file, $fileName);
        $uploaded_path = $disk->url($path.$fileName);

        return [
            'file_name' => $fileName,
            'uploaded_path' => $uploaded_path,
        ];
    }
}

if (! function_exists('generateBarcode')) {
    function generateBarcode()
    {
        $i = 0;
        do {
            $barcode = '9900' + Inventory::count() + $i;
            $i++;
            $exists = Inventory::where('barcode', $barcode)->exists();
        } while ($exists);

        return $barcode;
    }
}

if (! function_exists('sortDirection')) {
    function sortDirection($direction)
    {
        if ($direction === 'asc') {
            return '&uarr;';
        } else {
            return '&darr;';
        }
    }
}

if (! function_exists('barcodeTypes')) {
    function barcodeTypes()
    {
        return [
            'product_wise' => 'Product Wise',
            'system_generation' => 'System Generation',
        ];
    }
}
if (! function_exists('accountTypes')) {
    function accountTypes()
    {
        return [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'income' => 'Income',
            'expense' => 'Expense',
            'equity' => 'Equity',
        ];
    }
}

if (! function_exists('saleStatuses')) {
    function saleStatuses()
    {
        return [
            'draft' => 'Draft',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }
}

if (! function_exists('saleTypes')) {
    function saleTypes()
    {
        return [
            'version_1' => 'Version 1',
            'pos' => 'POS',
        ];
    }
}
if (! function_exists('activeOrDisabled')) {
    function activeOrDisabled()
    {
        return [
            'active' => 'Active',
            'disabled' => 'Disabled',
        ];
    }
}

if (! function_exists('thermalPrinterStyle')) {
    function thermalPrinterStyle()
    {
        return [
            'with_arabic' => 'With Arabic',
            'english_only' => 'English Only',
        ];
    }
}
if (! function_exists('getNextSaleInvoiceNo')) {
    function getNextSaleInvoiceNo()
    {
        $prefix = 'INV-';
        $year = now()->format('Y');
        $lastInvoice = DB::table('sales')->whereYear('created_at', $year)->max('invoice_no');
        $lastSequence = $lastInvoice ? (int) str_replace($prefix.$year.'-', '', $lastInvoice) : 0;
        do {
            $newSequence = $lastSequence + 1;
            $invoice = $prefix.$year.'-'.str_pad($newSequence, 5, '0', STR_PAD_LEFT);
            $exists = DB::table('sales')->where('invoice_no', $invoice)->exists();
        } while ($exists);

        return $invoice;
    }
}

if (! function_exists('TableView')) {
    function TableView($data)
    {
        echo '<table border="1" style="background: white;">';
        echo '<thead>';
        if (isset($data[0])) {
            echo '<tr>';
            foreach ($data[0] as $key => $value) {
                echo "<td>$key</td>";
            }
            echo '</tr>';
        } else {
            foreach ($data as $key => $single) {
                echo '<tr>';
                foreach ($single as $key => $value) {
                    echo "<td>$key</td>";
                }
                echo '</tr>';
                break;
            }
        }
        echo '</thead>';
        echo '<tbody>';
        foreach ($data as $single) {
            echo '<tr>';
            foreach ((array) $single as $key => $value) {
                echo "<td align='right'>".$single[$key] ?? $single->key.'</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }
}
