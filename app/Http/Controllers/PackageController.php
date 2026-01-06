<?php

namespace App\Http\Controllers;

use App\Actions\Package\GeneratePackageStatementAction;
use App\Models\Configuration;
use App\Models\PackagePayment;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        return view('package.index');
    }

    public function create()
    {
        return view('package.page', ['id' => null]);
    }

    public function edit($id)
    {
        return view('package.page', ['id' => $id]);
    }

    public function statement($id, Request $request)
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        return (new GeneratePackageStatementAction())->execute($id, $fromDate, $toDate);
    }

    public function paymentPrint($id)
    {
        $payment = PackagePayment::with(['package.account', 'package.packageCategory', 'paymentMethod'])->findOrFail($id);
        // Get company configuration
        $companyName = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $companyAddress = Configuration::where('key', 'company_address')->value('value') ?? '';
        $companyPhone = Configuration::where('key', 'company_phone')->value('value') ?? '';
        $companyEmail = Configuration::where('key', 'company_email')->value('value') ?? '';
        $enableLogoInPrint = Configuration::where('key', 'enable_logo_in_print')->value('value') ?? 'yes';
        $companyLogo = cache('logo', asset('assets/img/logo.svg'));

        return view('package.payment-print', compact('payment', 'companyName', 'companyAddress', 'companyPhone', 'companyEmail', 'enableLogoInPrint', 'companyLogo'));
    }
}
