<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\Configuration;

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

        // Get company configuration
        $companyName = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $companyAddress = Configuration::where('key', 'company_address')->value('value') ?? '';
        $companyPhone = Configuration::where('key', 'company_phone')->value('value') ?? '';
        $companyEmail = Configuration::where('key', 'company_email')->value('value') ?? '';
        $enableLogoInPrint = Configuration::where('key', 'enable_logo_in_print')->value('value') ?? 'yes';
        $companyLogo = cache('logo', asset('assets/img/logo.svg'));

        return view('accounts.general-voucher.print', compact('journal', 'companyName', 'companyAddress', 'companyPhone', 'companyEmail', 'enableLogoInPrint', 'companyLogo'));
    }
}
