<?php

namespace App\Actions\Account\Customer;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\DocumentType;
use App\Models\RentOut;
use App\Services\CompanyLogoResolver;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateKycFormAction
{
    public function execute($customerId, $rentoutId = null)
    {
        $customer = Account::customer() ->with('customerType') ->findOrFail($customerId);

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

        $pdf = Pdf::loadView('accounts.customer_kyc', [
            'customer' => $customer,
            'rentout' => $rentout,
            'companyInfo' => $companyInfo,
            'companyLogo' => CompanyLogoResolver::path(),
            'documentTypes' => $documentTypes,
            'submittedDocuments' => $submittedDocuments,
        ]);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'customer_kyc_'.$customer->name.'_'.now()->format('Y-m-d').'.pdf';

        return $pdf->stream($filename);
    }
}
