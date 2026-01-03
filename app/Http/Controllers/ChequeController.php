<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class ChequeController extends Controller
{
    public function index()
    {
        return view('accounts.cheque.index');
    }

    public function print(Request $request, $id = null)
    {
        $settings = Configuration::where('key', 'cheque_configurations')->value('value');
        $settings = json_decode($settings, true) ?? config('cheque_default_configuration');

        $company_name = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $company_address = Configuration::where('key', 'company_address')->value('value') ?? '';

        // Sample cheque data - in real implementation, this would come from a database
        $chequeData = [
            'date' => $request->input('date', date('d-M-Y')),
            'payee' => $request->input('payee', 'Sample Payee Name'),
            'amount' => $request->input('amount', 1000.00),
            'amount_in_words' => $request->input('amount_in_words', $this->numberToWords($request->input('amount', 1000.00))),
            'cheque_number' => $request->input('cheque_number', 'CHQ-'.str_pad(rand(1, 9999), 6, '0', STR_PAD_LEFT)),
            'account_number' => $request->input('account_number', '5002-626450536-14516568'),
            'bank_name' => $request->input('bank_name', 'Bank Name'),
            'signature' => $request->input('signature', ''),
        ];

        $useTemplate = $request->has('use_template')
            ? (bool) $request->input('use_template')
            : (bool) ($settings['use_template'] ?? true);

        $html = view('accounts.cheque.print', compact('settings', 'chequeData', 'company_name', 'company_address', 'useTemplate'))->render();

        $pdf = Browsershot::html($html)
            ->paperSize($settings['width'] ?? 210, $settings['height'] ?? 100)
            ->noSandbox()
            ->setNodeBinary('/usr/local/bin/node')
            ->setNpmBinary('/usr/local/bin/npm')
            ->ignoreHttpsErrors()
            ->disableJavascript()
            ->blockDomains(['*'])
            ->setOption('args', ['--disable-web-security', '--no-sandbox', '--disable-gpu'])
            ->margins(0, 0, 0, 0)
            ->deviceScaleFactor(1)
            ->pdf([
                'printBackground' => true,
                'preferCSSPageSize' => true,
                'scale' => 1,
            ]);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="cheque-'.time().'.pdf"');
    }

    public function view(Request $request)
    {
        $settings = Configuration::where('key', 'cheque_configurations')->value('value');
        $settings = json_decode($settings, true) ?? config('cheque_default_configuration');

        $company_name = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $company_address = Configuration::where('key', 'company_address')->value('value') ?? '';

        // Sample cheque data for preview
        $chequeData = [
            'date' => date('d-M-Y'),
            'payee' => 'Strana Management and Business Consultancy Service',
            'amount' => 1532364.50,
            'amount_in_words' => $this->numberToWords(1532364.50),
            'cheque_number' => 'CHQ-000001',
            'account_number' => '5002-626450536-14516568',
            'bank_name' => 'Bank Name',
            'signature' => '',
        ];

        $useTemplate = (bool) ($settings['use_template'] ?? true);

        return view('accounts.cheque.print', compact('settings', 'chequeData', 'company_name', 'company_address', 'useTemplate'));
    }

    public function configuration()
    {
        return view('accounts.cheque-configuration');
    }

    private function numberToWords($number)
    {
        $ones = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
            14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
            18 => 'Eighteen', 19 => 'Nineteen',
        ];

        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
        ];

        $number = (float) $number;
        $whole = floor($number);
        $fraction = round(($number - $whole) * 100);

        if ($whole == 0) {
            return 'Zero';
        }

        $result = '';

        // Handle millions
        if ($whole >= 1000000) {
            $millions = floor($whole / 1000000);
            $result .= $this->convertHundreds($millions, $ones, $tens).' Million ';
            $whole = $whole % 1000000;
        }

        // Handle thousands
        if ($whole >= 1000) {
            $thousands = floor($whole / 1000);
            $result .= $this->convertHundreds($thousands, $ones, $tens).' Thousand ';
            $whole = $whole % 1000;
        }

        // Handle hundreds
        if ($whole > 0) {
            $result .= $this->convertHundreds($whole, $ones, $tens);
        }

        $result = trim($result);

        // Add fraction (cents/fils)
        if ($fraction > 0) {
            $result .= ' and '.$this->convertHundreds($fraction, $ones, $tens).' Fils';
        } else {
            $result .= ' Only';
        }

        return $result;
    }

    private function convertHundreds($number, $ones, $tens)
    {
        $result = '';

        // Hundreds
        if ($number >= 100) {
            $hundreds = floor($number / 100);
            $result .= $ones[$hundreds].' Hundred ';
            $number = $number % 100;
        }

        // Tens and ones
        if ($number >= 20) {
            $tensPlace = floor($number / 10);
            $result .= $tens[$tensPlace].' ';
            $number = $number % 10;
        } elseif ($number >= 10) {
            $result .= $ones[$number].' ';
            $number = 0;
        }

        // Ones
        if ($number > 0) {
            $result .= $ones[$number].' ';
        }

        return trim($result);
    }
}
