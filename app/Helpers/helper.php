<?php

use App\Models\Country;
use App\Services\TenantService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
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
 * Human-readable day-relative label for a date (no time): "today", "1 day before", "2 days before", etc.
 *
 * @param  string|\Carbon\Carbon|\DateTimeInterface|null  $date
 * @return string
 */
if (! function_exists('relativeDayLabel')) {
    function relativeDayLabel($date)
    {
        if (! $date) {
            return '';
        }
        $issueDay = Carbon::parse($date)->startOfDay();
        $daysDiff = $issueDay->diffInDays(now()->startOfDay(), false);

        if ($daysDiff == 0) {
            return 'today';
        }
        if ($daysDiff == 1) {
            return '1 day before';
        }
        if ($daysDiff > 1) {
            return $daysDiff.' days before';
        }
        if ($daysDiff == -1) {
            return '1 day after';
        }

        return abs($daysDiff).' days after';
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
if (! function_exists('convert_number_to_words')) {
    function convert_number_to_words($number)
    {
        $hyphen = '-';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'negative ';
        $decimal = ' point ';
        $dictionary = [0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty', 40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety', 100 => 'hundred', 1000 => 'thousand', 1000000 => 'million', 1000000000 => 'billion', 1000000000000 => 'trillion', 1000000000000000 => 'quadrillion', 1000000000000000000 => 'quintillion'];

        if (! is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            trigger_error('convert_number_to_words only accepts numbers between -'.PHP_INT_MAX.' and '.PHP_INT_MAX, E_USER_WARNING);

            return false;
        }

        if ($number < 0) {
            return $negative.convert_number_to_words(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            [$number, $fraction] = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int) ($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen.$dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds].' '.$dictionary[100];
                if ($remainder) {
                    $string .= $conjunction.convert_number_to_words($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = convert_number_to_words($numBaseUnits).' '.$dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= convert_number_to_words($remainder);
                }
                break;
        }

        if ($fraction !== null && is_numeric($fraction)) {
            $string .= $decimal;
            $words = [];
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }
}


if (! function_exists('convertCurrencyToWords')) {
    function convertCurrencyToWords($amount)
    {
        $ones = [
            0 => 'ZERO',
            1 => 'ONE',
            2 => 'TWO',
            3 => 'THREE',
            4 => 'FOUR',
            5 => 'FIVE',
            6 => 'SIX',
            7 => 'SEVEN',
            8 => 'EIGHT',
            9 => 'NINE',
            10 => 'TEN',
            11 => 'ELEVEN',
            12 => 'TWELVE',
            13 => 'THIRTEEN',
            14 => 'FOURTEEN',
            15 => 'FIFTEEN',
            16 => 'SIXTEEN',
            17 => 'SEVENTEEN',
            18 => 'EIGHTEEN',
            19 => 'NINETEEN',
        ];

        $tens = [
            2 => 'TWENTY',
            3 => 'THIRTY',
            4 => 'FORTY',
            5 => 'FIFTY',
            6 => 'SIXTY',
            7 => 'SEVENTY',
            8 => 'EIGHTY',
            9 => 'NINETY',
        ];

        $currency = intval($amount);
        $cents = intval(($amount - $currency) * 100);

        $words = '';

        if ($currency > 0) {
            if ($currency >= 1000000) {
                $millions = intval($currency / 1000000);
                $words .= convertCurrencyToWords($millions).' MILLION ';
                $currency -= $millions * 1000000;
            }
            if ($currency >= 1000) {
                $thousands = intval($currency / 1000);
                $words .= convertCurrencyToWords($thousands).' THOUSAND ';
                $currency -= $thousands * 1000;
            }
            if ($currency >= 100) {
                $hundreds = intval($currency / 100);
                $words .= $ones[$hundreds].' HUNDRED ';
                $currency -= $hundreds * 100;
            }
            if ($currency > 0) {
                if ($currency < 20) {
                    $words .= $ones[$currency];
                } else {
                    $tensDigit = intval($currency / 10);
                    $onesDigit = $currency % 10;
                    $words .= $tens[$tensDigit];
                    if ($onesDigit > 0) {
                        $words .= ' '.$ones[$onesDigit];
                    }
                }
            }
        } else {
            $words .= 'ZERO RIYAL';
        }

        if ($cents > 0) {
            if ($cents < 20) {
                $words .= ' AND '.$ones[$cents];
            } else {
                $tensDigit = intval($cents / 10);
                $onesDigit = $cents % 10;
                $words .= ' AND '.$tens[$tensDigit];
                if ($onesDigit > 0) {
                    $words .= ' '.$ones[$onesDigit];
                }
            }
            $words .= ' DIRHAM';
        }

        return $words;
    }
}

if (! function_exists('ordinal')) {
    function ordinal($number)
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];

        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number.'th';
        }

        return $number.$ends[$number % 10];
    }
}

if (! function_exists('getPercentage')) {
    function getPercentage($value, $total, $decimalPlaces = 2, $returnFormatted = false)
    {
        if ($total == 0) {
            return $returnFormatted ? '0%' : 0;
        }

        $percentage = round(($value / $total) * 100, $decimalPlaces);

        return $returnFormatted ? $percentage.'%' : $percentage;
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
        // Get next unique number from UniqueNoCounter
        $uniqueNumber = getNextUniqueNumber('Barcode');
        // Ensure minimum of 8000 for numeric barcode
        $barcode = (string) max(1, $uniqueNumber);

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
if (! function_exists('barcodePrefix')) {
    function barcodePrefix(): string
    {
        return cache('barcode_prefix', '') ?? '';
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
if (! function_exists('stockCheckStatuses')) {
    function stockCheckStatuses()
    {
        return [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }
}
if (! function_exists('stockCheckItemStatuses')) {
    function stockCheckItemStatuses()
    {
        return [
            'pending' => 'Pending',
            'completed' => 'Completed',
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
if (! function_exists('packageFrequency')) {
    function packageFrequency()
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'bi_weekly' => 'Bi-Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
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
if (! function_exists('generateGrnNo')) {
    function generateGrnNo()
    {
        $branchCode = session('branch_code', 'M');
        $prefix = 'GRN-';

        if ($branchCode) {
            $prefix .= $branchCode.'-';
        }
        $year = now()->format('y');

        $invoicePrefix = $prefix.$year.'-';

        $number = getNextUniqueNumber('Grn');

        // Generate the invoice number
        $invoice = $invoicePrefix.str_pad($number, 4, '0', STR_PAD_LEFT);

        return $invoice;
    }
}
if (! function_exists('getNextTailorOrderNo')) {
    function getNextTailorOrderNo()
    {
        $branchCode = session('branch_code', 'M');
        $prefix = 'TA-';
        $prefix = '';

        if ($branchCode) {
            $prefix .= $branchCode.'-';
        }

        $year = now()->format('y');

        $orderPrefix = $prefix.$year.'-';

        $number = getNextUniqueNumber('TailoringOrder');

        return $orderPrefix.str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
if (! function_exists('getNextUniqueNumber')) {
    function getNextUniqueNumber($segment = 'Sale')
    {
        $branchCode = session('branch_code', 'M');
        $country_id = cache('country_id', Country::QATAR);

        // Get tenant_id from session or TenantService (e.g. when running inside a job)
        $tenantId = session('tenant_id') ?? app(TenantService::class)->getCurrentTenantId();
        if (! $tenantId) {
            throw new \Exception('Tenant ID is required to generate unique number');
        }

        if ($country_id == Country::INDIA) {
            $year = now()->format('y').'/'.now()->addYear()->format('y');
            if (now()->lt(now()->copy()->month(3)->day(31))) {
                $year = now()->subYear()->format('y').'/'.now()->format('y');
            }
        } else {
            $year = now()->format('y');
            if ($segment == 'Barcode') {
                $year = 1;
            }
        }

        DB::statement('SET @out_unique_no = 0;');
        $yearEscaped = DB::getPdo()->quote($year);
        $tenantIdEscaped = DB::getPdo()->quote($tenantId);
        $branchCodeEscaped = DB::getPdo()->quote($branchCode);
        $segmentEscaped = DB::getPdo()->quote($segment);
        DB::statement("CALL getNextUniqueNumber($tenantIdEscaped, $yearEscaped, $branchCodeEscaped, $segmentEscaped, @out_unique_no);");

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

if (! function_exists('tailoringOrderStatuses')) {
    function tailoringOrderStatuses()
    {
        return [
            'pending' => 'Pending',
            'completed' => 'Completed',
        ];
    }
}

if (! function_exists('tailoringOrderDeliveryStatuses')) {
    function tailoringOrderDeliveryStatuses()
    {
        return [
            'not delivered' => 'Not Delivered',
            'partially delivered' => 'Partially Delivered',
            'delivered' => 'Delivered',
        ];
    }
}

if (! function_exists('tailoringOrderItemStatuses')) {
    function tailoringOrderItemStatuses()
    {
        return [
            'pending' => 'Pending',
            'partially completed' => 'Partially Completed',
            'completed' => 'Completed',
            'delivered' => 'Delivered',
        ];
    }
}

if (! function_exists('tailoringOrderItemCompletionStatuses')) {
    function tailoringOrderItemCompletionStatuses()
    {
        return [
            'not completed' => 'Not Completed',
            'partially completed' => 'Partially Completed',
            'completed' => 'Completed',
        ];
    }
}

if (! function_exists('tailoringOrderItemDeliveryStatuses')) {
    function tailoringOrderItemDeliveryStatuses()
    {
        return [
            'not delivered' => 'Not Delivered',
            'partially delivered' => 'Partially Delivered',
            'delivered' => 'Delivered',
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

if (! function_exists('leadSources')) {
    function leadSources(): array
    {
        return [
            'Social Media' => 'Social Media',
            'Facebook' => 'Facebook',
            'Instagram' => 'Instagram',
            'Snapchat' => 'Snapchat',
            'YouTube' => 'YouTube',
            'SMS' => 'SMS',
            'E-mail Campaign' => 'E-mail Campaign',
            'Outdoor Marketing' => 'Outdoor Marketing',
            'Personal' => 'Personal',
            'Walk-In' => 'Walk-In',
            'Local Broker' => 'Local Broker',
            'International Broker' => 'International Broker',
            'Other' => 'Other',
        ];
    }
}

if (! function_exists('leadStatuses')) {
    function leadStatuses(): array
    {
        return [
            'New Lead' => 'New Lead',
            'Follow Up' => 'Follow Up',
            'Interested' => 'Interested',
            'Not Interested' => 'Not Interested',
            'Low Budget' => 'Low Budget',
            'Visit Scheduled' => 'Visit Scheduled',
            'Closed Deal' => 'Closed Deal',
            'Shopping For Info' => 'Shopping For Info',
            'Call Back' => 'Call Back',
            'Same Day Call Back' => 'Same Day Call Back',
            'No Answer' => 'No Answer',
            'Whatsapp Only' => 'Whatsapp Only',
            'Dead Lead' => 'Dead Lead',
            'Rejected' => 'Rejected',
            'Drop' => 'Drop',
            'Follow Up For Visit' => 'Follow Up For Visit',
        ];
    }
}

if (! function_exists('leadTypes')) {
    function leadTypes(): array
    {
        return [
            'Sales' => 'Sales',
            'Rentout' => 'Rentout',
        ];
    }
}

if (! function_exists('propertyLeadLocations')) {
    function propertyLeadLocations(): array
    {
        return [
            'Site' => 'Site',
            'Company Office' => 'Company Office',
            'Outside Location' => 'Outside Location',
        ];
    }
}

if (! function_exists('leadStatusBadgeClass')) {
    function leadStatusBadgeClass(?string $status): string
    {
        return match ($status) {
            'New Lead' => 'bg-primary-subtle text-primary',
            'Follow Up' => 'bg-info-subtle text-info',
            'Interested' => 'bg-success-subtle text-success',
            'Not Interested' => 'bg-secondary-subtle text-secondary',
            'Low Budget' => 'bg-warning-subtle text-warning',
            'Visit Scheduled' => 'bg-success text-white',
            'Closed Deal' => 'bg-success text-white',
            'Shopping For Info' => 'bg-info-subtle text-info',
            'Call Back' => 'bg-warning text-dark',
            'Same Day Call Back' => 'bg-warning-subtle text-warning',
            'No Answer' => 'bg-secondary-subtle text-secondary',
            'Whatsapp Only' => 'bg-success-subtle text-success',
            'Dead Lead' => 'bg-danger-subtle text-danger',
            'Rejected' => 'bg-danger text-white',
            'Drop' => 'bg-secondary text-white',
            'Follow Up For Visit' => 'bg-primary text-white',
            default => 'bg-light text-dark',
        };
    }
}

if (! function_exists('removeSpace')) {
    function removeSpace(?string $value): string
    {
        return str_replace(' ', '', (string) $value);
    }
}

if (! function_exists('enumOptions')) {
    /**
     * Convert a backed enum class to a [value => label] array for html()->select().
     * Expects each case to have a label() method.
     */
    function enumOptions(string $enumClass): array
    {
        return collect($enumClass::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}

if (! function_exists('buildingOwnershipOptions')) {
    function buildingOwnershipOptions(): array
    {
        return enumOptions(\App\Enums\Property\BuildingOwnership::class);
    }
}

if (! function_exists('propertyStatusOptions')) {
    function propertyStatusOptions(): array
    {
        return enumOptions(\App\Enums\Property\PropertyStatus::class);
    }
}

if (! function_exists('rentOutStatusOptions')) {
    function rentOutStatusOptions(): array
    {
        return enumOptions(\App\Enums\RentOut\RentOutStatus::class);
    }
}

if (! function_exists('paymentModeOptions')) {
    function paymentModeOptions(): array
    {
        return enumOptions(\App\Enums\RentOut\PaymentMode::class);
    }
}

if (! function_exists('paymentMethodsOptions')) {
    function paymentMethodsOptions(): array
    {
        return \App\Models\Account::whereIn('id', cache('payment_methods', []))->pluck('name', 'id')->toArray();
    }
}

if (! function_exists('agreementTypeOptions')) {
    function agreementTypeOptions(): array
    {
        return enumOptions(\App\Enums\RentOut\AgreementType::class);
    }
}

if (! function_exists('chequeStatusOptions')) {
    function chequeStatusOptions(): array
    {
        return enumOptions(\App\Enums\RentOut\ChequeStatus::class);
    }
}

if (! function_exists('securityStatusOptions')) {
    function securityStatusOptions(): array
    {
        return enumOptions(\App\Enums\RentOut\SecurityStatus::class);
    }
}

if (! function_exists('securityTypeOptions')) {
    function securityTypeOptions(): array
    {
        return enumOptions(\App\Enums\RentOut\SecurityType::class);
    }
}

if (! function_exists('paymentTermLabels')) {
    function paymentTermLabels(): array
    {
        return [
            'rent payment' => 'Rent Payment',
            'installment' => 'Installment',
            'down payment' => 'Down Payment',
            'handover payment' => 'Handover Payment',
            'balloon payment' => 'Balloon Payment',
            'booking amount' => 'Booking Amount',
            'registration' => 'Registration',
            'maintenance' => 'Maintenance',
            'other' => 'Other',
        ];
    }
}

if (! function_exists('extract403Details')) {
    // Helper to extract 403 permission details from request context
    function extract403Details(string $message, \Illuminate\Http\Request $request): array
    {
        $user = $request->user();
        $route = $request->route();

        $action = $route?->getActionMethod();
        $controllerClass = $route?->getControllerClass();
        $resource = $controllerClass ? class_basename($controllerClass) : null;
        $resourceName = $resource ? str_replace('Controller', '', $resource) : null;

        // Try to resolve the permission name
        $permission = null;
        $isGeneric = in_array($message, ['This action is unauthorized.', ''], true);

        if (! $isGeneric && $message) {
            // abort(403, 'custom message') — use the message directly
            $permission = $message;
        } elseif ($resourceName && $action) {
            // Policy-based — reconstruct from resource + action
            // Convert PascalCase to snake_case with spaces: LocalPurchaseOrder → local purchase order
            $readable = strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $resourceName));
            $permAction = $action;
            $permission = "{$readable}.{$permAction}";
        }

        return [
            'permission' => $permission,
            'action' => $action,
            'resource' => $resourceName,
            'url' => $request->path(),
            'user_role' => $user?->getRoleNames()?->implode(', '),
        ];
    }
}
