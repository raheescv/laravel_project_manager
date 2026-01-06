<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Journal;

class GeneralVoucherController extends Controller
{
    public function index()
    {
        return view('accounts.general-voucher.index');
    }

    public function print($id)
    {
        $journal = Journal::where('id', $id)
            ->where('source', 'General Voucher')
            ->with(['entries.account', 'createdBy'])
            ->firstOrFail();

        // Determine voucher type based on Payment Methods
        $voucherType = 'general'; // Default
        $paymentMethodIds = cache('payment_methods', []);

        foreach ($journal->entries as $entry) {
            if ($entry->account && in_array($entry->account_id, $paymentMethodIds)) {
                // If Payment Method account is debited, it's a Receipt Voucher (money received)
                if ($entry->debit > 0) {
                    $voucherType = 'receipt';
                    break;
                }
                // If Payment Method account is credited, it's a Payment Voucher (money paid)
                if ($entry->credit > 0) {
                    $voucherType = 'payment';
                    break;
                }
            }
        }
        // Get company configuration
        $companyName = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $companyAddress = Configuration::where('key', 'company_address')->value('value') ?? '';
        $companyPhone = Configuration::where('key', 'company_phone')->value('value') ?? '';
        $companyEmail = Configuration::where('key', 'company_email')->value('value') ?? '';
        $enableLogoInPrint = Configuration::where('key', 'enable_logo_in_print')->value('value') ?? 'yes';
        $companyLogo = cache('logo', asset('assets/img/logo.svg'));

        return view('accounts.general-voucher.print', compact('journal', 'companyName', 'companyAddress', 'companyPhone', 'companyEmail', 'enableLogoInPrint', 'companyLogo', 'voucherType'));
    }
}
