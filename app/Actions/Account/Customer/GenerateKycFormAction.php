<?php

namespace App\Actions\Account\Customer;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\DocumentType;
use App\Models\RentOut;
use App\Services\CompanyLogoResolver;
use App\Traits\UsesBrowsershot;

class GenerateKycFormAction
{
    use UsesBrowsershot;

    /**
     * Render the customer KYC form.
     *
     * Rendered through Browsershot (Chrome), not DomPDF: the form is almost
     * entirely tenant-entered free text — customer name, nationality, address,
     * company details — which in this market is routinely Arabic, and DomPDF
     * renders Arabic unshaped and right-to-left reversed.
     */
    public function execute($customerId, $rentoutId = null)
    {
        $customer = Account::customer()->with('customerType')->findOrFail($customerId);

        $rentout = null;
        if ($rentoutId) {
            $rentout = RentOut::with(['property', 'building', 'group', 'salesman'])
                ->where('account_id', $customerId)
                ->findOrFail($rentoutId);
        }

        $companyInfo = [
            'companyName' => Configuration::where('key', 'company_name')->value('value') ?? config('app.name'),
            'companyAddress' => Configuration::where('key', 'company_address')->value('value') ?? '',
            'companyPhone' => Configuration::where('key', 'company_phone')->value('value') ?? '',
            'companyEmail' => Configuration::where('key', 'company_email')->value('value') ?? '',
        ];

        // KYC document checklist comes from the corresponding rent-out booking's own
        // mandatory document types (which fall back to the tenant default only while the
        // booking has never been configured). Omitted entirely when no booking is linked.
        $documentTypes = collect();
        $submittedDocuments = collect();
        if ($rentout) {
            $documentTypes = DocumentType::whereIn('id', $rentout->mandatoryDocumentTypeIds())
                ->orderBy('name')
                ->get(['id', 'name']);
            // Documents actually uploaded against this booking, grouped by type so the
            // checklist can auto-mark each requirement as submitted / pending.
            $submittedDocuments = $rentout->documents()
                ->get(['id', 'document_type_id', 'name', 'remarks'])
                ->groupBy('document_type_id');
        }

        // Browsershot blocks external requests, so the logo must be embedded.
        $html = view('accounts.customer_kyc', [
            'customer' => $customer,
            'rentout' => $rentout,
            'companyInfo' => $companyInfo,
            'companyLogo' => CompanyLogoResolver::dataUri(),
            'documentTypes' => $documentTypes,
            'submittedDocuments' => $submittedDocuments,
        ])->render();

        // The view sets `@page { margin: 0 }` and pads itself, so keep Chrome's
        // margins at the trait default of zero.
        $pdf = $this->makeBrowsershot($html)
            ->format('A4')
            ->showBackground()
            ->pdf();

        // Names may contain "/" or "\" (eg. "Ahmed / Ali"), which Content-Disposition forbids.
        $customerName = str_replace(['/', '\\'], '-', (string) $customer->name);
        $filename = 'customer_kyc_'.$customerName.'_'.now()->format('Y-m-d').'.pdf';

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }
}
