<?php

use App\Models\Country;
use App\Models\Product;
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

if (! function_exists('excelDateConversion')) {
    function excelDateConversion($excelDate)
    {
        $unixDate = ($excelDate - 25569) * 86400;

        return gmdate('Y-m-d', $unixDate);
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

if (! function_exists('systemTime')) {
    function systemTime($value)
    {
        if ($value) {
            return date('h:i:s A', strtotime($value));
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

/**
 * Extract numeric value from string
 *
 * @param  mixed  $value  The value to extract numeric from
 * @param  float  $default  Default value if extraction fails
 * @return float
 */
if (! function_exists('extractNumericValue')) {
    function extractNumericValue($value, $default = 0)
    {
        // If already numeric, return as float
        if (is_numeric($value)) {
            return (float) $value;
        }

        // If not a string, return default
        if (! is_string($value)) {
            return $default;
        }

        // Remove common currency symbols and text
        $cleaned = preg_replace('/[^\d.,\-]/', '', $value);

        // Handle different decimal separators
        if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
            // Both comma and dot present - assume comma is thousands separator
            $cleaned = str_replace(',', '', $cleaned);
        } elseif (strpos($cleaned, ',') !== false) {
            // Only comma - check if it's decimal separator
            $parts = explode(',', $cleaned);
            if (count($parts) == 2 && strlen($parts[1]) <= 2) {
                // Likely decimal separator
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                // Likely thousands separator
                $cleaned = str_replace(',', '', $cleaned);
            }
        }

        // Extract the first number found
        if (preg_match('/-?\d+\.?\d*/', $cleaned, $matches)) {
            return (float) $matches[0];
        }

        return $default;
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
    function validationHelper($rules, $data, $tableName = null)
    {
        $messages = [];

        if ($tableName) {
            $messages['name.required'] = "The {$tableName} name field is required.";
            $messages['name.unique'] = "The {$tableName} name has already been taken.";
        }

        $validator = Validator::make($data, $rules, $messages);
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
        $relativePath = rtrim($path, '/').'/'.$fileName;
        $uploaded_path = Storage::url($relativePath);

        return [
            'file_name' => $fileName,
            'uploaded_path' => $uploaded_path,
        ];
    }
}

if (! function_exists('generateBarcode')) {
    function generateBarcode()
    {
        $maxBarcode = Product::max('barcode') ?? '0';
        $barcode = 0;
        // If maxBarcode is purely numeric, handle as before
        if (is_numeric($maxBarcode)) {
            $numericPart = (int) $maxBarcode;
            // Ensure we start from at least 8000
            if ($numericPart < 8000) {
                $numericPart = 8000;
            }
            $barcode = $numericPart + 1;
        } else {
            // Extract prefix and numeric part (e.g., "TFQ01" -> prefix: "TFQ", number: "01")
            if (preg_match('/^([^0-9]*)(\d+)$/', $maxBarcode, $matches)) {
                $prefix = $matches[1];
                $numericPart = (int) $matches[2];
                $paddingLength = strlen($matches[2]); // Preserve original padding length

                // Increment the numeric part
                $numericPart++;

                // Reconstruct barcode with same prefix and padding
                $barcode = $prefix.str_pad($numericPart, $paddingLength, '0', STR_PAD_LEFT);
            }
        }

        // Ensure uniqueness
        while (Product::where('barcode', $barcode)->exists()) {
            if (is_numeric($barcode)) {
                $barcode = (int) $barcode + 1;
            } else {
                // Extract and increment numeric part
                if (preg_match('/^([^0-9]*)(\d+)$/', $barcode, $matches)) {
                    $prefix = $matches[1];
                    $numericPart = (int) $matches[2] + 1;
                    $paddingLength = strlen($matches[2]);
                    $barcode = $prefix.str_pad($numericPart, $paddingLength, '0', STR_PAD_LEFT);
                } else {
                    $barcode = (int) $barcode + 1;
                }
            }
        }

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
if (! function_exists('appointmentStatuses')) {
    function appointmentStatuses()
    {
        return [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no response' => 'No Response',
        ];
    }
}
if (! function_exists('noteTypes')) {
    function noteTypes()
    {
        return [
            'general' => 'General',
            'appointment' => 'Appointment',
            'payment' => 'Payment',
            'complaint' => 'Complaint',
            'followup' => 'Follow Up',
        ];
    }
}
if (! function_exists('saleReturnStatuses')) {
    function saleReturnStatuses()
    {
        return [
            'draft' => 'Draft',
            'completed' => 'Completed',
        ];
    }
}

if (! function_exists('purchaseStatuses')) {
    function purchaseStatuses()
    {
        return [
            'draft' => 'Draft',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }
}
if (! function_exists('purchaseReturnStatuses')) {
    function purchaseReturnStatuses()
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
            'pos' => 'POS',
            'version_1' => 'Version 1',
            'version_2' => 'Version 2',
        ];
    }
}

if (! function_exists('priceTypes')) {
    function priceTypes()
    {
        return [
            'normal' => 'Normal',
            'home_service' => 'Home Service',
            'offer' => 'Offer',
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
        $branchCode = session('branch_code', 'M');
        $prefix = 'INV-';

        if ($branchCode) {
            $prefix .= $branchCode.'-';
        }

        $country_id = cache('country_id', Country::QATAR);

        if ($country_id == Country::INDIA) {
            $year = now()->format('y').'/'.now()->addYear()->format('y');
            if (now()->lt(now()->copy()->month(3)->day(31))) {
                $year = now()->subYear()->format('y').'/'.now()->format('y');
            }
        } else {
            $year = now()->format('y');
        }

        $invoicePrefix = $prefix.$year.'-';

        $number = getNextUniqueNumber('Sale');

        // Generate the invoice number
        $invoice = $invoicePrefix.str_pad($number, 4, '0', STR_PAD_LEFT);

        return $invoice;
    }
}
if (! function_exists('getNextUniqueNumber')) {
    function getNextUniqueNumber($segment = 'Sale')
    {
        $branchCode = session('branch_code', 'M');
        $country_id = cache('country_id', Country::QATAR);

        if ($country_id == Country::INDIA) {
            $year = now()->format('y').'/'.now()->addYear()->format('y');
            if (now()->lt(now()->copy()->month(3)->day(31))) {
                $year = now()->subYear()->format('y').'/'.now()->format('y');
            }
        } else {
            $year = now()->format('y');
        }

        DB::statement('SET @out_unique_no = 0;');
        $yearEscaped = DB::getPdo()->quote($year);
        $branchCodeEscaped = DB::getPdo()->quote($branchCode);
        $segmentEscaped = DB::getPdo()->quote($segment);
        DB::statement("CALL getNextUniqueNumber($yearEscaped, $branchCodeEscaped, $segmentEscaped, @out_unique_no);");

        $result = DB::select('SELECT @out_unique_no as unique_no');

        return $result[0]->unique_no;
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

if (! function_exists('pendingCompletedStatuses')) {
    function pendingCompletedStatuses()
    {
        return [
            'pending' => 'pending',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
        ];
    }
}

if (! function_exists('feedbackTypes')) {
    function feedbackTypes()
    {
        return [
            'compliment' => 'Compliment',
            'suggestion' => 'Suggestion',
            'complaint' => 'Complaint',
        ];
    }
}

if (! function_exists('packageItemStatuses')) {
    function packageItemStatuses()
    {
        return [
            'pending' => 'Pending',
            'visited' => 'Visited',
            'rescheduled' => 'Rescheduled',
        ];
    }
}
if (! function_exists('packageStatuses')) {
    function packageStatuses()
    {
        return [
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }
}

if (! function_exists('arabicNumber')) {
    function arabicNumber($value)
    {
        $arabicNumerals = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        return strtr(number_format($value, 2), array_combine(range(0, 9), $arabicNumerals));
    }
}

if (! function_exists('https_asset')) {
    function https_asset($path)
    {
        $url = asset($path);

        // If production → force HTTPS
        if (in_array(config('app.env'), ['production', 'staging'])) {
            return str_replace('http://', 'https://', $url);
        } else {
            // Otherwise (local/staging/dev) → keep HTTP
            return str_replace('https://', 'http://', $url);
        }
    }
}
